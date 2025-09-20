<?php

/**
 * fcn_1_unlinkInputOld
 * 
 * Cleans up old files from the input directory by removing files older than specified time.
 * This prevents the watch folder from accumulating too many old files that might cause
 * processing issues or consume excessive disk space.
 *
 * @param string $path Path to the directory to clean up
 * @param int $TimeAdjust Maximum age in seconds - files older than this will be deleted
 * @param mixed $logger Logger instance for logging cleanup operations
 * @return void
 */
function fcn_1_unlinkInputOld(string $path, int $TimeAdjust, mixed $logger): void
{
    if ($handle = opendir($path)) {
        while (false !== ($file = readdir($handle))) {
            $filelastmodified = filemtime($path . "/" . $file);
            if ((time() - $filelastmodified) > $TimeAdjust) {
                unlink($path . "/" . $file);
                $logger->info("File " . $file . " removed from " . $path);
            }
        }
        closedir($handle);
    }
    $logger->info("All files older than " . $TimeAdjust . " removed from Input folder " . $path);
}
