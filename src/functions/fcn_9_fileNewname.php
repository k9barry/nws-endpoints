<?php

/**
 * fcn_9_fileNewname
 * 
 * Generates a unique filename by appending a counter if the original filename already exists.
 * If 'example.txt' exists, it will return 'example_0.txt', 'example_1.txt', etc.
 *
 * @param string $path The directory path where the file will be placed
 * @param string $filename The original filename to make unique
 * @return string The unique filename that doesn't already exist
 */
function fcn_9_fileNewname(string $path, string $filename): string
{
    $pos = strrpos($filename, '.');
    if ($pos !== false) {
        $name = substr($filename, 0, $pos);
        $ext = substr($filename, $pos);
    } else {
        $name = $filename;
        $ext = '';
    }
    
    $path = rtrim($path, DIRECTORY_SEPARATOR);
    $newpath = $path . DIRECTORY_SEPARATOR . $filename;
    $newname = $filename;
    $counter = 0;
    
    while (file_exists($newpath)) {
        $newname = $name . '_' . $counter . $ext;
        $newpath = $path . DIRECTORY_SEPARATOR . $newname;
        $counter++;
    }
    
    return $newname;
}
