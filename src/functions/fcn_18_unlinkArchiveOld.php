<?php

use Psr\Log\LoggerInterface;

/**
 * fcn_18_unlinkArchiveOld
 * 
 * Cleans up old files from the archive directory to manage disk space.
 * Removes processed incident files older than 1 hour (3600 seconds) to prevent
 * the archive folder from accumulating too many files over time.
 *
 * @param string $path Path to the archive directory to clean up
 * @param LoggerInterface $logger Logger instance for archive cleanup operations
 * @return void
 */
function fcn_18_unlinkArchiveOld(string $path, LoggerInterface $logger): void // $strBackupFolder
{
    if ($handle = opendir($path)) {
        while (false !== ($file = readdir($handle))) {
            $filelastmodified = filemtime($path . "/" . $file);
            //3 days * 24 hours in a day * 3600 seconds per hour
            if ((time() - $filelastmodified) > 3600) {
                if ($file != "." && $file != "..") {
                    unlink($path . "/" . $file);
                    $logger->info("File " . $file . " removed from " . $path);
                }
            }
        }
        closedir($handle);
    }
}
