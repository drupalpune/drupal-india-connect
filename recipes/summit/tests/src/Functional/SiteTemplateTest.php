<?php

declare(strict_types=1);

use Drupal\Core\Config\FileStorage;
use Drupal\canvas\Entity\ComponentTreeEntityInterface;
use Drupal\canvas\JsonSchemaDefinitionsStreamwrapper;
use Drupal\FunctionalTests\Core\Recipe\RecipeTestTrait;
use Drupal\Tests\BrowserTestBase;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Symfony\Component\Process\Process;

/**
 * Tests this site template to ensure it can be applied without errors.
 *
 * All deprecation notices triggered by the recipe's dependencies will be
 * displayed. To suppress them, add the
 * \PHPUnit\Framework\Attributes\IgnoreDeprecations attribute to this class.
 */
#[RunTestsInSeparateProcesses]
class SiteTemplateTest extends BrowserTestBase {

  use RecipeTestTrait {
    runDrupalCommand as baseRunDrupalCommand;
  }

  /**
   * Disable strict schema checking to avoid failures from contrib.
   *
   * @var bool Whether to enable strict config schema checking
   *
   * ConfigSchemaChecker validates every config object loaded during recipe
   * application, including config owned by the 140+ contrib modules the recipe
   * installs. Schema gaps in those modules are not the recipe's responsibility
   * to fix, and they don't represent production breakage (ConfigSchemaChecker
   * only runs in test environments). Recipe-owned config schema is validated
   * separately in RequirementsTest::testRecipeConfigStructure().
   */
  protected $strictConfigSchema = FALSE;

  /**
   * Minimum container memory required for the full recipe apply smoke test.
   */
  private const MINIMUM_RECIPE_TEST_MEMORY_KB = 4 * 1024 * 1024;

  /**
   * {@inheritdoc}
   *
   * Increases the default timeout to accommodate this large recipe, which
   * installs 140+ modules and may take longer on slower local environments.
   */
  protected function runDrupalCommand(array $arguments, int $timeout = 500): Process {
    return $this->baseRunDrupalCommand($arguments, max($timeout, 1800));
  }

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Returns the absolute path of the recipe this test is for.
   *
   * @return string
   *   The absolute path of the recipe.
   */
  protected static function getRecipePath(): string {
    return dirname(__FILE__, 4);
  }

  /**
   * Returns the best available pre-existing site baseline recipe path.
   *
   * @return string
   *   The absolute path of the recipe.
   */
  protected function getPreExistingSiteRecipePath(): string {
    $drupal_cms_base = dirname($this->getDrupalRoot()) . '/recipes/drupal_cms_site_template_base';
    if (is_dir($drupal_cms_base)) {
      return $drupal_cms_base;
    }

    return $this->getDrupalRoot() . '/core/recipes/standard';
  }

  /**
   * Skips the test when the container cannot safely run a full recipe install.
   */
  protected function skipIfInsufficientMemory(): void {
    $memory_total = $this->getContainerMemoryTotal();
    if ($memory_total !== NULL && $memory_total < self::MINIMUM_RECIPE_TEST_MEMORY_KB) {
      $this->markTestSkipped(sprintf(
        'The full site template recipe apply test requires at least 4 GB of container memory; this environment has %.1f GB.',
        $memory_total / 1024 / 1024,
      ));
    }
  }

  /**
   * Gets total memory visible to the test container, in kilobytes.
   *
   * @return int|null
   *   The total memory, or NULL if it cannot be determined.
   */
  protected function getContainerMemoryTotal(): ?int {
    $memory_info = @file('/proc/meminfo');
    if ($memory_info === FALSE) {
      return NULL;
    }

    foreach ($memory_info as $line) {
      if (preg_match('/^MemTotal:\s+(\d+)\s+kB$/', $line, $matches)) {
        return (int) $matches[1];
      }
    }

    return NULL;
  }

  /**
   * Tests that the site template can be applied to a bare Drupal installation.
   */
  public function testSiteTemplate(): void {
    $this->markTestSkipped('This site template is intended to be applied on top of Drupal CMS; use testSiteTemplateOnDrupalCms() for the supported install path.');
    $this->applyRecipe(self::getRecipePath());
    $this->assertCanvasComponentsAreIncluded();
    $this->assertEventPlatformStructure();
  }

  /**
   * Tests that the site template can be applied on top of a pre-existing site.
   *
   * This simulates the intended production use case: a user installs Drupal CMS
   * (which applies base recipes including standard content types and roles),
   * then selects this site template in the installer. The recipe's strict:
   * false mode must handle pre-existing configuration without errors.
   *
   * This test is in the 'full-install' group because it installs 140+ contrib
   * modules and takes ~30 minutes. It is excluded from the standard CI job and
   * runs only in the scheduled nightly pipeline.
   */
  #[Group('full-install')]
  public function testSiteTemplateOnDrupalCms(): void {
    $this->skipIfInsufficientMemory();
    $this->applyRecipe($this->getPreExistingSiteRecipePath());
    $this->applyRecipe(self::getRecipePath());
    $this->assertCanvasComponentsAreIncluded();
    $this->assertEventPlatformStructure();
  }

  /**
   * Asserts that the event platform structure is correctly set up.
   *
   * Checks content types, taxonomy vocabularies, user roles, content moderation
   * workflows, and key site settings that are the core value of this template.
   */
  protected function assertEventPlatformStructure(): void {
    $node_type_storage = \Drupal::entityTypeManager()->getStorage('node_type');
    foreach (['article', 'bof', 'featured_speaker', 'job_listing', 'page', 'session', 'sponsor'] as $type) {
      $this->assertNotNull(
        $node_type_storage->load($type),
        "Content type '$type' must exist after applying the recipe."
      );
    }

    $vocab_storage = \Drupal::entityTypeManager()->getStorage('taxonomy_vocabulary');
    foreach (['event', 'room', 'session_audience', 'session_category', 'sponsor_level', 'tags', 'time_slot'] as $vocab) {
      $this->assertNotNull(
        $vocab_storage->load($vocab),
        "Taxonomy vocabulary '$vocab' must exist after applying the recipe."
      );
    }

    $role_storage = \Drupal::entityTypeManager()->getStorage('user_role');
    foreach (['administrator', 'content_editor', 'session_moderator', 'speaker'] as $role) {
      $this->assertNotNull(
        $role_storage->load($role),
        "User role '$role' must exist after applying the recipe."
      );
    }

    $workflow_storage = \Drupal::entityTypeManager()->getStorage('workflow');
    foreach (['basic_editorial', 'event_planning', 'session_acceptance'] as $workflow) {
      $this->assertNotNull(
        $workflow_storage->load($workflow),
        "Content moderation workflow '$workflow' must exist after applying the recipe."
      );
    }

    $theme_config = \Drupal::config('system.theme');
    $this->assertSame('event_horizon', $theme_config->get('default'), 'The default theme must be event_horizon.');
    $this->assertSame('gin', $theme_config->get('admin'), 'The admin theme must be gin.');

    $site_config = \Drupal::config('system.site');
    $this->assertSame('/home', $site_config->get('page.front'), 'The front page must be set to /home.');
    $this->assertSame('/user/login', $site_config->get('page.403'), 'The 403 page must redirect to /user/login.');

    // Verify anonymous users have permissions explicitly granted by the recipe.
    $anonymous_role = $role_storage->load('anonymous');
    $this->assertNotNull($anonymous_role);
    $this->assertTrue($anonymous_role->hasPermission('access content'), 'Anonymous users must be able to access content.');
    $this->assertTrue($anonymous_role->hasPermission('view media'), 'Anonymous users must be able to view media.');
  }

  /**
   * Checks that the site template includes all Canvas components that it uses.
   */
  protected function assertCanvasComponentsAreIncluded(): void {
    // Examine all entities that implement
    // \Drupal\canvas\Entity\ComponentTreeEntityInterface.
    $entity_types = array_filter(
      \Drupal::entityTypeManager()->getDefinitions(),
      fn ($entity_type): bool => $entity_type->entityClassImplements(ComponentTreeEntityInterface::class),
    );

    $included_components = (new FileStorage(self::getRecipePath() . '/config'))
      ->listAll('canvas.component.');

    foreach ($entity_types as $entity_type) {
      $entities = \Drupal::entityTypeManager()
        ->getStorage($entity_type->id())
        ->loadMultiple();

      foreach ($entities as $entity) {
        $this->assertInstanceOf(ComponentTreeEntityInterface::class, $entity);
        /** @var \Drupal\canvas\Plugin\Field\FieldType\ComponentTreeItem $item */
        foreach ($entity->getComponentTree() as $item) {
          $component = $item->getComponent()?->getConfigDependencyName();
          if ($component) {
            $this->assertContains($component, $included_components, 'The site template should include this component in its configuration.');
          }
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function rebuildAll(): void {
    // The rebuild won't succeed without the `json-schema-definitions` stream
    // wrapper. This would normally happen automatically whenever a module is
    // installed, but in this case, all of that has taken place in a separate
    // process, so we need to refresh *this* process manually.
    // @see canvas_module_preinstall()
    \Drupal::service('stream_wrapper_manager')->registerWrapper(
      'json-schema-definitions',
      JsonSchemaDefinitionsStreamwrapper::class,
      JsonSchemaDefinitionsStreamwrapper::getType(),
    );
    parent::rebuildAll();
  }

}
