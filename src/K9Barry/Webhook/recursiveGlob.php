<?php

namespace K9Barry\Webhook;

class recursiveGlob
{
    /**
     * recursiveGlob
     *
     * @param  mixed $dir
     * @param  mixed $ext
     * @param  mixed $strInRootFolder
     * @param  mixed $strOutFolder
     * @param  mixed $strBackupFolder
     * @return void
     */
    public function recursiveGlob($dir, $ext, $strInRootFolder, $strOutFolder, $strBackupFolder)
    {
        $logger->info("Looking for: " . $dir . "/" . $ext . "");
        $globFiles = glob("$dir/$ext");
        $globDirs = glob("$dir/*", GLOB_ONLYDIR);
        if (is_array($globDirs)) {
            foreach ($globDirs as $_dir) {
                recursiveGlob($_dir, $ext, $strInRootFolder, $strOutFolder, $strBackupFolder);
            }
        }
        $logger->info("Found " . count($globFiles) . " files in [" . $dir . "]...");
        if (is_array($globFiles)) {
            foreach ($globFiles as $file) {
                if (!is_file($file)) {
                    continue;
                }
                $nFileSize = filesize($file);
                if ($nFileSize <= 0) {
                    continue;
                }
                $logger->info("Found file: " . $file . "");
                RunExternalEXE($file, $strInRootFolder, $strOutFolder, $strBackupFolder);
            }
        }
    }
}
