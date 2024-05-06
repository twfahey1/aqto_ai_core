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
   * Helper that takes an initial prompt as a string, makes request to OpenAI API.
   */
  public function getOpenAiResponse(string $prompt): string {
    $apiKey = $this->getOpenAiApiKeyFromConfig();
    $url = "https://api.openai.com/v1/chat/completions";
    $data = [
      "model" => "gpt-3.5-turbo",
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
