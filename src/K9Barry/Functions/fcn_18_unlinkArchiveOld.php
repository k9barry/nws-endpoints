<?php

/**
 * fcn_18_unlinkArchiveOld
 *
 * @param  mixed $path
 * @return void
 */
function fcn_18_unlinkArchiveOld($path) // $strBackupFolder
{
    global $logger;
    if ($handle = opendir($path)) {
        while (false !== ($file = readdir($handle))) {
            $filelastmodified = filemtime($path . "/" . $file);
            //3 days * 24 hours in a day * 3600 seconds per hour
            if ((time() - $filelastmodified) > 1 * 3600) {
                unlink($path . "/" . $file);
                $logger->info("File " . $file . " removed from " . $path . "");
            }
        }
        closedir($handle);
    }
}