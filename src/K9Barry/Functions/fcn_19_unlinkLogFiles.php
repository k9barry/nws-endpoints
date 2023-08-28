<?php

/**
 * fcn_19_unlinkLogFiles
 *
 * @param  mixed $strLogFolder
 * @param  mixed $logger
 * @return void
 */
function fcn_19_unlinkLogFiles($strLogFolder, $logger)
{
    $files = glob("$strLogFolder/*.log");
    $now = time();
    foreach ($files as $file) {
        if (is_file($file)) {
            if ($now - filemtime($file) >= 60 * 60 * 24 * 2) { // 2 days
                unlink($file);
                $logger->info("Log " . $file . " removed from " . $strLogFolder . "");
            }
        }
    }
}
