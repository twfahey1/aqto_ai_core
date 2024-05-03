![Aqto AI Core](logo.webp)

## INTRODUCTION

The Aqto AI Core module provides a base for AI integrations in Drupal 10.

The primary use case for this module is:

- Use as a base for other modules that leverage shared configurations like API keys.


## INSTALLATION

- Configure `settings.local.php` / `settings.php` to include the following:

```php
$config['aqto_ai_core.settings'] = [
  'openai_api_key' => 'sk-1234567890',
];
```

Install as you would normally install a contributed Drupal module.
See: https://www.drupal.org/node/895232 for further information.

## CONFIGURATION
- Configure the `openai_api_key` in the `aqto_ai_core.settings` configuration.
- Assign the `Administer aqto_ai_core configuration` permission to the appropriate roles.
- Optional: Test out the `/aqto-ai-core/test` route and ask it to generate some number of articles, e.g. "Hey, I need 6 articles, please."

## MAINTAINERS

Current maintainers for Drupal 10:

- Tyler Fahey - https://www.drupal.org/u/twfahey

