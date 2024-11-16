<?php

/**
 * fcn_unlinkInputOld
 *
 * @param  string $path
 * @param  int $TimeAdjust
 * @param  mixed $logger
 * @return void
 */
function fcn_1_unlinkInputOld($path, $TimeAdjust, $logger)
{
    if ($handle = opendir($path)) {
        while (false !== ($file = readdir($handle))) {
            $filelastmodified = filemtime($path . "/" . $file);
            if ((time() - $filelastmodified) > $TimeAdjust) {
                unlink($path . "/" . $file);
                $logger->info("File " . $file . " removed from " . $path . "");
            }
        }
        closedir($handle);
    }
    $logger->info("All files older than " . $TimeAdjust . " removed from Input folder " . $path . "");
}
