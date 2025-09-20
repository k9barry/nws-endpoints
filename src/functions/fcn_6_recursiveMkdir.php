<?php

/**
 * fcn_6_recursiveMkdir
 * 
 * Creates directories recursively, similar to 'mkdir -p' command.
 * Ensures that all parent directories exist before creating the target directory.
 * Used to set up folder structure for processing New World CAD files.
 *
 * @param mixed $dest Destination directory path to create
 * @param mixed $logger Logger instance for directory creation operations
 * @param int $permissions Directory permissions (default: 0755)
 * @param bool $create Whether to actually create the directory (default: true)
 * @return true|void Returns true if directory already exists, void if created
 */
function fcn_6_recursiveMkdir(mixed $dest, mixed $logger, int $permissions = 0755, bool $create = true)
{
    if (!is_dir(dirname($dest))) {
        fcn_6_recursiveMkdir(dirname($dest), $logger, $permissions, $create);
    } elseif (!is_dir($dest)) {
        $logger->info("Make directory " . $dest . " with permission level " . $permissions);
        mkdir($dest, $permissions, $create);
    } else {
        return true;
    }
}
