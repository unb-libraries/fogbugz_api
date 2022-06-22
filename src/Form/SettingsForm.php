<?php

namespace Drupal\fogbugz_api\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\Core\Url;

/**
 * Configure fogbugz settings for this site.
 */
class SettingsForm extends ConfigFormBase {

  /**
   * Config settings.
   *
   * @var string
   */
  const SETTINGS = 'fogbugz_api.settings';

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'fogbugz_form_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      static::SETTINGS,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config(static::SETTINGS);

    $form['#tree'] = TRUE;
    $form['alert_wrapper'] = [
      '#type' => 'container',
    ];
    $form['alert_wrapper']['alert'] = [
      '#type' => 'html_tag',
      '#tag' => 'div',
      '#attributes' => [
        'class' => [
          'messages',
          'messages--warning',
        ],
      ],
      '#value' => $this
        ->t('Changes to these settings are overwritten by site configuration on rebuild.'),
    ];

    $form['settings_wrapper'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Fogbugz Settings'),
    ];
    $form['settings_wrapper']['your_fogbugz'] = [
      '#type' => 'url',
      '#title' => $this->t('Installation URL'),
      '#description' => $this->t('Enter the url according to your Fogbugz install, eg. <code>https://support.lib.unb.ca</code>'),
      '#required' => TRUE,
      '#default_value' => $config->get('your_fogbugz') ?: '',
    ];
    $form['settings_wrapper']['email_from'] = [
      '#type' => 'email',
      '#title' => $this->t('Email Address'),
      '#description' => $this->t('Enter the email address to be used as the sender (i.e. sFrom argument) for FogBugz emails, eg. <code>libsystems@unb.ca</code>'),
      '#required' => TRUE,
      '#default_value' => $config->get('fogbugz_email') ?: '',
    ];

    $form['message_wrapper'] = [
      '#type' => 'container',
    ];
    $form['message_wrapper']['message'] = [
      '#type' => 'html_tag',
      '#tag' => 'span',
      '#value' => $this
        ->t('<i class="fas fa-info-circle mr-1"></i>
          Shortcut: FogBugz API @key-admin-url.',
          [
            '@key-admin-url' => Link::fromTextAndUrl('authentication key',
                                Url::fromRoute('entity.key.collection'))->toString(),
          ]
      ),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Retrieve the configuration.
    $this->configFactory->getEditable(static::SETTINGS)
      // Set the submitted configuration setting.
      ->set('your_fogbugz', $form_state
        ->getValue(['settings_wrapper', 'your_fogbugz']))
      ->set('fogbugz_email', $form_state
        ->getValue(['settings_wrapper', 'email_from']))
      ->save();

    parent::submitForm($form, $form_state);
  }

  /**
   * Implements form validation.
   *
   * The validateForm method is the default method called to validate input on
   * a form.
   *
   * @param array $form
   *   The render array of the currently built form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Object describing the current state of the form.
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    /* Email Address for FogBugz Sender */
    $email_from = trim($form_state
      ->getValue(['settings_wrapper', 'email_from'])
    );

    if (!\Drupal::service('email.validator')->isValid($email_from)) {
      $form_state
        ->setErrorByName('settings_wrapper][email_from',
          $this->t('@message', ['@message' => 'Invalid email address format']));
    }
  }

}
