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
    // Things that the manager can do. First, lets have "Create a new node".
    $actions = [
      'create_fun_random_article' => [
        'description' => 'Create a new article with a fun title and body about a random topic from health, science, or math.',
        'callback' => 'createFunRandomArticle',
      ],
      'create_multiple_articles' => [
        'description' => 'Create multiple articles with fun titles and bodies about random topics from health, science, or math.',
        'callback' => 'createMultipleArticles',
        'args' => [
          'numberToCreate' => 'A number that can be a max of 20.',
        ]
      ],
      


    ];

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
    $response = $this->utilities->getOpenAiResponse($prompt);
    $response = json_decode($response, TRUE);
    $nodeData = json_decode($response["choices"][0]["message"]["content"], TRUE);
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
   * A method that takes module_name and enables.
   * 
   * @param string $module_name
   * The name of the module to enable.
   */
  public function enableModule(string $module_name)
  {
    \Drupal::service('module_installer')->install([$module_name], TRUE);
    return $this->getStandardizedResult('enableModule', $module_name);
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
      $menuLink = \Drupal::entityTypeManager()->getStorage('menu_link_content')->create([
        'title' => $title,
        'link' => ['uri' => $url],
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

}
