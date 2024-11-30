<?php

/**
 * fcn_9_fileNewname
 *
 * @param mixed $path
 * @param mixed $filename
 * @return mixed|string $newname
 */
function fcn_9_fileNewname(mixed $path, mixed $filename): mixed
{
    if ($pos = strrpos($filename, '.')) {
        $name = substr($filename, 0, $pos);
        $ext = substr($filename, $pos);
    } else {
        $name = $filename;
    }
    $newpath = $path . '/' . $filename;
    $newname = $filename;
    $counter = 0;
    while (file_exists($newpath)) {
        $newname = $name . '_' . $counter . $ext;
        $newpath = $path . '/' . $newname;
        $counter++;
    }
    return $newname;
}
