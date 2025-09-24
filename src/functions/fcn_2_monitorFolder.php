<?php

declare(strict_types=1);

/**
 * fcn_2_monitorFolder
 * 
 * Monitors a folder for new files with specified extensions and processes them.
 * This is the main entry point for file monitoring that initiates the recursive
 * file discovery and processing workflow for New World CAD XML files.
 *
 * @param string $strInFolder Input folder path to monitor for new files
 * @param array $extensions Array of file extensions to monitor (e.g., ['xml'])
 * @param string $strOutFolder Output folder for processed files
 * @param string $strBackupFolder Archive folder for storing processed files
 * @param mixed $logger Logger instance for monitoring operations
 * @param string $db Database file path for incident storage
 * @param string $db_table Database table name for incident records
 * @return void
 */
function fcn_2_monitorFolder(string $strInFolder, array $extensions, string $strOutFolder, string $strBackupFolder, mixed $logger, string $db, string $db_table): void
{
    // Improved variable name for clarity while maintaining business logic
    $globPatternFilter = fcn_3_globCaseInsensitivePattern($extensions);

    fcn_4_recursiveGlob($strInFolder, $globPatternFilter, $strInFolder, $strOutFolder, $strBackupFolder, $logger, $db, $db_table);
}
