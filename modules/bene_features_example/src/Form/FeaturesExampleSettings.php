<?php

namespace Drupal\bene_features_example\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configure Your Settings.
 */
class FeaturesExampleSettings extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'bene_features_example_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['bene_features_example.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);

    // Add form header describing purpose and use of form.
    $form['header'] = [
      '#type' => 'markup',
      '#markup' => t('<h3>A description of what this feature does.</h3>'),
    ];

    $settings = $this->config('bene_features_example.settings')->get();
    $form['example_radio'] = [
      '#title' => t('Example Radio'),
      '#type' => 'radios',
      '#default_value' => isset($settings['example_radio']) ? $settings['example_radio'] : 'off',
      '#options' => [
        'on' => 'On',
        'off' => 'Off',
      ],
      '#required' => TRUE,
      '#description' => t('An example of a Radio button.'),
    ];

    $form['example_text'] = [
      '#title' => t('Example Text Field'),
      '#type' => 'textfield',
      '#default_value' => isset($settings['example_text']) ? $settings['example_text'] : '',
      '#states' => [
        'visible' => [
          ':input[name="example_radio"]' => ['value' => 'on'],
        ],
      ],
    ];

    $form['actions']['submit']['#value'] = t('Save');

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $settings = $this->configFactory()->getEditable('bene_features_example.settings');
    $values = $form_state->cleanValues()->getValues();
    $settings->set('example_radio', $values['example_radio']);
    $settings->set('example_text', $values['example_text']);
    $settings->save();
    parent::submitForm($form, $form_state);
  }

}
