<?php

declare(strict_types=1);

namespace Drupal\aqto_ai_core\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\HtmlCommand;
use Drupal\Core\Render\Markup;

/**
 * Provides a Aqto AI Core form.
 */
final class AgentForm extends FormBase
{


  /**
   * {@inheritdoc}
   */
  public function getFormId(): string
  {
    return 'aqto_ai_core_agent';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array
  {

    $form['message'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Message'),
      '#required' => TRUE,
      '#description' => $this->t('What do you want to do?'),
      // Let's add a suffix where we put a nicely styled small description that indicates: "You can ask for things like 'Let\'s make a new module called my_custom_module'""
      '#suffix' => '<div class="text-sm text-gray-600">You can ask for things like "Let\'s make a new module called my_custom_module"</div>',

    ];

    $form['actions'] = [
      '#type' => 'actions',
      'submit' => [
        '#type' => 'submit',
        '#value' => $this->t('Send'),
        '#ajax' => [
          'callback' => '::messageSubmitAjax',
          'wrapper' => 'message-output',
        ],
      ],
    ];

    $form['output'] = [
      '#type' => 'markup',
      '#markup' => '<div id="message-output"></div>',
    ];



    return $form;
  }

  /**
   * AJAX callback to process the input message and interact with AI.
   */
  public function messageSubmitAjax(array &$form, FormStateInterface $form_state): AjaxResponse
  {
    $response = new AjaxResponse();
    $message = $form_state->getValue('message');
    $siteActionsManager = \Drupal::service('aqto_ai_core.site_actions_manager');
    $actionTaken = $siteActionsManager->invokeActionableQuestion($message);
    // If we have a "report" in the payload, we want to output it with some nice tailwind classes and text styling
    if (isset($actionTaken['report'])) {
      $report_markup = Markup::create($actionTaken['report']);
    }
    $output_render_array = [];
    $output_render_array[] = [
      '#markup' => $this->t('Action taken: @action, status: @status', [
        '@action' => $actionTaken['action'],
        '@status' => $actionTaken['status'],
      ]),
    ];
    if (isset($report_markup)) {
      $output_render_array[] = [
        '#markup' => $report_markup->__toString(),
      ];
    }
    $response->addCommand(new HtmlCommand(
      '#message-output',
      $output_render_array
    ));
    
    return $response;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state): void
  {
    // if (mb_strlen($form_state->getValue('message')) < 10) {
    //   $form_state->setErrorByName('message', $this->t('Message should be at least 10 characters.'));
    // }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void
  {
    // We might not need this if everything is handled in AJAX
  }
}
