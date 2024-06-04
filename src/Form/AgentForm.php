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
      '#title' => $this->t('What do you want to do?'),
      '#required' => TRUE,
      '#description' => $this->t(''),
      // Let's add a suffix where we put a nicely styled small description that indicates: "You can ask for things like 'Let\'s make a new module called my_custom_module'""
      '#suffix' => '<div class="text-sm text-gray-600">Ask "What can you do?" for the available actions.</div>',
      '#attributes' => [
        'placeholder' => $this->t('Create a new module called "hello_world"'),
      ],
      '#weight' => '0',
    ];

    $form['actions'] = [
      '#type' => 'actions',
      '#weight' => '1',
      'submit' => [
        '#type' => 'submit',
        '#value' => $this->t('Send'),
        '#ajax' => [
          'callback' => '::messageSubmitAjax',
          'wrapper' => 'aqto-assistant-message-output',
        ],
      ],
      // We need to add a margin bottom
      '#attributes' => [
        'class' => ['mb-4'],
      ],
    ];

    $form['aqto_output_fieldset'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Output'),
      '#weight' => '2',
      // We can use Tailwind classes to style the fieldset
      '#attributes' => [
        'class' => ['bg-gray-100', 'p-4', 'rounded-lg'],
      ],
    ];

    $form['aqto_output_fieldset']['output'] = [
      '#type' => 'markup',
      '#markup' => '<div id="aqto-assistant-message-output"></div>',
    ];


    // We want to add Tailwind attributes to make our agent window rise above all when it's open. We just need to add classes to the wrapper 
    $extra_classes =  ['z-50', 'fixed', 'bottom-0', 'right-0', 'bg-white', 'p-4', 'rounded-lg', 'shadow-lg'];
    // Merge with existing class
    $form['#attributes']['class'] = array_merge($form['#attributes']['class'], $extra_classes);

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
      '#aqto-assistant-message-output',
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
