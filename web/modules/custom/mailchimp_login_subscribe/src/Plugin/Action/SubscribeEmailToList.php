<?php

namespace Drupal\mailchimp_login_subscribe\Plugin\Action;

use Drupal\Core\Action\Attribute\Action;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\eca\Attribute\EcaAction;
use Drupal\eca\Plugin\Action\ConfigurableActionBase;

/**
 * Subscribes an email address to a Mailchimp Audience.
 *
 * Wraps mailchimp_subscribe() so it can be used as an ECA action, e.g. wired
 * to the "User: after login" event to opt users into the newsletter list on
 * login. list_id and email are both token-enabled, so the model can read the
 * Audience ID from a token set elsewhere and the email from the account that
 * triggered the event (e.g. [account:mail]).
 */
#[Action(
  id: 'mailchimp_login_subscribe_subscribe_email',
  label: new TranslatableMarkup('Mailchimp: Subscribe email to Audience'),
)]
#[EcaAction(
  description: new TranslatableMarkup('Subscribe an email address to a Mailchimp Audience (list).'),
  version_introduced: '1.0.0',
)]
class SubscribeEmailToList extends ConfigurableActionBase {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration(): array {
    return [
      'list_id' => '',
      'email' => '[account:mail]',
      'double_optin' => FALSE,
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state): array {
    $form['list_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Audience (list) ID'),
      '#default_value' => $this->configuration['list_id'],
      '#description' => $this->t('The Mailchimp Audience ID to subscribe to. Find it under Audience settings in Mailchimp, or via the "Get signup lists" ECA action.'),
      '#required' => TRUE,
      '#eca_token_replacement' => TRUE,
    ];
    $form['email'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Email address'),
      '#default_value' => $this->configuration['email'],
      '#description' => $this->t('The email address to subscribe. Defaults to the account that triggered the event.'),
      '#required' => TRUE,
      '#eca_token_replacement' => TRUE,
    ];
    $form['double_optin'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Require double opt-in'),
      '#default_value' => $this->configuration['double_optin'],
      '#description' => $this->t('If checked, Mailchimp sends a confirmation email before the subscription is active.'),
    ];
    return parent::buildConfigurationForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state): void {
    $this->configuration['list_id'] = $form_state->getValue('list_id');
    $this->configuration['email'] = $form_state->getValue('email');
    $this->configuration['double_optin'] = (bool) $form_state->getValue('double_optin');
    parent::submitConfigurationForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function execute(): void {
    $list_id = (string) $this->tokenService->replaceClear($this->configuration['list_id']);
    $email = (string) $this->tokenService->replaceClear($this->configuration['email']);

    if ($list_id === '' || $email === '' || !\Drupal::service('email.validator')->isValid($email)) {
      return;
    }

    mailchimp_subscribe($list_id, $email, NULL, [], $this->configuration['double_optin']);
  }

}
