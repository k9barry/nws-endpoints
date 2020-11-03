<?php

namespace K9Barry\Webhook;

class RenameIfExisFileName
{
    /**
     * RenameIfExisFileName
     *
     * @param  mixed $filename
     * @return void
     */
    public function RenameIfExisFileName($filename)
    {
        if (!file_exists($filename)) {
            return $filename;
        }
        $arrayParts = pathinfo($filename);
        $strFolder = get_value($arrayParts, 'dirname');
        $strNewFileName = $strFolder . '//' . file_newname($strFolder, get_value($arrayParts, 'basename'));
        $strNewFileName = str_replace('//', '/', $strNewFileName);
        return $strNewFileName;
    }
}
