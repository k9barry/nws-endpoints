<?php

/**
 * fcn_unlinkMonitoredOld
 *
 * @param  mixed $path
 * @param  mixed $TimeAdjust
 * @param  mixed $logger
 * @return void
 */
function fcn_unlinkMonitoredOld($path, $TimeAdjust, $logger) // $strInFolder, $TimeAdjust

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
}
