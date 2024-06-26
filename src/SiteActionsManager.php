<?php

declare(strict_types=1);

namespace Drupal\aqto_ai_core;

use Psr\Http\Client\ClientInterface;


/**
 * @todo Add class description.
 */
final class SiteActionsManager
{


  use SiteActionsTrait;

  /**
   * Constructs a SiteActionsManager object.
   */
  public function __construct(
    private readonly ClientInterface $httpClient,
    private readonly Utilities $utilities,
  ) {
  }

  /**
   * @todo Add method description.
   */
  public function listActions()
  {
    // Things that the manager can do provided by other modules and aqto_ai_core itself.
    $actions = [];

    // Lets also gather actions from any modules implementing hook_aqto_ai_actions_available().
    $moduleHandler = \Drupal::moduleHandler();
    $modules = $moduleHandler->getModuleList();
    foreach ($modules as $module => $moduleData) {
      $hookName = $module . '_aqto_ai_actions_available';
      if (function_exists($hookName)) {
        $moduleActions = $hookName();
        $actions = array_merge($actions, $moduleActions);
      }
    }
    // Lets also gather actions from any modules implementing hook_aqto_ai_actions_available().
    return $actions;
  }




  /**
   * A public helper to give access ot the utilities
   */
  public function getUtilities()
  {
    return $this->utilities;
  }

  /**
   * A helper that takes a question as input and gets the feedback from openAI on which of the available actions would apply. Then, we get back the answer and apply the callback.
   */
  public function invokeActionableQuestion(string $question, string $model = "gpt-4o")
  {
    $all_actions = $this->listActions();
    $all_actions_in_json = json_encode($all_actions);
    $prompt = "You must provide just a single string response. You need to give us clarification on which of the possible actions to take based on the question. The actions data is like this: $all_actions_in_json. The question is: $question. Feel free to reply with the 'error' if there is no action that applies. Provide json with func_name, service_name and method_name, and args array if applicable. Consider extra_context if available";
    $action_data = $this->utilities->getOpenAiJsonResponse($prompt, $model);


    if ($action_data === NULL) {
      // Handle error if JSON is still not decodable
      \Drupal::logger('aqto_ai_core')->error('Failed to decode JSON: ' . json_last_error_msg());
    } else {
      // Proceed with using $action_data
      \Drupal::logger('aqto_ai_core')->info('Decoded JSON data: ' . print_r($action_data, TRUE));
    }
    if ($action_data['func_name'] == 'error') {
      return $action_data;
    }
    // If we have a service_name and method_name keys, we can programmatically call that service and method with the $args array. Let's just pass the arg values and not use the keys
    if (isset($action_data['service_name']) && isset($action_data['method_name'])) {
      $service = \Drupal::service($action_data['service_name']);
      $method = $action_data['method_name'];
      $args = $action_data['args'] ?? [];
      return $service->$method(...$args);
    } else {
      $callback = $all_actions[$action_data['func_name']]['callback'];
    }
    $args = $action_data['args'] ?? [];
    // Check if the callback is something like "\Drupal::service()", if so, we can call it like that. Otherwise try and invoke on this object.
    if (strpos($callback, '::') !== FALSE) {
      return $callback(...$args);
    }
    return $this->$callback(...$args);
  }

  /**
   * A createFunRandomArticle that will create a node.
   * 
   * Using the getOpenAiResponse() method from Utilities, we can get a response from OpenAI API.
   */
  public function createFunRandomArticle()
  {
    $prompt = "You are creating a node with a fun title and body about a random topic from health, science, or math. Provide JSON formatted data only for the node. The keys should be - title and body. So the object should be like this: {\"title\": \"Your title here\", \"body\": \"Your body here\"}";
    $response = $this->utilities->getOpenAiResponse($prompt);
    $response = json_decode($response, TRUE);
    $nodeData = json_decode($response["choices"][0]["message"]["content"], TRUE);
    $nodeData['type'] = 'article';
    $nodeData['status'] = 1;
    $node = \Drupal::entityTypeManager()->getStorage('node')->create($nodeData);
    $node->save();
    return $this->getStandardizedResult('createFunRandomArticle', $nodeData);
  }

  /**
   * A createMultipleArticles callback.
   * 
   * Takes the number to create and then creates multiple articles.
   */
  public function createMultipleArticles(int $numberToCreate = 10)
  {
    $prompt = "You are creating multiple nodes with fun titles and bodies about random topics from health, science, or math. Provide JSON formatted data with information for $numberToCreate nodes. The json should have objects where each of the keys should be - title and body for each of the nodes. So the object should be like this: [{\"title\": \"Your title here\", \"body\": \"Your body here\"}, {\"title\": \"Your title here\", \"body\": \"Your body here\"}, ...]";
    $nodeData = $this->utilities->getOpenAiJsonResponse($prompt);

    foreach ($nodeData as $nodeDatum) {
      $nodeDatum['type'] = 'article';
      $nodeDatum['status'] = 1;
      $node = \Drupal::entityTypeManager()->getStorage('node')->create($nodeDatum);
      $node->save();
    }
    return $this->getStandardizedResult('createMultipleArticles', $nodeData);
  }

  /**
   * A clearCaches method that replicates a core cache rebuild op.
   */
  public function clearCaches()
  {
    drupal_flush_all_caches();
    return $this->getStandardizedResult('clearCaches', 'Caches cleared');
  }

  /**
   * A method that takes module_names array of module and enable each with deps.
   * 
   * @param string $requested_modules
   * A natural language or other structure of the modules to work on. Could be specific or generic.
   * 
   * @return array
   * An array of the enabled modules.
   */
  public function enableModules(string $module_names)
  {
    $module_names = $this->figureOutWhatModulesToWorkOn($module_names);
    $moduleHandler = \Drupal::service('module_handler');
    $enabledModules = [];
    foreach ($module_names as $module_name) {
      $moduleHandler->install([$module_name]);
      $enabledModules[] = $module_name;
    }
    return $this->getStandardizedResult('enableModules', $enabledModules);
  }

  /**
   * A method that takes module_names array of module and disables each.
   * 
   * @param string $requested_modules
   * A natural language or other structure of the modules to work on. Could be specific or generic.
   * 
   * @return array
   * An array of the disabled modules.
   */
  public function disableAndUninstallModules(string $module_names)
  {
    $module_names = $this->figureOutWhatModulesToWorkOn($module_names);
    $moduleHandler = \Drupal::service('module_handler');
    $moduleInstaller = \Drupal::service('module_installer');
    $disabledModules = [];
    $uninstalledModules = [];

    foreach ($module_names as $module_name) {
      if ($moduleHandler->moduleExists($module_name)) {
        // Disable the module first
        $moduleInstaller->uninstall([$module_name], FALSE);
        $disabledModules[] = $module_name;
      }
    }

    foreach ($module_names as $module_name) {
      // Uninstall the module
      $moduleInstaller->uninstall([$module_name]);
      $uninstalledModules[] = $module_name;
    }

    // Make a $report variable that will list all the modules that were uninstalled in a nicely styled tailwind div
    $report = '<div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative" role="alert"><strong class="font-bold">Success!</strong><span class="block sm:inline"> The following modules were uninstalled: ' . implode(', ', $uninstalledModules) . '</span></div>';

    return $this->getStandardizedResult('disableAndUninstallModules', $uninstalledModules, $report);
  }

  /**
   * Generates a report of all the current enabled modules on site.
   */
  public function generateEnabledModulesReport()
  {
    $allModules = \Drupal::moduleHandler()->getModuleList();
    $enabledModules = [];
    foreach ($allModules as $module => $moduleData) {
      $enabledModules[] = $module;
    }
    $report = '<div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded" role="alert"><strong class="font-bold">Success!</strong><span class="block sm:inline"> The following modules are enabled: ' . implode(', ', $enabledModules) . '</span></div>';
    return $this->getStandardizedResult('generateEnabledModulesReport', $enabledModules, 'success', $report);
  }


  /**
   * Gets a list of all applicable site modules based on the natural language input. Like if user provides us with "Disable taht admin toolbar module", or  "All modules that start with the letter 't' disable!", we figure out which possible ones meet the criteria via query.
   * 
   * @param string $requested_modules
   * A natural language or other structure of the modules to work on. Could be specific or generic.
   * 
   * @return array
   * An array of the validated module names.
   */
  public function figureOutWhatModulesToWorkOn(string $requested_modules)
  {
    $allModules = \Drupal::moduleHandler()->getModuleList();
    // Lets use openAI query to ask to compare rqeusted against all, and provide json response of valid modules.
    $prompt = "We have received a user query, and have the module_names that we need to figure out which modules they might mean from our current available modules. The modules we currently have are: " . json_encode(array_keys($allModules)) . ". The module_names or a general inclination of the modules described that we have from the user to figure out are: " . $requested_modules . ". Please provide the machine names of the modules that you think the user is referring to. Provide your answer strictly as a JSON array of machine_names of the modules, OR the string 'error' if no modules apply.";
    $response = $this->utilities->getOpenAiJsonResponse($prompt);
    return $response;
  }

  /**
   * A method that takes menu_name and menu_data and creates menu links.
   * 
   * @param string $menu_name
   * The name of the menu to create links in.
   * 
   * @param array $menu_data
   * An array of title -> url data.
   * 
   * @return array
   * An array of the created menu links.
   *
   */
  public function makeMenuLinks(string $menu_name, array $menu_data)
  {
    // The menu name is coming from natural language request from user. We want to load all available menus, then use the askOpenAi method to get a single response based on the natural provided menu and what is available, and provide best guess or error if no available menu, to return just the machine name of menu we need.
    $allMenus = \Drupal::entityTypeManager()->getStorage('menu')->loadMultiple();
    $allMenuNames = [];
    foreach ($allMenus as $menu) {
      $allMenuNames[$menu->id()] = $menu->label();
    }
    $prompt = "We have received a user query, and have the menu_name that we need to figure out which menu they might mean from our current available menus. THe menus we currently have, keyed by their ID and label, are: " . json_encode($allMenuNames) . ". The menu_name we have from the user to update is: $menu_name. Please provide the machine name of the menu that you think the user is referring to. Provide your answer strictly as a machine_name of the menu, OR the string 'error' if no menu applies.";
    $response = $this->utilities->getOpenAiResponse($prompt);
    $response = json_decode($response, TRUE);
    $menu_name = $response["choices"][0]["message"]["content"];
    if ($menu_name == 'error') {
      return $this->getStandardizedResult('makeMenuLinks', 'No menu found');
    }

    $menuLinks = [];
    $menu = \Drupal::entityTypeManager()->getStorage('menu')->load($menu_name);
    foreach ($menu_data as $title => $url) {
      // Lets make sure the $Url, which could be like "/foo", has to be a proper uri
      $uri = \Drupal\Core\Url::fromUserInput($url)->toUriString();
      $menuLink = \Drupal::entityTypeManager()->getStorage('menu_link_content')->create([
        'title' => $title,
        'link' => ['uri' => $uri],
        'menu_name' => $menu_name,
      ]);
      
      $menuLink->save();
      $menuLinks[] = $menuLink;
    }
    // Lets flush caches now
    drupal_flush_all_caches();
    return $this->getStandardizedResult('makeMenuLinks', $menuLinks);
  }

  /**
   * A method that takes site_name and updates the site name.
   * 
   * @param string $site_name
   * The new site name.
   */
  public function updateSiteName(string $site_name)
  {
    $config = \Drupal::service('config.factory')->getEditable('system.site');
    $config->set('name', $site_name);
    $config->save();
    return $this->getStandardizedResult('updateSiteName', $site_name);
  }

  // Add a listActions that just lists all the keys and descriptions of the actions available.
  public function get_available_actions_with_descriptions() {
    $actions = $this->listActions();
    $actions_with_descriptions = [];
    foreach ($actions as $action => $actionData) {
      $actions_with_descriptions[$action] = $actionData['description'];
    }
    // Get some nice html from $actions_with_descriptions via openAI
    $prompt = "We have received a user query to list all the available actions with descriptions. The actions we currently have are: " . json_encode($actions_with_descriptions) . ". Return a JSON ONLY with a 'report_html' key with the value is a Tailwind based html with a a summary of what can be asked given the various actions";
    $report_response = $this->utilities->getOpenAiJsonResponse($prompt);
    $report_html = $report_response['report_html'];

    return $this->getStandardizedResult('get_available_actions_with_descriptions', $actions_with_descriptions, 'success', $report_html);

  }
}
