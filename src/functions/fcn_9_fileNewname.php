<?php

/**
 * fcn_9_fileNewname
 * 
 * Generates a unique filename by appending a counter if the original filename already exists.
 * If 'example.txt' exists, it will return 'example_0.txt', 'example_1.txt', etc.
 *
 * @param mixed $path The directory path where the file will be placed
 * @param mixed $filename The original filename to make unique
 * @return mixed|string The unique filename that doesn't already exist
 */
function fcn_9_fileNewname(mixed $path, mixed $filename): mixed
{
    if ($pos = strrpos($filename, '.')) {
        $name = substr($filename, 0, $pos);
        $ext = substr($filename, $pos);
    } else {
        $name = $filename;
        $ext = '';
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
