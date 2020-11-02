<?php

namespace K9Barry\Webhook;

class MonitorFolderAndCallEXE {
    /**
     * MonitorFolderAndCallEXE
     *
     * @param  mixed $strInFolder
     * @param  mixed $extensions
     * @param  mixed $strOutFolder
     * @param  mixed $strBackupFolder
     * @return void
     */
    public function MonitorFolderAndCallEXE($strInFolder, $extensions, $strOutFolder, $strBackupFolder)
    {
        $strFilterFormat = globCaseInsensitivePattern($extensions);
        recursiveGlob($strInFolder, $strFilterFormat, $strInFolder, $strOutFolder, $strBackupFolder);
    }
}
