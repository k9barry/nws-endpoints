<?php

// Require function.php file
$functionfile = "./src/functions.php";
require_once $functionfile;

// Check if config.php exists and if so include it
$configfile = "./config.php";
require_once $configfile;

//Composer autoload
require_once "./vendor/autoload.php";

ini_set('memory_limit', '-1');
ini_set("max_execution_time", 0);
set_time_limit(0);

//Monolog
use Monolog\Handler\RotatingFileHandler;
use Monolog\Logger;
use Monolog\Processor\IntrospectionProcessor;

$logger = new Logger('webhook_logger'); // Create the logger
$logger->pushHandler(new RotatingFileHandler($strLogFolder . "/webhook.log", Logger::DEBUG)); // Now add some handlers
$logger->pushProcessor(new IntrospectionProcessor());
$logger->info('Webhook logger is now ready'); // You can now use your logger

//////////////////////////////////////////////////////////////

if (!is_dir($strOutFolder)) {
    mkdir($strOutFolder);
}
if (!is_dir($strBackupFolder)) {
    mkdir($strBackupFolder);
}
while (true) {
    MonitorFolderAndCallEXE($strInFolder, $arrayInputFileExtensions, $strOutFolder, $strBackupFolder);
    $logger->info('[Start/End] =====================================================');
    sleep($sleep); //Waiting for XXX seconds
}

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
    if (!is_dir(dirname($dest))) {
        recursive_mkdir(dirname($dest), $permissions, $create);
    } elseif (!is_dir($dest)) {
        $logger->info("[recursive_mkdir] $dest");
        mkdir($dest, $permissions, $create);
    } else {
        return true;
    }
}

/**
 * RunExternalEXE
 *
 * @param  mixed $strInFile
 * @param  mixed $strInRootFolder
 * @param  mixed $strOutFolder
 * @param  mixed $strBackupFolder
 * @return void
 */
function RunExternalEXE($strInFile, $strInRootFolder, $strOutFolder, $strBackupFolder)
{
    global $logger;
    $logger->info("[RunExternalEXE] $strInFile => $strOutFolder");
    $arrayParts = pathinfo($strInFile);
    $strRelativeFileName = str_replace($strInRootFolder, '', $strInFile);
    $logger->info("[RunExternalEXE] RelativeFileName=$strRelativeFileName");
    $strOutFile = $strOutFolder . '/' . $strRelativeFileName;
    $strOutFile = str_replace('//', '/', $strOutFile);
    recursive_mkdir(dirname($strOutFile));
    $strOutFile = RenameIfExisFileName($strOutFile);

    /*************************************************************************************************************************************
     * Add my custom functions to be preformed when new file is added to monitor folder
    /*************************************************************************************************************************************/
    global $strLogFolder, $db, $db_table, $CfsCsvFilePath, $CfsTableName;
    $db_conn = new PDO("sqlite:$db");
    $logger->info("[RunExternalEXE] Connection opened to database $db");
    if (!tableExists($db_conn, $db_table)) {
        createIncidentsTable($db_conn, $db_table); // Create incidents table in DB if it does not exist
    }
    if (!tableExists($db_conn, $CfsTableName)) {
        csvToSqlite($db_conn, $CfsCsvFilePath, $options = array()); // Create CFS table in DB if it does not exist
    }
    recordReceived($db_conn, $db_table, $strInFile);
    closeConnection($db_conn);
    unlinkArchiveOld($strBackupFolder);
    unlinkLogFiles($strLogFolder);
    /*************************************************************************************************************************************/
    /*************************************************************************************************************************************/

    //Move original file to Archive folder
    $strBackupFile = $strBackupFolder . '/' . $strRelativeFileName;
    $strBackupFile = str_replace('//', '/', $strBackupFile);
    recursive_mkdir(dirname($strBackupFile));
    $strBackupFile = RenameIfExisFileName($strBackupFile);
    rename($strInFile, $strBackupFile);
    $logger->info("[RunExternalEXE] MoveFile: $strInFile => $strBackupFile");
}

/**
 * RenameIfExisFileName
 *
 * @param  mixed $filename
 * @return void
 */
function RenameIfExisFileName($filename)
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

/**
 * file_newname
 *
 * @param  mixed $path
 * @param  mixed $filename
 * @return void
 */
function file_newname($path, $filename)
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

/**
 * get_value
 *
 * @param  mixed $array
 * @param  mixed $index
 * @param  mixed $default
 * @return void
 */
function get_value($array, $index, $default = '')
{
    if (!isset($array[$index])) {
        return $default;
    }
    $value = trim($array[$index]);
    if (strlen($value) <= 0) {
        return $default;
    }
    return $value;
}

/**
 * MonitorFolderAndCallEXE
 *
 * @param  mixed $strInFolder
 * @param  mixed $extensions
 * @param  mixed $strOutFolder
 * @param  mixed $strBackupFolder
 * @return void
 */
function MonitorFolderAndCallEXE($strInFolder, $extensions, $strOutFolder, $strBackupFolder)
{
    $strFilterFormat = globCaseInsensitivePattern($extensions);
    recursiveGlob($strInFolder, $strFilterFormat, $strInFolder, $strOutFolder, $strBackupFolder);
}

/**
 * globCaseInsensitivePattern
 * * create case insensitive patterns for glob or simular functions
 * ['jpg','gif'] as input
 * converted to: *.{[Jj][Pp][Gg],[Gg][Ii][Ff]}
 *
 * @param  mixed $arr_extensions
 * @return void
 */
function globCaseInsensitivePattern($arr_extensions)
{
    $opbouw = '';
    $comma = '';
    foreach ($arr_extensions as $ext) {
        $opbouw .= $comma;
        $comma = ',';
        foreach (str_split($ext) as $letter) {
            $opbouw .= '[' . strtoupper($letter) . strtolower($letter) . ']';
        }
    }
    if (count($arr_extensions) == 1 && strlen($opbouw) > 0) {
        return '*.' . $opbouw;
    }
    if ($opbouw) {
        return '*.{' . $opbouw . '}';
    }
    // if no pattern given show all
    return '*';
}

/**
 * recursiveGlob
 *
 * @param  mixed $dir
 * @param  mixed $ext
 * @param  mixed $strInRootFolder
 * @param  mixed $strOutFolder
 * @param  mixed $strBackupFolder
 * @return void
 */
function recursiveGlob($dir, $ext, $strInRootFolder, $strOutFolder, $strBackupFolder)
{
    global $logger;
    $logger->info("[recursiveGlob] Looking for: " . $dir . "/" . $ext . "");
    $globFiles = glob("$dir/$ext");
    $globDirs = glob("$dir/*", GLOB_ONLYDIR);
    if (is_array($globDirs)) {
        foreach ($globDirs as $_dir) {
            recursiveGlob($_dir, $ext, $strInRootFolder, $strOutFolder, $strBackupFolder);
        }
    }
    $logger->info("[recursiveGlob] Found " . count($globFiles) . " files in [" . $dir . "]...");
    if (is_array($globFiles)) {
        foreach ($globFiles as $file) {
            if (!is_file($file)) {
                continue;
            }
            $nFileSize = filesize($file);
            if ($nFileSize <= 0) {
                continue;
            }
            $logger->info("[recursiveGlob] Found file: " . $file . "");
            RunExternalEXE($file, $strInRootFolder, $strOutFolder, $strBackupFolder);
        }
    }
}
