<?php

/**
 * recursive_mkdir
 *
 * @param  mixed $dest
 * @param  mixed $permissions
 * @param  mixed $create
 * @return void
 */
function recursive_mkdir($dest, $permissions = 0755, $create = true)
{
    global $logger;
    if (!is_dir(dirname($dest))) {
        recursive_mkdir(dirname($dest), $permissions, $create);
    } elseif (!is_dir($dest)) {
        $logger->info("Make directory " . $dest . " with permission level " . $permissions . "");
        mkdir($dest, $permissions, $create);
    } else {
        return true;
    }
}
