<?php

/**
 * Composer autoload and php 
 *
 */
ini_set('memory_limit', '-1');
ini_set("max_execution_time", 0);
set_time_limit(0);
require_once "./vendor/autoload.php";
require_once "./config.php";

/**
 * Load Monolog and Initialize
 *
 */
use Monolog\Level;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;

$logger = new Logger("nws-endpoint");
$stream_handler = new StreamHandler("php://stdout", Level::Info);
$logger->pushHandler($stream_handler);
$logger->info('nws-endpoint logger is now ready'); // You can now use your logger

/**
 * Require config file
 * */

if (!file_exists('./config.php')) {
    rename("./config.php.dist", "./config.php");
    $logger->warning("config.php file was not located so I created one for you.  Please be sure to change the settings");
    die("config.php file was not located so I created one for you.  Please be sure to change the settings");
}

/**
 * Require functions
 */
foreach (glob('./functions/*.php') as $filename) {
    include_once $filename;
    $logger->info("include_once $filename \r\n");
}

/**
 * Setup file structure
 */

if (!is_dir($strInFolder)) {
    mkdir($strInFolder);
    $logger->info("Watch folder not found - So I created one for you at $strInFolder");
} else {
    $logger->info("Watch folder found at $strInFolder");
}

if (!is_dir($strOutFolder)) {
    mkdir($strOutFolder);
    $logger->info("Output folder not found - So I created one for you at $strOutFolder");
} else {
    $logger->info("Output folder found at $strOutFolder");
}
if (!is_dir($strBackupFolder)) {
    mkdir($strBackupFolder);
    $logger->info("Backup folder not found - So I created one for you at $strBackupFolder");
} else {
    $logger->info("Backup folder found at $strBackupFolder");
}

/**
 * Cleanup the watch folder.....
 */
fcn_1_unlinkInputOld($strInFolder, $TimeAdjust, $logger);

/**
 * Launch this thing.....
 */
while (true) {
    fcn_2_monitorFolder($strInFolder, $arrayInputFileExtensions, $strOutFolder, $strBackupFolder, $logger);
    //$logger->info("=====================================================");
    sleep($sleep); //Waiting for XXX seconds
}