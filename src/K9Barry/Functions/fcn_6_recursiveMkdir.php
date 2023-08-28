<?php

/**
 * fcn_6_recursiveMkdir
 *
 * @param  mixed $dest
 * @param  mixed $permissions
 * @param  mixed $create
 * @param  mixed $logger
 * @return void
 */
function fcn_6_recursiveMkdir($dest, $permissions = 0755, $create = true, $logger)
{
    if (!is_dir(dirname($dest))) {
        fcn_6_recursiveMkdir(dirname($dest), $permissions, $create, $logger);
    } elseif (!is_dir($dest)) {
        $logger->info("Make directory " . $dest . " with permission level " . $permissions . "");
        mkdir($dest, $permissions, $create);
    } else {
        return true;
    }
}
