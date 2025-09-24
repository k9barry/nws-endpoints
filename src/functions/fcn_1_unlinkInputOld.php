<?php
declare(strict_types=1);

use Psr\Log\LoggerInterface;

/**
 * fcn_1_unlinkInputOld
 *
 * Cleans up old files from the input directory by removing files older than specified time.
 * Prevents the watch folder from accumulating too many old files.
 *
 * @param string $path Path to the directory to clean up
 * @param int $TimeAdjust Maximum age in seconds - files older than this will be deleted
 * @param mixed $logger Logger instance for logging cleanup operations
 * @return void
 */
function fcn_1_unlinkInputOld(
    string $path,
    int $TimeAdjust,
    LoggerInterface $logger
): void {
  if (!is_dir($path)) {
    if ($logger) {
      $logger->error("Directory not found: {$path}");
    }
    return;
  }

  $removedCount = 0;
  if ($handle = opendir($path)) {
    while (false !== ($file = readdir($handle))) {
      if ($file === '.' || $file === '..') {
        continue;
      }
      $filePath = $path . DIRECTORY_SEPARATOR . $file;
      if (!is_file($filePath)) {
        continue;
      }
      $filelastmodified = @filemtime($filePath);
      if ($filelastmodified === false) {
        if ($logger) {
          $logger->warning("Could not get modification time for: {$filePath}");
        }
        continue;
      }
      if ((time() - $filelastmodified) > $TimeAdjust) {
        if (@unlink($filePath)) {
          $removedCount++;
          if ($logger) {
            $logger->info("File {$file} removed from {$path}");
          }
        } else {
          if ($logger) {
            $logger->error("Failed to remove file: {$filePath}");
          }
        }
      }
    }
    closedir($handle);
  } else {
    if ($logger) {
      $logger->error("Failed to open directory: {$path}");
    }
    return;
  }
  if ($logger) {
    $logger->info("{$removedCount} files older than {$TimeAdjust} seconds removed from input folder {$path}");
  }
}
