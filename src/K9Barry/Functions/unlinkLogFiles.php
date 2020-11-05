<?php

/**
 * unlinkLogFiles
 *
 * @param  mixed $strLogFolder
 * @return void
 */
function unlinkLogFiles($strLogFolder)
{
    global $logger;
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
