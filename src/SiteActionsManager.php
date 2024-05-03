<?php

declare(strict_types=1);

namespace Drupal\aqto_ai_core;

use Psr\Http\Client\ClientInterface;


/**
 * @todo Add class description.
 */
final class SiteActionsManager {

  /**
   * Constructs a SiteActionsManager object.
   */
  public function __construct(
    private readonly ClientInterface $httpClient,
    private readonly Utilities $utilities,
  ) {}

  /**
   * @todo Add method description.
   */
  public function listActions() {
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
    return $actions;
  }

  /**
   * Returns a standardized array of a "result" from an action taken. 
   * 
   * We have an arg of some data chunk that we can return as well as the "status".
   */
  public function getStandardizedResult($action, $data, $status = 'success') {
    return [
      'action' => $action,
      'status' => $status,
      'data' => $data,
    ];
  }

  /**
   * A helper that takes a question as input and gets the feedback from openAI on which of the available actions would apply. Then, we get back the answer and apply the callback.
   */
  public function askQuestion(string $question) {
    $all_actions = $this->listActions();
    $all_actions_in_json = json_encode($all_actions);
    $prompt = "You need to give us clarification on which of the possible actions to take based on the question. The actions data is like this: $all_actions_in_json. The question is: $question. Feel free to reply with the 'error' if there is no action that applies. Provide json with func_name, and args array if applicable.";
    $response = $this->utilities->getOpenAiResponse($prompt);
    $response = json_decode($response, TRUE);
    $action_raw_response_data = $response["choices"][0]["message"]["content"];
    $action_data = json_decode($action_raw_response_data, TRUE);
    if ($action_data['func_name'] == 'error') {
      return $action_data;
    }
    $callback = $all_actions[$action_data['func_name']]['callback'];
    $args = $action_data['args'] ?? [];
    return $this->$callback(...$args);
  }

  /**
   * A createFunRandomArticle that will create a node.
   * 
   * Using the getOpenAiResponse() method from Utilities, we can get a response from OpenAI API.
   */
  public function createFunRandomArticle() {
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
  public function createMultipleArticles(int $numberToCreate = 10) {
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
  
}
