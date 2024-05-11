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
    // Lets wrap the form in a div with Tailwind classes to put the form in a bottom right hovering toolbar thats mobile friendly.
    $form['#prefix'] = '<div class="fixed bottom-0 right-0 p-4 bg-white border border-gray-200 rounded-lg shadow-lg">';
    $form['#suffix'] = '</div>';
    return $form;
  }

}
