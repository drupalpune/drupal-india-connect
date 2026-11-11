<?php

/**
 * @file
 * Shared by build-panel-pages.php and update-program-day2-panel.php.
 *
 * Returns a closure: given a file name in docs/panel-revamp/img/, it gives
 * back the inputs for a Canvas image prop ({target_id: <media id>}), so a
 * panelist photo lives in the repo and each environment gets its own media.
 *
 * An existing image media with that name is reused (re-runs, and photos an
 * editor already uploaded under the same name); otherwise the file is copied
 * to public://panel/ and a media entity created, owned by uid 1.
 */

use Drupal\Core\File\FileExists;
use Drupal\file\Entity\File;
use Drupal\media\Entity\Media;

return function (string $filename): array {
  $source = DRUPAL_ROOT . '/../docs/panel-revamp/img/' . $filename;
  if (!is_file($source)) {
    throw new \RuntimeException("No photo at docs/panel-revamp/img/$filename");
  }

  $ids = \Drupal::entityQuery('media')
    ->accessCheck(FALSE)
    ->condition('bundle', 'image')
    ->condition('name', $filename)
    ->sort('mid')
    ->range(0, 1)
    ->execute();
  if ($ids) {
    return ['target_id' => (string) reset($ids)];
  }

  $fs = \Drupal::service('file_system');
  $dir = 'public://panel';
  $fs->prepareDirectory($dir, \Drupal\Core\File\FileSystemInterface::CREATE_DIRECTORY);
  $uri = $fs->copy($source, "$dir/$filename", FileExists::Rename);
  $file = File::create(['uri' => $uri, 'uid' => 1, 'status' => 1]);
  $file->save();

  $name = preg_replace('/[-_]+/', ' ', pathinfo($filename, PATHINFO_FILENAME));
  $media = Media::create([
    'bundle' => 'image',
    'name' => $filename,
    'uid' => 1,
    'status' => 1,
    'field_media_image' => ['target_id' => $file->id(), 'alt' => $name],
  ]);
  $media->save();
  echo "  Created media {$media->id()} from $filename\n";
  return ['target_id' => (string) $media->id()];
};
