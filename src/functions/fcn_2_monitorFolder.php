<?php

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
 * @param \Monolog\Logger $logger Logger instance for monitoring operations
 * @param string $db Database file path for incident storage
 * @param string $db_table Database table name for incident records
 * @return void
 */
function fcn_2_monitorFolder(string $strInFolder, array $extensions, string $strOutFolder, string $strBackupFolder, \Monolog\Logger $logger, string $db, string $db_table): void
{
    // Parameter validation
    if (empty($strInFolder)) {
        $logger->error("Input folder path cannot be empty");
        return;
    }
    
    if (empty($extensions)) {
        $logger->error("Extensions array cannot be empty");
        return;
    }
    
    if (empty($strOutFolder)) {
        $logger->error("Output folder path cannot be empty");
        return;
    }
    
    if (empty($strBackupFolder)) {
        $logger->error("Backup folder path cannot be empty");
        return;
    }
    
    if (empty($db)) {
        $logger->error("Database path cannot be empty");
        return;
    }
    
    if (empty($db_table)) {
        $logger->error("Database table name cannot be empty");
        return;
    }

    // Ensure output folder exists with proper permissions
    if (!is_dir($strOutFolder)) {
        try {
            fcn_6_recursiveMkdir($strOutFolder, $logger, 0755, true);
            if (!is_dir($strOutFolder)) {
                $logger->error("Failed to create output folder: $strOutFolder");
                return;
            }
        } catch (Exception $e) {
            $logger->error("Exception creating output folder $strOutFolder: " . $e->getMessage());
            return;
        }
    }

    // Ensure backup folder exists with proper permissions
    if (!is_dir($strBackupFolder)) {
        try {
            fcn_6_recursiveMkdir($strBackupFolder, $logger, 0755, true);
            if (!is_dir($strBackupFolder)) {
                $logger->error("Failed to create backup folder: $strBackupFolder");
                return;
            }
        } catch (Exception $e) {
            $logger->error("Exception creating backup folder $strBackupFolder: " . $e->getMessage());
            return;
        }
    }

    // Validate input folder exists
    if (!is_dir($strInFolder)) {
        $logger->error("Input folder does not exist: $strInFolder");
        return;
    }

    // Generate case-insensitive pattern for file extensions
    $strFilterFormat = fcn_3_globCaseInsensitivePattern($extensions);

    // Proceed with recursive file monitoring
    fcn_4_recursiveGlob($strInFolder, $strFilterFormat, $strInFolder, $strOutFolder, $strBackupFolder, $logger, $db, $db_table);
}
