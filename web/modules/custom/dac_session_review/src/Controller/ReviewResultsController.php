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

    // Session moderators only see their assigned tracks (the admin-only
    // "Session category" on their account): the filter offers just those,
    // and is hidden when there is only one. See _dac_session_review_tracks().
    $assigned = _dac_session_review_tracks($this->currentUser());
    if ($assigned !== NULL) {
      $tracks = array_intersect_key($tracks, array_flip($assigned));
    }
    $show_filter = $assigned === NULL || count($tracks) > 1;
    $no_track = !$tracks;

    $track = $show_filter ? (string) $request->query->get('track', '') : (string) array_key_first($tracks);
    if (!isset($tracks[$track])) {
      $track = '';
    }
    // The tracks the table is limited to; empty means every track.
    $limit = $track !== '' ? [$track] : ($assigned === NULL ? [] : array_keys($tracks));

    $sort_options = self::SORTS;
    foreach (array_keys($questions) as $delta) {
      $sort_options['q' . $delta] = 'desc';
    }
    $sort = (string) $request->query->get('sort', 'score');
    if (!isset($sort_options[$sort])) {
      $sort = 'score';
    }
    $order = $request->query->get('order') === 'asc' ? 'asc' : ($request->query->get('order') === 'desc' ? 'desc' : $sort_options[$sort]);

    $rows = $no_track ? [] : $this->buildRows($questions, $limit);
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
      '#show_filter' => $show_filter,
      '#all_label' => $assigned === NULL ? $this->t('All tracks') : $this->t('All my tracks'),
      '#own_tracks' => $assigned !== NULL,
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
   * One row per reviewed session in the given tracks, with average scores.
   *
   * @param string[] $questions
   *   The evaluation's rating questions.
   * @param array $tracks
   *   Session category term IDs to limit to; empty for every track.
   */
  protected function buildRows(array $questions, array $tracks): array {
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
      if ($tracks && (!$category || !in_array((string) $category->id(), array_map('strval', $tracks), TRUE))) {
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
