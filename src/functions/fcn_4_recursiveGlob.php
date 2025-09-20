<?php

/**
 * fcn_4_recursiveGlob
 * 
 * Recursively searches for files matching specified patterns in directories and subdirectories.
 * Processes each found file through the New World CAD workflow for incident notification.
 * This function handles the core file discovery logic for monitoring folders.
 *
 * @param string $dir Current directory being searched
 * @param string $ext File extension pattern to match (from fcn_3_globCaseInsensitivePattern)
 * @param string $strInRootFolder Root input folder path for relative path calculations
 * @param string $strOutFolder Output folder for processed files
 * @param string $strBackupFolder Archive folder for storing processed files
 * @param mixed $logger Logger instance for file processing operations
 * @param string $db Database file path for incident storage
 * @param string $db_table Database table name for incident records
 * @return void
 */
function fcn_4_recursiveGlob(string $dir, string $ext, string $strInRootFolder, string $strOutFolder, string $strBackupFolder, mixed $logger, string $db, string $db_table): void
{
    $globFiles = glob("$dir/$ext");
    $globDirs = glob("$dir/*", GLOB_ONLYDIR);
    if (is_array($globDirs)) {
        foreach ($globDirs as $_dir) {
            fcn_4_recursiveGlob($_dir, $ext, $strInRootFolder, $strOutFolder, $strBackupFolder, $logger, $db, $db_table);
        }
    }
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
            $logger->info("Found file: " . $file);

            fcn_5_runExternal($file, $strInRootFolder, $strOutFolder, $strBackupFolder, $logger, $db, $db_table);
        }
    }
}
