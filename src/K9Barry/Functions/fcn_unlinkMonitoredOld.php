<?php

/**
 * fcn_unlinkMonitoredOld
 *
 * @param  mixed $path
 * @param  mixed $TimeAdjust
 * @return void
 */
function fcn_unlinkMonitoredOld($path, $TimeAdjust) // $strInFolder, $TimeAdjust

{
    global $logger;
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
