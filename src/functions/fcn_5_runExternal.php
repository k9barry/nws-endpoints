<?php

use Psr\Log\LoggerInterface;

/**
 * fcn_5_runExternal
 * 
 * Processes a single New World CAD file through the complete workflow.
 * Handles file movement, database operations, and triggers notification sending.
 * This is the main processing function that coordinates all incident handling operations.
 *
 * @param string $strInFile Full path to the input file to process
 * @param string $strInRootFolder Root input folder for relative path calculations
 * @param string $strOutFolder Output folder for temporary file processing
 * @param string $strBackupFolder Archive folder for storing processed files
 * @param LoggerInterface $logger Logger instance for processing operations
 * @param string $db Database file path for incident storage
 * @param string $db_table Database table name for incident records
 * @param array $config Configuration array containing notification and timing settings
 * @return void
 * @throws \RuntimeException When file operations fail
 * @throws \InvalidArgumentException When input parameters are invalid
 */
function fcn_5_runExternal(
    string $strInFile, 
    string $strInRootFolder, 
    string $strOutFolder, 
    string $strBackupFolder, 
    LoggerInterface $logger, 
    string $db, 
    string $db_table,
    array $config
): void
{
    // Input validation
    if (!is_file($strInFile) || !is_readable($strInFile)) {
        throw new \InvalidArgumentException("Input file does not exist or is not readable: {$strInFile}");
    }
    
    if (empty($strInRootFolder) || empty($strOutFolder) || empty($strBackupFolder)) {
        throw new \InvalidArgumentException("Folder paths cannot be empty");
    }
    
    if (empty($db) || empty($db_table)) {
        throw new \InvalidArgumentException("Database path and table name cannot be empty");
    }

    try {
        $logger->info("{$strInFile} => {$strOutFolder}");

        // Use DIRECTORY_SEPARATOR for cross-platform compatibility
        $normalizedRootFolder = rtrim($strInRootFolder, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
        $strRelativeFileName = ltrim(str_replace($normalizedRootFolder, '', $strInFile), DIRECTORY_SEPARATOR);
        $logger->info("RelativeFileName={$strRelativeFileName}");

        // Prepare output file path with proper path separators
        $strOutFile = rtrim($strOutFolder, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $strRelativeFileName;
        $strOutFile = str_replace('/', DIRECTORY_SEPARATOR, $strOutFile);
        
        // Ensure output directory exists
        $outputDir = dirname($strOutFile);
        if (!fcn_6_recursiveMkdir($outputDir, $logger)) {
            throw new \RuntimeException("Failed to create output directory: {$outputDir}");
        }
        
        $strOutFile = fcn_7_renameIfExists($strOutFile);

        // Database operations with proper exception handling
        $db_conn = fcn_10_openConnection($db, $logger);
        
        try {
            if (!fcn_11_tableExists($db_conn, $db_table, $logger)) {
                fcn_12_createIncidentsTable($db_conn, $db_table, $logger);
            }
            fcn_13_recordReceived($db_conn, $db_table, $strInFile, $logger, $config);
        } finally {
            // Ensure database connection is always closed
            $db_conn = null;
            $logger->info("Database connection closed");
        }

        // Clean up old archives
        fcn_18_unlinkArchiveOld($strBackupFolder, $logger);

        // Prepare backup file path with proper path separators
        $strBackupFile = rtrim($strBackupFolder, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $strRelativeFileName;
        $strBackupFile = str_replace('/', DIRECTORY_SEPARATOR, $strBackupFile);
        
        // Ensure backup directory exists
        $backupDir = dirname($strBackupFile);
        if (!fcn_6_recursiveMkdir($backupDir, $logger)) {
            throw new \RuntimeException("Failed to create backup directory: {$backupDir}");
        }
        
        $strBackupFile = fcn_7_renameIfExists($strBackupFile);

        // Move file to archive with proper error handling
        if (!@rename($strInFile, $strBackupFile)) {
            $error = error_get_last();
            $errorMessage = $error ? $error['message'] : 'Unknown error';
            $logger->error("Failed to move file {$strInFile} => {$strBackupFile}: {$errorMessage}");
            throw new \RuntimeException("Unable to move file to archive folder: {$errorMessage}");
        }
        
        $logger->info("File successfully moved: {$strInFile} => {$strBackupFile}");

    } catch (\Throwable $e) {
        $logger->error("Exception in fcn_5_runExternal: " . $e->getMessage());
        throw $e; // Re-throw to allow proper error handling upstream
    }
}
