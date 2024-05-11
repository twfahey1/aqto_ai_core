<?php

declare(strict_types=1);

namespace Drupal\aqto_ai_core;

use Drupal\Core\Config\ConfigFactoryInterface;

/**
 * @todo Add class description.
 */
final class Utilities {

  /**
   * Constructs an Utilities object.
   */
  public function __construct(
    private readonly ConfigFactoryInterface $configFactory,
  ) {}

  /**
   * @todo Add method description.
   */
  public function getOpenAiApiKeyFromConfig(): string {
    $config = $this->configFactory->get('aqto_ai_core.settings');
    return $config->get('openai_api_key');
  }

  /**
   * A helper that takes a prompt and expects JSON so returns it sanitized and extracted directly.
   * 
   */
  public function getOpenAiJsonResponse(string $prompt): array {
    $response = $this->getOpenAiResponse($prompt);
    $response = json_decode($response, TRUE);
    $action_raw_response_data = $response["choices"][0]["message"]["content"];
    // Retrieve the raw response data
    $action_raw_response_data = $response["choices"][0]["message"]["content"];
  
    // Remove the initial and closing backticks along with 'json' and extra whitespaces
    $action_raw_response_data = preg_replace('/^```json|```$/s', '', $action_raw_response_data);
    $action_raw_response_data = trim($action_raw_response_data);

    // Decode the JSON data
    $action_data = json_decode($action_raw_response_data, TRUE);

    // Check for JSON errors
    if (json_last_error() !== JSON_ERROR_NONE) {
        \Drupal::logger('aqto_ai_core')->error('JSON decode error: ' . json_last_error_msg());
        return NULL;
    }
    return $action_data;
  }

  /**
   * Helper that takes an initial prompt as a string, makes request to OpenAI API.
   */
  public function getOpenAiResponse(string $prompt): string {
    $apiKey = $this->getOpenAiApiKeyFromConfig();
    $url = "https://api.openai.com/v1/chat/completions";
    $data = [
      "model" => "gpt-4-turbo",
      "messages" => [
        [
          "role" => "user",
          "content" => $prompt,
        ],
      ],
      "max_tokens" => 1024,
      "temperature" => 0.5,
    ];
    $options = [
      "http" => [
        "header" => "Content-type: application/json\r\nAuthorization: Bearer $apiKey",
        "method" => "POST",
        "content" => json_encode($data),
      ],
    ];
    $context = stream_context_create($options);
    $result = file_get_contents($url, false, $context);
    return $result;
  }
  

}
