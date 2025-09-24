<?php

declare(strict_types=1);

use Psr\Log\LoggerInterface;

/**
 * fcn_2_monitorFolder
 * 
 * Monitors a folder for new files with specified extensions and processes them.
 * This is the main entry point for file monitoring that initiates the recursive
 * file discovery and processing workflow for New World CAD XML files.
 *
 * @param string $strInFolder Input folder path to monitor for new files
 * @param array<string> $extensions Array of file extensions to monitor (e.g., ['xml'])
 * @param string $strOutFolder Output folder for processed files
 * @param string $strBackupFolder Archive folder for storing processed files
 * @param LoggerInterface $logger Logger instance for monitoring operations
 * @param string $db Database file path for incident storage
 * @param string $db_table Database table name for incident records
 * @return void
 * @throws \InvalidArgumentException When input parameters are invalid
 */
function fcn_2_monitorFolder(
    string $strInFolder, 
    array $extensions, 
    string $strOutFolder, 
    string $strBackupFolder, 
    LoggerInterface $logger, 
    string $db, 
    string $db_table
): void {
    // Parameter validation
    if (empty($strInFolder)) {
        throw new \InvalidArgumentException("Input folder path cannot be empty");
    }
    
    if (empty($extensions)) {
        throw new \InvalidArgumentException("Extensions array cannot be empty");
    }
    
    if (empty($strOutFolder)) {
        throw new \InvalidArgumentException("Output folder path cannot be empty");
    }
    
    if (empty($strBackupFolder)) {
        throw new \InvalidArgumentException("Backup folder path cannot be empty");
    }
    
    if (empty($db)) {
        throw new \InvalidArgumentException("Database path cannot be empty");
    }
    
    if (empty($db_table)) {
        throw new \InvalidArgumentException("Database table name cannot be empty");
    }

    // Ensure output folder exists with proper permissions
    if (!is_dir($strOutFolder)) {
        try {
            if (!fcn_6_recursiveMkdir($strOutFolder, $logger, 0755, true)) {
                throw new \RuntimeException("Failed to create output folder: {$strOutFolder}");
            }
        } catch (\Exception $e) {
            $logger->error("Exception creating output folder {$strOutFolder}: " . $e->getMessage());
            throw $e;
        }
    }

    // Ensure backup folder exists with proper permissions
    if (!is_dir($strBackupFolder)) {
        try {
            if (!fcn_6_recursiveMkdir($strBackupFolder, $logger, 0755, true)) {
                throw new \RuntimeException("Failed to create backup folder: {$strBackupFolder}");
            }
        } catch (\Exception $e) {
            $logger->error("Exception creating backup folder {$strBackupFolder}: " . $e->getMessage());
            throw $e;
        }
    }

    // Validate input folder exists
    if (!is_dir($strInFolder)) {
        throw new \InvalidArgumentException("Input folder does not exist: {$strInFolder}");
    }

    // Generate case-insensitive pattern for file extensions
    $strFilterFormat = fcn_3_globCaseInsensitivePattern($extensions);

    // Proceed with recursive file monitoring
    fcn_4_recursiveGlob($strInFolder, $strFilterFormat, $strInFolder, $strOutFolder, $strBackupFolder, $logger, $db, $db_table);
}
