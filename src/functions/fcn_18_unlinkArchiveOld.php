<?php

/**
 * fcn_18_unlinkArchiveOld
 *
 * @param  string  $path
 * @param  mixed $logger
 * @return void
 */
function fcn_18_unlinkArchiveOld($path, $logger) // $strBackupFolder
{
    if ($handle = opendir($path)) {
        while (false !== ($file = readdir($handle))) {
            $filelastmodified = filemtime($path . "/" . $file);
            //3 days * 24 hours in a day * 3600 seconds per hour
            if ((time() - $filelastmodified) > 1 * 3600) {
                if ($file != "." && $file != "..") {
                unlink($path . "/" . $file);
                $logger->info("File " . $file . " removed from " . $path . "");
                }
            }
        }
        closedir($handle);
    }
}
