<?php

use Psr\Log\LoggerInterface;

/**
 * Recursively searches for files matching specified patterns in directories and subdirectories.
 * Processes each found file through the New World CAD workflow for incident notification.
 * This function handles the core file discovery logic for monitoring folders.
 *
 * @param string $dir Current directory being searched
 * @param string $ext File extension pattern to match (from fcn_3_globCaseInsensitivePattern)
 * @param string $strInRootFolder Root input folder path for relative path calculations
 * @param string $strOutFolder Output folder for processed files
 * @param string $strBackupFolder Archive folder for storing processed files
 * @param LoggerInterface $logger Logger instance for file processing operations
 * @param string $db Database file path for incident storage
 * @param string $db_table Database table name for incident records
 * @return void
 * @throws \InvalidArgumentException When directory parameters are invalid
 */
function fcn_4_recursiveGlob(
    string $dir,
    string $ext,
    string $strInRootFolder,
    string $strOutFolder,
    string $strBackupFolder,
    LoggerInterface $logger,
    string $db,
    string $db_table
): void {
    // Input validation
    if (!is_dir($dir)) {
        throw new \InvalidArgumentException("Directory does not exist: {$dir}");
    }
    
    if (empty($ext)) {
        throw new \InvalidArgumentException("File extension pattern cannot be empty");
    }

    // Normalize directory path to remove trailing slash for consistency
    $dir = rtrim($dir, DIRECTORY_SEPARATOR);

    // Pattern match for files using proper directory separator
    $globFiles = glob($dir . DIRECTORY_SEPARATOR . $ext, GLOB_NOSORT | GLOB_BRACE);
    // Pattern match for subdirectories
    $globDirs = glob($dir . DIRECTORY_SEPARATOR . "*", GLOB_ONLYDIR | GLOB_NOSORT);

    // Recursively process subdirectories first (depth-first)
    if (!empty($globDirs) && is_array($globDirs)) {
        foreach ($globDirs as $_dir) {
            try {
                fcn_4_recursiveGlob(
                    $_dir,
                    $ext,
                    $strInRootFolder,
                    $strOutFolder,
                    $strBackupFolder,
                    $logger,
                    $db,
                    $db_table
                );
            } catch (\Throwable $e) {
                $logger->error("Error processing subdirectory {$_dir}: " . $e->getMessage());
                // Continue processing other directories
            }
        }
    }

    // Process files
    if (!empty($globFiles) && is_array($globFiles)) {
        foreach ($globFiles as $file) {
            if (!is_file($file) || !is_readable($file)) {
                $logger->warning("Skipping unreadable file: {$file}");
                continue;
            }

            $nFileSize = filesize($file);
            if ($nFileSize === false || $nFileSize <= 0) {
                $logger->warning("Skipping empty or invalid file: {$file}");
                continue;
            }

            $logger->info("Found file: {$file} (size: {$nFileSize} bytes)");

            // Process file with comprehensive error handling
            try {
                fcn_5_runExternal(
                    $file,
                    $strInRootFolder,
                    $strOutFolder,
                    $strBackupFolder,
                    $logger,
                    $db,
                    $db_table
                );
            } catch (\Throwable $e) {
                $logger->error("Error processing file {$file}: " . $e->getMessage());
                // Continue processing other files
            }
        }
    }
}
