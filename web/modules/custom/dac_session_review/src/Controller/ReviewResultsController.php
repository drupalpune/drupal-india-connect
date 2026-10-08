<?php

namespace Drupal\dac_session_review\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Database\Connection;
use Drupal\Core\Url;
use Drupal\webform\Entity\WebformOptions;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Review results: every reviewed session with its average moderator scores.
 *
 * Averages are worked out here from the session_evaluation submissions
 * rather than in a view: Views' webform aggregation gives one overall
 * average, but not one per criterion or a count of reviewers per session.
 */
class ReviewResultsController extends ControllerBase {

  /**
   * Columns that can be sorted, and their default direction.
   */
  const SORTS = [
    'score' => 'desc',
    'reviews' => 'desc',
    'title' => 'asc',
  ];

  public function __construct(protected Connection $database) {}

  public static function create(ContainerInterface $container) {
    return new static($container->get('database'));
  }

  /**
   * Builds the page.
   */
  public function page(Request $request): array {
    $webform = $this->entityTypeManager()->getStorage('webform')->load('session_evaluation');
    $ratings_element = $webform->getElement('ratings');
    $questions = array_values($ratings_element['#questions']);
    $answers_element = ['#options' => $ratings_element['#answers']];
    $max = max(array_map('intval', array_keys(WebformOptions::getElementOptions($answers_element))));

    $tracks = [];
    foreach ($this->entityTypeManager()->getStorage('taxonomy_term')->loadTree('session_category', 0, 1, TRUE) as $term) {
      $tracks[$term->id()] = $term->label();
    }
    $track = (string) $request->query->get('track', '');
    if (!isset($tracks[$track])) {
      $track = '';
    }

    // Session moderators only see their assigned track (the admin-only
    // "Session category" on their account) and get no track filter; the
    // ?track= parameter is ignored for them. See _dac_session_review_track().
    $assigned = _dac_session_review_track($this->currentUser());
    $show_filter = $assigned === NULL;
    $no_track = FALSE;
    if (!$show_filter) {
      $track = (string) $assigned;
      if (!isset($tracks[$track])) {
        $track = '';
        $no_track = TRUE;
      }
    }

    $sort_options = self::SORTS;
    foreach (array_keys($questions) as $delta) {
      $sort_options['q' . $delta] = 'desc';
    }
    $sort = (string) $request->query->get('sort', 'score');
    if (!isset($sort_options[$sort])) {
      $sort = 'score';
    }
    $order = $request->query->get('order') === 'asc' ? 'asc' : ($request->query->get('order') === 'desc' ? 'desc' : $sort_options[$sort]);

    $rows = $no_track ? [] : $this->buildRows($questions, $track);
    $this->sortRows($rows, $sort, $order);

    // Rank always follows the overall average, whatever the table is sorted
    // by; sessions with the same average share a rank.
    $averages = array_column($rows, 'score');
    rsort($averages);
    foreach ($rows as &$row) {
      $row['rank'] = array_search($row['score'], $averages, TRUE) + 1;
      $row['percent'] = round($row['score'] / $max * 100);
      $row['score_display'] = number_format($row['score'], 1);
      $row['criteria_display'] = array_map(fn($value) => $value === NULL ? '–' : number_format($value, 1), $row['criteria']);
    }
    unset($row);

    $headers = [];
    $columns = ['title' => $this->t('Session'), 'reviews' => $this->t('Reviews')];
    foreach ($questions as $delta => $question) {
      $columns['q' . $delta] = $question;
    }
    $columns['score'] = $this->t('Average');
    foreach ($columns as $key => $label) {
      $active = $key === $sort;
      $next = $active ? ($order === 'asc' ? 'desc' : 'asc') : $sort_options[$key];
      $headers[$key] = [
        'label' => $label,
        'url' => Url::fromRoute('dac_session_review.results', [], [
          'query' => array_filter(['track' => $show_filter ? $track : '', 'sort' => $key, 'order' => $next]),
        ])->toString(),
        'active' => $active,
        'order' => $active ? $order : NULL,
      ];
    }

    return [
      '#theme' => 'session_review_results',
      '#rows' => $rows,
      '#headers' => $headers,
      '#tracks' => $tracks,
      '#track' => $track,
      '#track_label' => $track ? $tracks[$track] : NULL,
      '#sort' => $sort,
      '#order' => $order,
      '#max' => $max,
      '#unreviewed' => $no_track ? 0 : $this->countUnreviewed(array_column($rows, 'nid'), $track),
      '#show_filter' => $show_filter,
      '#no_track' => $no_track,
      '#form_action' => Url::fromRoute('dac_session_review.results')->toString(),
      '#attached' => ['library' => ['dac_session_review/review-results']],
      '#cache' => [
        'contexts' => ['url.query_args', 'user.permissions', 'user'],
        'tags' => ['webform_submission_list', 'node_list', 'taxonomy_term_list', 'config:webform.webform.session_evaluation', 'user:' . $this->currentUser()->id()],
      ],
    ];
  }

  /**
   * One row per reviewed session in the track, with average scores.
   */
  protected function buildRows(array $questions, string $track): array {
    $query = $this->database->select('webform_submission', 's');
    $query->join('webform_submission_data', 'd', 'd.sid = s.sid');
    $query->fields('s', ['sid'])
      ->fields('d', ['name', 'property', 'value'])
      ->condition('s.webform_id', 'session_evaluation')
      ->condition('s.in_draft', 0)
      ->condition('d.name', ['session', 'ratings'], 'IN');

    // Gather each submission's session and answers first: the session
    // reference and the ratings are separate rows in the data table.
    $submissions = [];
    foreach ($query->execute() as $record) {
      if ($record->name === 'session') {
        $submissions[$record->sid]['session'] = (int) $record->value;
      }
      elseif ($record->value !== '') {
        $submissions[$record->sid]['ratings'][$record->property] = (int) $record->value;
      }
    }

    $by_session = [];
    foreach ($submissions as $submission) {
      if (empty($submission['session']) || empty($submission['ratings'])) {
        continue;
      }
      $by_session[$submission['session']][] = $submission['ratings'];
    }

    $nodes = $this->entityTypeManager()->getStorage('node')->loadMultiple(array_keys($by_session));
    $rows = [];
    foreach ($by_session as $nid => $reviews) {
      $node = $nodes[$nid] ?? NULL;
      if (!$node || $node->bundle() !== 'session') {
        continue;
      }
      $category = $node->get('field_session_category')->entity;
      if ($track && (!$category || (string) $category->id() !== $track)) {
        continue;
      }

      $criteria = [];
      $all = [];
      foreach ($questions as $question) {
        $values = array_column($reviews, $question);
        $criteria[] = $values ? array_sum($values) / count($values) : NULL;
        array_push($all, ...$values);
      }
      if (!$all) {
        continue;
      }

      $state = $node->hasField('moderation_state') ? $node->get('moderation_state')->value : NULL;
      $rows[] = [
        'nid' => $nid,
        'title' => $node->label(),
        'url' => $node->toUrl()->toString(),
        'track' => $category?->label(),
        'state' => $state,
        'state_label' => $state ? $this->stateLabel($state) : NULL,
        'reviews' => count($reviews),
        'criteria' => $criteria,
        'score' => array_sum($all) / count($all),
      ];
    }
    return $rows;
  }

  /**
   * Sorts rows in place; ties fall back to average, then title.
   */
  protected function sortRows(array &$rows, string $sort, string $order): void {
    usort($rows, function ($a, $b) use ($sort, $order) {
      $value = fn($row) => match (TRUE) {
        $sort === 'title' => mb_strtolower($row['title']),
        $sort === 'reviews' => $row['reviews'],
        str_starts_with($sort, 'q') => $row['criteria'][(int) substr($sort, 1)] ?? -1,
        default => $row['score'],
      };
      $result = $value($a) <=> $value($b);
      if ($order === 'desc') {
        $result = -$result;
      }
      return $result
        ?: ($b['score'] <=> $a['score'])
        ?: strcasecmp($a['title'], $b['title']);
    });
  }

  /**
   * Proposed sessions in the track that nobody has reviewed yet.
   */
  protected function countUnreviewed(array $reviewed_nids, string $track): int {
    // moderation_state is a computed field, so an entity query cannot filter
    // on it; read the state from content_moderation's own table.
    $query = $this->database->select('node_field_data', 'n');
    $query->join('content_moderation_state_field_data', 'm', "m.content_entity_type_id = 'node' AND m.content_entity_id = n.nid AND m.content_entity_revision_id = n.vid AND m.langcode = n.langcode");
    $query->condition('n.type', 'session')
      ->condition('n.default_langcode', 1)
      ->condition('m.moderation_state', 'proposed');
    if ($track) {
      $query->join('node__field_session_category', 'c', 'c.entity_id = n.nid AND c.langcode = n.langcode');
      $query->condition('c.field_session_category_target_id', $track);
    }
    if ($reviewed_nids) {
      $query->condition('n.nid', $reviewed_nids, 'NOT IN');
    }
    return (int) $query->countQuery()->execute()->fetchField();
  }

  /**
   * The workflow's label for a moderation state.
   */
  protected function stateLabel(string $state): string {
    static $labels;
    if ($labels === NULL) {
      $labels = [];
      $workflow = $this->entityTypeManager()->getStorage('workflow')->load('session_acceptance');
      foreach ($workflow ? $workflow->getTypePlugin()->getStates() : [] as $id => $workflow_state) {
        $labels[$id] = $workflow_state->label();
      }
    }
    return $labels[$state] ?? $state;
  }

}
