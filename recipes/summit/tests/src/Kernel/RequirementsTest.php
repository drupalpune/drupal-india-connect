<?php

declare(strict_types=1);

use Composer\InstalledVersions;
use Drupal\Component\Serialization\Json;
use Drupal\Component\Serialization\Yaml;
use Drupal\Core\Config\FileStorage;
use Drupal\Core\Recipe\Recipe;
use Drupal\KernelTests\KernelTestBase;

/**
 * Tests that the site template conforms to basic requirements.
 *
 * You can customize this test, but generally shouldn't unless you have a
 * specific reason to do so. The requirements for site templates are documented
 * in GET-STARTED.md.
 */
final class RequirementsTest extends KernelTestBase {

  /**
   * Tests that the site template conforms to basic requirements.
   */
  public function testSiteTemplateRequirements(): void {
    $path = realpath(__DIR__ . '/../../..');

    // Ensure the recipe's type is correct.
    $this->assertSame('Site', Recipe::createFromDirectory($path)->type, 'The recipe type must be "Site".');

    // Read `composer.json` and ensure it's syntactically valid.
    $file = $path . '/composer.json';
    $this->assertFileExists($file);
    $data = file_get_contents($file);
    $data = Json::decode($data);
    $this->assertIsArray($data);

    // To avoid confusion about what packages are part of Drupal CMS, site
    // templates should never be prefixed with "drupal_cms_" or "drupal-cms-".
    // The only exception is the starter kit.
    [, $name] = explode('/', $data['name'], 2);
    if ($name !== 'drupal_cms_site_template_base') {
      $this->assertStringStartsNotWith('drupal_cms_', $name, 'Site templates should not use the drupal_cms_ prefix in their name.');
      $this->assertStringStartsNotWith('drupal-cms-', $name, 'Site templates should not use the drupal-cms- prefix in their name.');
    }

    $install_profiles = InstalledVersions::getInstalledPackagesByType('drupal-profile');
    foreach ($data['require'] ?? [] as $name => $constraint) {
      // Use a basic heuristic to detect pinned dependencies, which are never
      // allowed in a site template.
      $this->assertDoesNotMatchRegularExpression('/^v?[0-9]+\./i', $constraint, "The site template cannot pin a specific version of $name.");
      // Site templates aren't allowed to depend on install profiles.
      $this->assertNotContains($name, $install_profiles, "The site template cannot depend on $name because it is an install profile.");
      // Site templates may not patch dependencies in any way, which includes
      // depending on the cweagans/composer-patches plugin.
      $this->assertNotSame('cweagans/composer-patches', $name, "The site template cannot depend on $name because site templates must not patch dependencies.");
    }
    $this->assertArrayNotHasKey('patches', $data['extra'] ?? [], 'Site templates cannot supply or specify patches for dependencies.');

    // The site template must identify itself as a recipe.
    $this->assertSame(Recipe::COMPOSER_PROJECT_TYPE, $data['type'], sprintf('The project type must be "%s".', Recipe::COMPOSER_PROJECT_TYPE));

    // Although not a hard technical requirement, it's an extremely good idea
    // for a site template to specify a license.
    $this->assertNotEmpty($data['license'], 'The site template should declare a license.');

    // Ensure that all config shipped by this site template doesn't have the
    // `uuid` or `_core` keys.
    // Exception: canvas.folder.* configs must include a `uuid` key because the
    // Canvas Folder entity uses UUID as its entity ID. Without it,
    // ConfigEntityStorage generates a new UUID at install time that doesn't
    // match the filename, causing a LogicException. See Canvas module issue.
    $storage = new FileStorage($path . '/config');
    foreach ($storage->listAll() as $name) {
      $data = $storage->read($name);
      if (!str_starts_with($name, 'canvas.folder.')) {
        $this->assertArrayNotHasKey('uuid', $data, "The $name config should not include a `uuid` key.");
      }
      $this->assertArrayNotHasKey('_core', $data, "The $name config should not include a `_core` key.");
    }
  }

  /**
   * Tests that exported file entities include their source assets.
   */
  public function testExportedFileEntitiesHaveAssets(): void {
    $path = realpath(__DIR__ . '/../../..');
    $files = glob($path . '/content/file/*.yml') ?: [];

    foreach ($files as $file) {
      $data = Yaml::decode(file_get_contents($file));
      $filename = $data['default']['filename'][0]['value'] ?? NULL;
      $expected_size = $data['default']['filesize'][0]['value'] ?? NULL;

      $this->assertIsString($filename, "$file should include a filename.");
      $asset = $path . '/content/file/' . $filename;
      $this->assertFileExists($asset, "$file should include its source asset.");

      if ($expected_size !== NULL) {
        $this->assertSame((int) $expected_size, filesize($asset), "$asset should match the exported file entity size.");
      }
    }
  }

  /**
   * Tests the recipe's views config files for known-problematic patterns.
   *
   * This is a fast, static check (no module installation required) that catches
   * schema issues in config files the recipe owns. It complements the full
   * recipe apply test in SiteTemplateTest but runs in milliseconds.
   */
  public function testRecipeConfigStructure(): void {
    $path = realpath(__DIR__ . '/../../..');
    $storage = new FileStorage($path . '/config');

    foreach ($storage->listAll() as $config_name) {
      if (!str_starts_with($config_name, 'views.view.')) {
        continue;
      }
      $data = $storage->read($config_name);

      foreach ($data['display'] ?? [] as $display_id => $display) {
        $options = $display['display_options'] ?? [];

        // views.sort.timestamp has no schema definition; the correct plugin
        // for date-based field sorting with granularity support is
        // views.sort.date.
        foreach ($options['sorts'] ?? [] as $sort_id => $sort) {
          $this->assertNotSame(
            'timestamp',
            $sort['plugin_id'] ?? '',
            "$config_name display '$display_id' sort '$sort_id': plugin_id 'timestamp' has no schema definition; use 'date' instead.",
          );
        }

        // views_bulk_operations removed force_selection_info in 4.x; using it
        // causes a schema error. The replacement keys are
        // show_multipage_selection_box and show_select_all.
        foreach ($options['fields'] ?? [] as $field_id => $field) {
          $this->assertArrayNotHasKey(
            'force_selection_info',
            $field,
            "$config_name display '$display_id' field '$field_id': 'force_selection_info' is a deprecated VBO key with no schema definition.",
          );
        }
      }
    }
  }

  /**
   * Tests the header CTA block config uses only schema-supported settings.
   */
  public function testHeaderCtaBlockConfigUsesOnlySchemaSupportedSettings(): void {
    $path = realpath(__DIR__ . '/../../..');
    $storage = new FileStorage($path . '/config');
    $data = $storage->read('block.block.event_horizon_eventplatformheadercta');

    $this->assertIsArray($data);
    $this->assertSame('event_platform_header_cta', $data['plugin'] ?? NULL);
    $this->assertSame(
      [
        'id',
        'label',
        'label_display',
        'provider',
        'default_state_link',
        'date_state_link',
        'sessions_open_state_link',
        'sessions_closed_state_link',
        'scheduled_state_link',
        'underway_state_link',
        'published_state_link',
      ],
      array_keys($data['settings'] ?? []),
      'The Event Platform Header CTA block config should only contain schema-supported settings.',
    );
  }

}
