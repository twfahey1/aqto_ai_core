<?php

namespace Drupal\aqto_ai_core\Plugin\Block;

use Drupal\Core\Block\BlockBase;

/**
 * Provides a 'Aqto AI Agent' block.
 *
 * @Block(
 *   id = "aqto_ai_core_agent",
 *   admin_label = @Translation("Aqto AI Agent"),
 *   category = @Translation("Aqto AI"),
 * )
 */
class AgentFormBlock extends BlockBase {

  public function build() {
    $form = \Drupal::formBuilder()->getForm('Drupal\aqto_ai_core\Form\AgentForm');
    // Add a button to toggle the visibility of the form
    $form['#attached']['library'][] = 'aqto_ai_core/agent_form_toggle';

    $form['#prefix'] = '
    <div id="agent-form-toolbar" class="fixed bottom-0 right-0 p-4 bg-white border border-gray-200 rounded-lg shadow-lg h-96 overflow-y-auto">
      <div class="flex flex-col">
        <div id="agent-form-toggle" class="glowing-button mb-2 px-4 py-2 bg-blue-700 text-white rounded cursor-pointer">AqtoAssistant</div>
        <div class="flex flex-row mb-2">
          <div id="agent-form-move" class="w-100 text-xs px-4 py-2 bg-green-700 text-white rounded cursor-move mr-2">+Move</div>
          <div id="agent-form-reset" class="w-100 text-xs px-4 py-2 bg-yellow-500 text-black rounded cursor-pointer">Reset</div>
        </div>
      </div>
      <div id="agent-form-container" class="hidden">
  ';

    $form['#suffix'] = '
        </div>
      </div>
    ';

    return $form;
  }

}
