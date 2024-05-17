<?php

namespace Drupal\aqto_ai_core\Plugin\Block;

use Drupal\Core\Block\BlockBase;

/**
 * Provides a 'Aqto AI Agent' block.
 *
 * @Block(
 *   id = "aqto_ai_core_agent",
 *   admin_label = @Translation("Aqto AI Agent"),
 * )
 */
class AgentFormBlock extends BlockBase {

  public function build() {
    $form = \Drupal::formBuilder()->getForm('Drupal\aqto_ai_core\Form\AgentForm');
    // Add a button to toggle the visibility of the form
    $form['#attached']['library'][] = 'aqto_ai_core/agent_form_toggle';

    $form['#prefix'] = '
      <div id="agent-form-toolbar" class="fixed bottom-0 right-0 p-4 bg-white border border-gray-200 rounded-lg shadow-lg max-h-96 overflow-y-auto">
        <div id="agent-form-toggle" class="mb-2 px-4 py-2 bg-blue-700 text-white rounded cursor-pointer">AqtoAssistant</div>
        <div id="agent-form-container" class="hidden">
    ';
    $form['#suffix'] = '
        </div>
      </div>
    ';

    return $form;
  }

}
