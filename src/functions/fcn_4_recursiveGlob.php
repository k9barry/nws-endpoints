<?php

/**
 * fcn_4_recursiveGlob
 *
 * @param  string $dir
 * @param  string $ext
 * @param  string $strInRootFolder
 * @param  string $strOutFolder
 * @param  string $strBackupFolder
 * @param  mixed $logger
 * @return void
 */
function fcn_4_recursiveGlob($dir, $ext, $strInRootFolder, $strOutFolder, $strBackupFolder, $logger)
{
    //$logger->info("Looking for: " . $dir . "/" . $ext . "");
    $globFiles = glob("$dir/$ext");
    $globDirs = glob("$dir/*", GLOB_ONLYDIR);
    if (is_array($globDirs)) {
        foreach ($globDirs as $_dir) {
            fcn_4_recursiveGlob($_dir, $ext, $strInRootFolder, $strOutFolder, $strBackupFolder, $logger);
        }
    }
    //$logger->info("Found " . count($globFiles) . " files in [" . $dir . "]...");
    if (is_array($globFiles)) {
        foreach ($globFiles as $file) {
            if (!is_file($file)) {
                continue;
            }
            $nFileSize = filesize($file);
            if ($nFileSize <= 0) {
                continue;
            }
            $logger->info("=====================================================");
            $logger->info("Found file: " . $file . "");

            fcn_5_runExternal($file, $strInRootFolder, $strOutFolder, $strBackupFolder, $logger);
        }
    }
}