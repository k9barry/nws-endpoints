<?php

use Psr\Log\LoggerInterface;

/**
 * fcn_6_recursiveMkdir
 *
 * Recursively creates directories, like 'mkdir -p'.
 * Ensures all parent directories exist before creating the target directory.
 * Used to set up folder structure for processing New World CAD files.
 *
 * @param string $dest Destination directory path to create
 * @param LoggerInterface|null $logger Logger instance for directory creation operations
 * @param int $permissions Directory permissions (default: 0755)
 * @param bool $recursive Whether to recursively create directories (default: true)
 * @return bool Returns true if the directory exists or was created, false otherwise
 */
function fcn_6_recursiveMkdir(string $dest, ?LoggerInterface $logger = null, int $permissions = 0755, bool $recursive = true): bool
{
    // Normalize path and check if already exists
    $dest = rtrim($dest, DIRECTORY_SEPARATOR);

    if (is_dir($dest)) {
        return true;
    }

    // If parent doesn't exist, try to create it first
    $parent = dirname($dest);
    if (!is_dir($parent) && $recursive) {
        if (!fcn_6_recursiveMkdir($parent, $logger, $permissions, $recursive)) {
            $logger?->error("Failed to create parent directory: {$parent}");
            return false;
        }
    }

    // Directory doesn't exist, attempt to create it
    $logger?->info("Creating directory: {$dest} with permissions " . decoct($permissions));
    
    if (@mkdir($dest, $permissions, false)) {
        // Set permissions explicitly in case umask affected mkdir
        chmod($dest, $permissions);
        return true;
    } else {
        $logger?->error("Failed to create directory: {$dest}");
        return false;
    }
}
