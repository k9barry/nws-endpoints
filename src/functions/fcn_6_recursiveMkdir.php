<?php

/**
 * fcn_6_recursiveMkdir
 *
 * @param  mixed $dest
 * @param  mixed $logger
 * @param  int $permissions
 * @param  bool $create
 * @return true|void
 */
function fcn_6_recursiveMkdir($dest, $logger, $permissions = 0755, $create = true)
{
    if (!is_dir(dirname($dest))) {
        fcn_6_recursiveMkdir(dirname($dest),$logger, $permissions, $create);
    } elseif (!is_dir($dest)) {
        $logger->info("Make directory " . $dest . " with permission level " . $permissions . "");
        mkdir($dest, $permissions, $create);
    } else {
        return true;
    }
}