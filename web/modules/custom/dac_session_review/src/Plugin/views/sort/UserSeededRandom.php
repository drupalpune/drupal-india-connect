<?php

namespace Drupal\dac_session_review\Plugin\views\sort;

use Drupal\Core\Form\FormStateInterface;
use Drupal\views\Attribute\ViewsSort;
use Drupal\views\Plugin\views\sort\SortPluginBase;

/**
 * Random order that is fixed per user.
 *
 * Core's "Global: Random" reshuffles on every request, so with a pager each
 * page is drawn from a fresh shuffle: rows repeat across pages and others
 * never show. This orders by a hash of the row ID and the current user's ID
 * instead, so each user gets their own shuffle that holds from page to page
 * and visit to visit. Removing a row (rating a session) doesn't reorder the
 * rest.
 */
#[ViewsSort("dac_user_seeded_random")]
class UserSeededRandom extends SortPluginBase {

  /**
   * {@inheritdoc}
   */
  public function usesGroupBy() {
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function query() {
    $this->ensureMyTable();
    $uid = (int) \Drupal::currentUser()->id();
    $formula = "MD5(CONCAT({$this->tableAlias}.{$this->realField}, ':', $uid))";
    $this->query->addOrderBy(NULL, $formula, 'ASC', $this->tableAlias . '_user_seeded_random');
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheContexts() {
    return array_merge(parent::getCacheContexts(), ['user']);
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);
    $form['order']['#access'] = FALSE;
  }

}
