<?php

use K9Bary\Webhook;

/**
 * startScript
 *
 */

 // Require config functions
$configfile = "./config.php";
if (exists ($configfile)) {
    require_once $configfile;
} else {
    die ("Unable to locate config.php file");
}

// Require webhook functions
$functionfile = "./src/Webhook/*.php";
require_once $functionfile;

//Composer autoload
require_once "./vendor/autoload.php";

ini_set('memory_limit', '-1');
ini_set("max_execution_time", 0);
set_time_limit(0);

/**
 * Load Monolog and Initialize
 *
 */
use Monolog\Handler\RotatingFileHandler;
use Monolog\Logger;
use Monolog\Processor\IntrospectionProcessor;

$logger = new Logger('webhook_logger'); // Create the logger
$logger->pushHandler(new RotatingFileHandler($strLogFolder . "/webhook.log", Logger::DEBUG)); // Now add some handlers
$logger->pushProcessor(new IntrospectionProcessor());
$logger->info('Webhook logger is now ready'); // You can now use your logger

/**
 * Start monitoring
 *
 */

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
