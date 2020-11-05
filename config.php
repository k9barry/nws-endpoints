<?php

/**
 * Rename this file to config.php
 *
 * Config file defining file locations and storage of API keys.
 * This file is required for this script to function, you will
 * need to change the values below to match your keys.
 *
 */

// Data folder for files created by scripts
$strDataFolder = "./data"; // No ending '/'

// Monitor folder this script is watching for file additions
$strInFolder = "Y:"; // mapped drive to NWS xml file folder

// Filename extensions to be monitored for in the monitor folder
$arrayInputFileExtensions = array('xml');

// Set output folder for output files
$strOutFolder = "" . $strDataFolder . "/Output";

// Move the original files to this folder after processing
$strBackupFolder = "" . $strDataFolder . "/Archive";

// Log folder to store log files into
$strLogFolder = "" . $strDataFolder . "/Logs";

// Set time to wait before checking input folder for new files (in seconds)
$sleep = 3;

// Set the database location and name
$db = "" . $strDataFolder . "/Database.sqlite";

// Set the database table name to store incidents into
$db_table = 'incidents';

//Set the location for the csv file of active whitelist of incidents to send
$CfsCsvFilePath = "./src/nwscfstype.csv";

// Table name for active incidnets to be sent nwscfstypecsv
$CfsTableName = preg_replace("/[^a-zA-Z0-9\s]/", '', $CfsCsvFilePath);

/**
 * sendSNPP
 *
 */
$snppSend = false;
$snppUrl = "snpp.active911.com";
$snppPort = "444";
$snppPage = "PP2335-jZ4H4fh8Lk6zB2jf";

/**
 * sendPushover
 *
 */
$pushoverSend = false;
$pushoverUrl = "https://api.pushover.net/1/messages.json";
$pushoverToken = "aodca6wpud384boctokw3o3spnui41";
$pushoverUser = "gbvpr4istsx6g3yynx87eqah5nc2n1";

/**
 * sendWebhook
 *
 */
$webhookSend = true;
$webhookUrl = "https://outlook.office.com/webhook/e1059a7d-c09b-4b4d-afd7-edba8f04d9dd@deefb18f-6e39-486d-b9b0-4931e10710e2/IncomingWebhook/55a2bb25274e47e5af3d83792d432196/5102ae40-48ec-43ab-8729-1f0301969875";

/**
 * Google API key
 *
 */
$googleApiKey = "AIzaSyDHpvNjdl_GGR_Qrz-Dtw8v5D1-Otiqrms";
