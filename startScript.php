<?php

/**
 * Composer autoload
 *
 */

require_once "./vendor/autoload.php";

foreach (glob('./src/K9Barry/Webhook/*.php') as $filename)
{
    include_once $filename;
}

/**
 * Load K9Barry namespace
 *
 */

use K9Barry\Webhook;

/**
 * Load Monolog and Initialize
 *
 */

use Monolog\Handler\RotatingFileHandler;
use Monolog\Logger;
use Monolog\Processor\IntrospectionProcessor;

$logger = new Logger('webhook_logger'); // Create the logger
$logger->pushHandler(new RotatingFileHandler("./data/Logs/webhook.log", Logger::DEBUG)); // Add sRotatingFileHandler
$logger->pushProcessor(new IntrospectionProcessor());
$logger->info('Webhook logger is now ready'); // You can now use your logger

/**
 * startScript
 *
 */

// Require config functions
$configfile = "./config.php";
if (file_exists($configfile)) {
    require_once $configfile;
} else {
    die("Unable to locate config.php file");
}

ini_set('memory_limit', '-1');
ini_set("max_execution_time", 0);
set_time_limit(0);

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
