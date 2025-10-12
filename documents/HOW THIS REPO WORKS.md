# HOW THIS REPO WORKS - Complete Step-by-Step Guide

This document provides a comprehensive walkthrough of how the NWS Endpoints repository works from beginning to end, explaining the complete workflow from XML file arrival to notification delivery.

## Table of Contents
1. [Overview](#overview)
2. [System Architecture](#system-architecture)
3. [Application Startup](#application-startup)
4. [Main Processing Loop](#main-processing-loop)
5. [File Discovery Process](#file-discovery-process)
6. [File Processing Workflow](#file-processing-workflow)
7. [Database Operations](#database-operations)
8. [Incident Processing Logic](#incident-processing-logic)
9. [Notification System](#notification-system)
10. [Cleanup and Maintenance](#cleanup-and-maintenance)
11. [Data Flow Diagram](#data-flow-diagram)
12. [Error Handling Strategy](#error-handling-strategy)
13. [Function Call Chain](#function-call-chain)

## Overview

The NWS Endpoints application is a PHP-based incident notification system that monitors a watch folder for Tyler Tech New World CAD XML files, parses incident data, stores it in SQLite, and sends notifications via ntfy.sh and Pushover services.

### Key Components
- **Watch Folder**: Monitors for incoming XML files from New World CAD
- **SQLite Database**: Stores active incident records
- **Archive Folder**: Preserves processed XML files
- **Notification Services**: ntfy.sh (hierarchical topics) and Pushover (mobile push)
- **Logging System**: Monolog for comprehensive operation tracking

### Primary Purpose
Enable real-time notifications to first responders and dispatch personnel when:
- New incidents are created
- Existing incidents are updated (new units, location changes, alarm escalations)
- Critical incident information changes

## System Architecture

```
┌─────────────────────────────────────────────────────────────────┐
│                         NWS ENDPOINTS                            │
│                                                                   │
│  ┌─────────────┐   ┌──────────────┐   ┌────────────────────┐  │
│  │  CAD System │──▶│ Watch Folder │──▶│  Main Application  │  │
│  └─────────────┘   └──────────────┘   └────────────────────┘  │
│                                               │                  │
│                          ┌────────────────────┼────────────┐    │
│                          ▼                    ▼            ▼    │
│                  ┌──────────────┐   ┌──────────────┐  ┌──────┐│
│                  │   Database   │   │   Archive    │  │ Logs ││
│                  │   (SQLite)   │   │    Folder    │  │      ││
│                  └──────────────┘   └──────────────┘  └──────┘│
│                          │                                       │
│                          ▼                                       │
│                  ┌──────────────┐                               │
│                  │ Notification │                               │
│                  │   Processor  │                               │
│                  └──────────────┘                               │
│                          │                                       │
│                ┌─────────┴──────────┐                          │
│                ▼                    ▼                          │
│         ┌───────────┐        ┌───────────┐                    │
│         │  ntfy.sh  │        │ Pushover  │                    │
│         └───────────┘        └───────────┘                    │
└─────────────────────────────────────────────────────────────────┘
```

### Directory Structure
```
src/
├── run                         # Main entry point
├── config.php                  # Configuration settings
├── composer.json              # PHP dependencies
├── vendor/                    # Composer packages (Monolog, etc.)
├── functions/                 # Core function library (20 functions)
└── data/                      # Runtime data directories
    ├── watchfolder/          # Input: XML files from CAD
    ├── output/               # Temp: Processing workspace
    ├── archive/              # Archive: Processed files
    └── db/                   # Database: SQLite files
```

## Application Startup

The application starts with the `src/run` file. Here's the complete startup sequence:

### Step 1: Environment Configuration (lines 7-16)
```php
$strDataFolder = "./data";
$strInFolder = "./data/watchfolder";
$strOutFolder = "./data/output";
$strBackupFolder = "./data/archive";
$arrayInputFileExtensions = array('xml');
$sleep = 3;  // Seconds between folder scans
$db = "./data/db/db.sqlite";
$db_table = 'incidents';
$TimeAdjust = 900;  // 15 minutes in seconds
```

**Purpose**: Define all file paths, monitoring settings, and time thresholds.

### Step 2: PHP Configuration (lines 21-25)
```php
ini_set('memory_limit', '-1');      // Unlimited memory
ini_set("max_execution_time", 0);   // No time limit
set_time_limit(0);                  // No script timeout
require_once "./vendor/autoload.php";  // Composer autoload
require_once "./config.php";         // User configuration
```

**Purpose**: Configure PHP for long-running daemon process and load dependencies.

### Step 3: Logger Initialization (lines 31-41)
```php
use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Monolog\Processor\IntrospectionProcessor;

$logger = new Logger("nws-endpoint");
$logger->pushProcessor(new IntrospectionProcessor());
$stream_handler = new StreamHandler("php://stdout", Level::Info);
$logger->pushHandler($stream_handler);
$logger->info('nws-endpoint logger is now ready');
```

**Purpose**: Setup Monolog logging to stdout with introspection (file/line tracking).

**Reference**: Logging is used throughout by passing `$logger` to all functions.

### Step 4: Configuration File Check (lines 47-51)
```php
if (!file_exists('./config.php')) {
    rename("./config.php.dist", "./config.php");
    $logger->warning("config.php created from template");
    die("Please configure settings in config.php");
}
```

**Purpose**: Ensure configuration file exists, create from template if missing.

### Step 5: Build Configuration Array (lines 54-67)
```php
$config = [
    'timeAdjust' => $TimeAdjust,
    'ntfy' => [
        'send' => $ntfySend,
        'url' => $ntfyUrl,
        'authToken' => $ntfyAuthToken
    ],
    'pushover' => [
        'send' => $pushoverSend,
        'url' => $pushoverUrl,
        'token' => $pushoverToken,
        'user' => $pushoverUser
    ]
];
```

**Purpose**: Consolidate all configuration into single array for passing to functions.

### Step 6: Load Function Library (lines 72-75)
```php
foreach (glob('./functions/*.php') as $filename) {
    include_once $filename;
    $logger->info("include_once $filename");
}
```

**Purpose**: Load all 20 function files from `functions/` directory.

**Functions Loaded** (Note: functions 17 and 19 are not present in the codebase):
- fcn_1_unlinkInputOld.php
- fcn_2_monitorFolder.php
- fcn_3_globCaseInsensitivePattern.php
- fcn_4_recursiveGlob.php
- fcn_5_runExternal.php
- fcn_6_recursiveMkdir.php
- fcn_7_renameIfExists.php
- fcn_8_getValue.php
- fcn_9_fileNewname.php
- fcn_10_openConnection.php
- fcn_11_tableExists.php
- fcn_12_createIncidentsTable.php
- fcn_13_recordReceived.php
- fcn_14_deleteRecord.php
- fcn_15_callIdExist.php
- fcn_16_insertRecord.php
- fcn_18_unlinkArchiveOld.php
- fcn_20_DeltaTime.php
- fcn_21_sendMessage.php
- fcn_22_removeOldRecords.php

### Step 7: Create Required Directories (lines 81-99)
```php
if (!is_dir($strInFolder)) {
    mkdir($strInFolder);
    $logger->info("Watch folder created at $strInFolder");
}
// ... similar for output and backup folders
```

**Purpose**: Ensure all required directories exist before monitoring starts.

### Step 8: Initial Cleanup (line 104)
```php
fcn_1_unlinkInputOld($strInFolder, $config['timeAdjust'], $logger);
```

**Purpose**: Remove any old files from watch folder before starting monitoring.

**Reference**: See [fcn_1_unlinkInputOld.md](functions/fcn_1_unlinkInputOld.md)

### Step 9: Start Main Loop (lines 109-115)
```php
while (true) {
    fcn_2_monitorFolder($strInFolder, $arrayInputFileExtensions, 
                        $strOutFolder, $strBackupFolder, 
                        $logger, $db, $db_table, $config);
    sleep($sleep);
}
```

**Purpose**: Begin continuous monitoring loop that runs forever.

## Main Processing Loop

The application enters an infinite loop that continuously monitors the watch folder.

### Loop Cycle
1. **Call fcn_2_monitorFolder()** - Scan watch folder and process any XML files found
2. **Sleep 3 seconds** - Wait before next scan to prevent excessive CPU usage
3. **Repeat** - Loop continues indefinitely

### Loop Characteristics
- **Runs Forever**: Application must be manually stopped (Ctrl+C) or container stopped
- **3-Second Interval**: Configurable via `$sleep` variable
- **No Batch Limits**: Processes all files found in each scan
- **Error Resilient**: Individual file errors don't stop the loop

### Why This Design?
- **Simple and Reliable**: No complex scheduling or event systems
- **Low Latency**: New files detected within 3 seconds
- **Resource Efficient**: Sleep prevents busy-waiting
- **Docker-Friendly**: Single long-running process

## File Discovery Process

When `fcn_2_monitorFolder()` is called, it initiates a recursive file discovery process.

### Phase 1: Monitor Folder Initialization

**Function**: `fcn_2_monitorFolder()` ([documentation](functions/fcn_2_monitorFolder.md))

#### Step 1: Validate Parameters
- Checks all input parameters are not empty
- Validates folders, extensions, database settings
- Throws exceptions for invalid parameters

#### Step 2: Ensure Directories Exist
- Checks if output folder exists, creates if needed
- Checks if backup folder exists, creates if needed
- Uses `fcn_6_recursiveMkdir()` for directory creation
- Validates input folder exists (should already exist)

#### Step 3: Generate Case-Insensitive Pattern
```php
$strFilterFormat = fcn_3_globCaseInsensitivePattern($extensions);
// Input: ['xml']
// Output: '*.{[Xx][Mm][Ll]}'
```

**Function**: `fcn_3_globCaseInsensitivePattern()` ([documentation](functions/fcn_3_globCaseInsensitivePattern.md))

**Purpose**: Create glob pattern that matches files regardless of extension case.

#### Step 4: Initiate Recursive Search
Calls `fcn_4_recursiveGlob()` with all parameters to begin file discovery.

### Phase 2: Recursive File Discovery

**Function**: `fcn_4_recursiveGlob()` ([documentation](functions/fcn_4_recursiveGlob.md))

#### Step 1: Validate Directory and Pattern
- Checks directory exists and is valid
- Validates extension pattern is not empty

#### Step 2: Search Current Directory
```php
$globFiles = glob($dir . DIRECTORY_SEPARATOR . $ext, GLOB_NOSORT | GLOB_BRACE);
$globDirs = glob($dir . DIRECTORY_SEPARATOR . "*", GLOB_ONLYDIR | GLOB_NOSORT);
```

**Purpose**: Find all matching files and subdirectories in current directory.

#### Step 3: Process Subdirectories (Depth-First)
- Iterates through each subdirectory found
- Recursively calls itself for each subdirectory
- Processes nested directories before current directory files
- Continues on individual directory errors

#### Step 4: Process Files in Current Directory
For each file found:
1. Validate file is readable and has size > 0
2. Log file discovery with path and size
3. Call `fcn_5_runExternal()` to process file
4. Continue on individual file errors

### Phase 3: Error Handling During Discovery
- Directory errors are logged but don't stop scanning
- File errors are logged but don't stop processing other files
- Subdirectory errors don't prevent processing parent directory
- Ensures maximum files processed even with partial failures

## File Processing Workflow

When a file is discovered, `fcn_5_runExternal()` orchestrates the complete processing workflow.

**Function**: `fcn_5_runExternal()` ([documentation](functions/fcn_5_runExternal.md))

### Step 1: Validate Input File
- Verify file exists and is readable
- Validate all folder paths provided
- Validate database parameters provided
- Throws exception if validation fails

### Step 2: Calculate Relative Filename
```php
$normalizedRootFolder = rtrim($strInRootFolder, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
$strRelativeFileName = ltrim(str_replace($normalizedRootFolder, '', $strInFile), DIRECTORY_SEPARATOR);
```

**Purpose**: Convert absolute path to relative path for maintaining directory structure.

**Example**:
- Input: `/path/to/watchfolder/subdir/incident.xml`
- Root: `/path/to/watchfolder/`
- Result: `subdir/incident.xml`

### Step 3: Prepare Output Location
- Construct output file path using relative filename
- Create output directory if needed (using `fcn_6_recursiveMkdir()`)
- Generate unique output filename if file exists (using `fcn_7_renameIfExists()`)

**Reference**: 
- [fcn_6_recursiveMkdir.md](functions/fcn_6_recursiveMkdir.md)
- [fcn_7_renameIfExists.md](functions/fcn_7_renameIfExists.md)

### Step 4: Database Connection
```php
$db_conn = fcn_10_openConnection($db, $logger);
```

**Purpose**: Open SQLite connection for incident data operations.

**Reference**: [fcn_10_openConnection.md](functions/fcn_10_openConnection.md)

### Step 5: Ensure Database Schema
```php
if (!fcn_11_tableExists($db_conn, $db_table, $logger)) {
    fcn_12_createIncidentsTable($db_conn, $db_table, $logger);
}
```

**Purpose**: Create incidents table if this is first file processed.

**Reference**: 
- [fcn_11_tableExists.md](functions/fcn_11_tableExists.md)
- [fcn_12_createIncidentsTable.md](functions/fcn_12_createIncidentsTable.md)

### Step 6: Process Incident Record
```php
fcn_13_recordReceived($db_conn, $db_table, $strInFile, $logger, $config);
```

**Purpose**: Parse XML, update database, determine if notifications needed.

**Reference**: [fcn_13_recordReceived.md](functions/fcn_13_recordReceived.md)

This is the **core processing function** - see [Incident Processing Logic](#incident-processing-logic) section.

### Step 7: Close Database (Finally Block)
```php
finally {
    $db_conn = null;
    $logger->info("Database connection closed");
}
```

**Purpose**: Ensure database connection always closed, even if errors occur.

### Step 8: Archive Cleanup
```php
fcn_18_unlinkArchiveOld($strBackupFolder, $logger);
```

**Purpose**: Remove files older than 1 hour from archive folder.

**Reference**: [fcn_18_unlinkArchiveOld.md](functions/fcn_18_unlinkArchiveOld.md)

### Step 9: Prepare Archive Location
- Construct archive file path using relative filename
- Create archive directory if needed
- Generate unique archive filename if file exists

### Step 10: Move File to Archive
```php
if (!@rename($strInFile, $strBackupFile)) {
    $error = error_get_last();
    $logger->error("Failed to move file: {$errorMessage}");
    throw new RuntimeException("Unable to move file to archive");
}
```

**Purpose**: Move processed file from watch folder to archive.

**Why Move?** Prevents reprocessing same file on next scan cycle.

## Database Operations

The application uses SQLite for storing active incident records.

### Database Schema

**Table Name**: `incidents` (configurable via `$db_table`)

**Columns**: 24 fields capturing complete incident details

| Column | Type | Purpose |
|--------|------|---------|
| db_CallId | INTEGER PRIMARY KEY | Unique incident identifier |
| db_CallNumber | INTEGER | CAD-assigned call number |
| db_ClosedFlag | TEXT | Whether incident is closed |
| db_AgencyType | TEXT | Agencies involved (pipe-delimited) |
| db_CreateDateTime | TEXT | Incident creation timestamp |
| db_CallType | TEXT | Type of call |
| db_AlarmLevel | TEXT | Alarm level (1, 2, 3, etc.) |
| db_RadioChannel | TEXT | Radio channel(s) |
| db_NatureOfCall | TEXT | Nature/description |
| db_CommonName | TEXT | Location common name |
| db_FullAddress | TEXT | Street address |
| db_State | TEXT | State code |
| db_NearestCrossStreets | TEXT | Cross streets |
| db_AdditionalInfo | TEXT | Additional location info |
| db_FireOri | TEXT | Fire department ORI |
| db_FireQuadrant | TEXT | Fire quadrant |
| db_PoliceOri | TEXT | Police department ORI |
| db_PoliceBeat | TEXT | Police beat |
| db_LatitudeY | TEXT | GPS latitude |
| db_LongitudeX | TEXT | GPS longitude |
| db_UnitNumber | TEXT | Units assigned (pipe-delimited) |
| db_Incident_Number | TEXT | Official incident numbers |
| db_Incident_Jurisdiction | TEXT | Jurisdictions (pipe-delimited) |
| db_Narrative_Text | TEXT | Incident narrative |

### Key Database Functions

#### Connection Management
- **fcn_10_openConnection()**: Open SQLite connection with proper configuration
  - Sets error mode to exceptions
  - Sets fetch mode to associative arrays
  - Creates database file if doesn't exist

#### Schema Management
- **fcn_11_tableExists()**: Query sqlite_master to check if table exists
- **fcn_12_createIncidentsTable()**: Create table with complete schema using CREATE TABLE IF NOT EXISTS

#### Record Operations
- **fcn_15_callIdExist()**: Check if specific Call ID exists (returns boolean)
- **fcn_16_insertRecord()**: Insert or update incident record (INSERT OR REPLACE)
- **fcn_14_deleteRecord()**: Remove closed incident from database (DELETE WHERE)
- **fcn_22_removeOldRecords()**: Delete incidents older than current - 999 (maintenance)

### Database Lifecycle

```
┌─────────────────────────────────────────────────────────────────┐
│                     Incident Lifecycle                           │
├─────────────────────────────────────────────────────────────────┤
│                                                                   │
│  New Incident (ClosedFlag = false, Call ID not in database)     │
│                          ↓                                        │
│              fcn_16_insertRecord() → INSERT                      │
│                          ↓                                        │
│                   Active in Database                             │
│                          ↓                                        │
│  Updates (ClosedFlag = false, Call ID exists in database)       │
│                          ↓                                        │
│        fcn_16_insertRecord() → INSERT OR REPLACE (UPDATE)       │
│                          ↓                                        │
│                   Updated in Database                            │
│                          ↓                                        │
│  Closed (ClosedFlag = true)                                     │
│                          ↓                                        │
│              fcn_14_deleteRecord() → DELETE                      │
│                          ↓                                        │
│                   Removed from Database                          │
│                          ↓                                        │
│  (Old records with Call ID < current - 999)                     │
│                          ↓                                        │
│           fcn_22_removeOldRecords() → DELETE                     │
│                          ↓                                        │
│                   Database Maintenance Complete                  │
└─────────────────────────────────────────────────────────────────┘
```

## Incident Processing Logic

This is the heart of the system - `fcn_13_recordReceived()` determines what happens with each incident.

**Function**: `fcn_13_recordReceived()` ([documentation](functions/fcn_13_recordReceived.md))

### Step 1: Parse XML Data

#### Load XML
```php
$xml = simplexml_load_file($strInFile) or die("Error: Cannot create object");
```

#### Extract Agency Information
Loops through `AgencyContexts->AgencyContext` elements:
```php
$agencies = "FIRE|POLICE|EMS";  // Example result
```

#### Extract Jurisdiction Information
Loops through `Incidents->Incident` elements:
```php
$jurisdictions = "JURISDICTION_A|JURISDICTION_B";  // Example result
```

#### Extract Unit Information
Loops through `AssignedUnits->Unit` elements:
```php
$units = "E1|L2|M3|A4";  // Example result
```

#### Build Topic List
```php
$arr_Topics_Xml = array_unique(explode('|', $agencies . "|" . $jurisdictions . "|" . $units));
```

**Result**: Array of all potential notification topics from current XML.

### Step 2: Calculate Incident Age
```php
$delta = fcn_20_deltaTime($xml->CreateDateTime);
// Returns seconds since incident creation
```

**Reference**: [fcn_20_DeltaTime.md](functions/fcn_20_DeltaTime.md)

### Step 3: Determine Processing Path

The function takes one of three paths based on incident status:

#### Path A: Closed Incident
```php
if ($xml->ClosedFlag == "true") {
    fcn_14_deleteRecord($db_conn, $db_incident, $xml->CallId, $logger);
    return;  // Exit - no further processing
}
```

**Action**: Remove from database, no notifications.

**Reference**: [fcn_14_deleteRecord.md](functions/fcn_14_deleteRecord.md)

#### Path B: New Incident
```php
elseif (!fcn_15_callIdExist($db_conn, $db_incident, $xml->CallId, $logger)) {
    fcn_16_insertRecord($db_conn, $db_incident, $xml, $logger, $agencies, $jurisdictions, $units);
    fcn_21_sendMessage($db_conn, $db_incident, $xml, $delta, $logger, $topics, 0, $config);
    return;
}
```

**Action**: Insert to database, send notifications to all relevant topics.

**References**: 
- [fcn_15_callIdExist.md](functions/fcn_15_callIdExist.md)
- [fcn_16_insertRecord.md](functions/fcn_16_insertRecord.md)
- [fcn_21_sendMessage.md](functions/fcn_21_sendMessage.md)

#### Path C: Existing Incident (Update)
This is the most complex path - requires change detection.

### Step 4: Change Detection (Existing Incidents)

#### Load Existing Data from Database
```php
$sql = "SELECT * FROM $db_incident WHERE db_CallId = ?";
// ... execute and fetch
extract($ntfyMessage[0]);  // Creates $db_AgencyType, $db_CallType, etc.
```

#### Build Database Topic List
```php
$topics_arrDb_Agency = array_unique(explode("|", $db_AgencyType));
$topics_arrDb_Jurisdiction = array_unique(explode("|", $db_Incident_Jurisdiction));
$topics_arrDb_Unit = array_unique(explode("|", $db_UnitNumber));
$arr_Topics_Db = array_merge($topics_arrDb_Agency, $topics_arrDb_Jurisdiction, $topics_arrDb_Unit);
```

#### Detect New Topics (New Units)
```php
$topics = array_diff($arr_Topics_Xml, $arr_Topics_Db);
// Topics in XML but not in database = newly dispatched units
```

If new topics found:
- Set `$saveToDb = 1` (update database)
- Keep `$resendAll = 0` (notify only new units)

#### Detect Call Type Changes
```php
if ($AgencyContexts_AgencyContext_CallType != $db_CallType) {
    $saveToDb = 1;
    $resendAll = 1;  // Critical change - notify everyone
}
```

#### Detect Location Changes
```php
if ($xml->Location->FullAddress != $db_FullAddress) {
    $saveToDb = 1;
    $resendAll = 1;  // Critical change - notify everyone
}
```

#### Detect Alarm Level Escalation
```php
if ($xml->AlarmLevel > $db_AlarmLevel) {
    $saveToDb = 1;
    $resendAll = 1;  // Critical change - notify everyone
}
```

### Step 5: Process Updates

If `$saveToDb` flag is set (something changed):

#### Check Time Delta
```php
if ($delta < $config['timeAdjust']) {  // Default 900 seconds (15 minutes)
    // Incident is recent - update and notify
    fcn_16_insertRecord(...);
    fcn_21_sendMessage(..., $topics, $resendAll, ...);
} else {
    // Incident is old - update database only, no notifications
    fcn_16_insertRecord(...);
}
```

**Purpose**: Prevent notifications for stale incidents.

### Change Detection Summary

| Change Type | Save to DB? | Resend All? | Notify? |
|------------|-------------|-------------|---------|
| New units dispatched | Yes | No | Only new units |
| Call type changed | Yes | Yes | All topics |
| Location changed | Yes | Yes | All topics |
| Alarm level increased | Yes | Yes | All topics |
| No changes | No | N/A | No |
| Too old (>15 min) | Yes | N/A | No |

## Notification System

When `fcn_21_sendMessage()` is called, it sends notifications to configured services.

**Function**: `fcn_21_sendMessage()` ([documentation](functions/fcn_21_sendMessage.md))

### Step 1: Load Incident Data from Database
```php
$sql = "SELECT * FROM {$db_incident} WHERE db_CallId = ?";
// Execute and fetch complete incident record
```

**Why?** Ensures latest data sent, even if multiple XMLs arrived quickly.

### Step 2: Build Google Maps URL
```php
$mapUrl = "https://www.google.com/maps/dir/?api=1&destination={$db_LatitudeY},{$db_LongitudeX}";
```

**Purpose**: Provide driving directions link in notifications.

### Step 3: Send NTFY Notification (If Enabled)

**Helper Function**: `sendNtfyNotification()`

#### Determine Tags
```php
$tags = match ($db_AgencyType) {
    "Fire" => "fire_engine",
    "Police" => "police_car",
    default => "fire_engine,police_car"
};

// Add alarm level emoji
if ($db_AlarmLevel == "1") $tags = "1st_place_medal,{$tags}";
// ... similar for levels 2 and 3
```

#### Calculate Priority
```php
$priority = ((int) ($db_AlarmLevel ?? 1)) + 2;
// Alarm 1 → Priority 3
// Alarm 2 → Priority 4
// Alarm 3 → Priority 5
```

#### Rebuild Topics If Needed
```php
if ($resendAll === 1) {
    $topics = "{$db_AgencyType}|{$db_Incident_Jurisdiction}|{$db_UnitNumber}";
}
```

#### Send to Each Topic
Split topics by `|` and send separately to each:

```php
foreach ($topicArray as $topic) {
    $context = stream_context_create([
        'http' => [
            'method' => 'PUT',
            'header' => [
                "Authorization: {$config['ntfy']['authToken']}",
                "Title: Call: {$db_CallNumber} {$db_CallType} ({$delta})",
                "Tags: {$tags}",
                "Attach: {$mapUrl}",
                "Priority: {$priority}"
            ],
            'content' => "C-Name: {$db_CommonName}\nLoc: {$db_FullAddress}\n..."
        ]
    ]);
    
    file_get_contents("{$config['ntfy']['url']}/{$topic}", false, $context);
}
```

**Topic Hierarchy Example**:
- Topics: "FIRE|JURISDICTION_A|E1|L2"
- Sends to 4 separate topic channels:
  - ntfy.domain.com/FIRE
  - ntfy.domain.com/JURISDICTION_A
  - ntfy.domain.com/E1
  - ntfy.domain.com/L2

### Step 4: Send Pushover Notification (If Enabled)

**Helper Function**: `sendPushoverNotification()`

#### Build Request
```php
curl_setopt_array($ch, [
    CURLOPT_URL => $config['pushover']['url'],
    CURLOPT_POSTFIELDS => [
        "token" => $config['pushover']['token'],
        "user" => $config['pushover']['user'],
        "title" => "MCCD Call: {$db_CallNumber} {$db_CallType} ({$delta})",
        "message" => "C-Name: {$db_CommonName}\nLoc: {$db_FullAddress}\n...",
        "sound" => "bike",
        "url" => $mapUrl,
        "url_title" => "Driving Directions"
    ]
]);
```

#### Send Request
```php
$result = curl_exec($ch);
```

#### Validate Response
```php
$responseData = json_decode($result, true);
if ($responseData["status"] !== 1) {
    throw new RuntimeException("Pushover API error");
}
```

**Difference from NTFY**: Pushover sends single notification (not topic-based).

### Step 5: Database Cleanup
```php
fcn_22_removeOldRecords($db_conn, $db_incident, $CallId, $logger);
```

**Purpose**: Keep database size manageable by removing incidents older than current - 999.

**Reference**: [fcn_22_removeOldRecords.md](functions/fcn_22_removeOldRecords.md)

## Cleanup and Maintenance

The application performs automatic cleanup at multiple levels to prevent resource exhaustion.

### Watch Folder Cleanup

**Function**: `fcn_1_unlinkInputOld()`  
**When**: Once at startup  
**Threshold**: 900 seconds (15 minutes)  
**Purpose**: Remove old XML files from watch folder before monitoring starts

**Reference**: [fcn_1_unlinkInputOld.md](functions/fcn_1_unlinkInputOld.md)

### Archive Folder Cleanup

**Function**: `fcn_18_unlinkArchiveOld()`  
**When**: After processing each file  
**Threshold**: 3600 seconds (1 hour)  
**Purpose**: Remove old processed XML files from archive to manage disk space

**Reference**: [fcn_18_unlinkArchiveOld.md](functions/fcn_18_unlinkArchiveOld.md)

### Database Cleanup

**Function**: `fcn_22_removeOldRecords()`  
**When**: After sending each notification  
**Threshold**: Current Call ID - 999  
**Purpose**: Keep only most recent 999 incidents in database

**Reference**: [fcn_22_removeOldRecords.md](functions/fcn_22_removeOldRecords.md)

### Cleanup Strategy

```
┌─────────────────────────────────────────────────────────────────┐
│                    Cleanup Levels                                │
├─────────────────────────────────────────────────────────────────┤
│                                                                   │
│  Watch Folder:                                                   │
│    - Cleaned once at startup                                     │
│    - Files older than 15 minutes removed                         │
│    - Prevents backlog from previous runs                         │
│                                                                   │
│  Archive Folder:                                                 │
│    - Cleaned after each file processed                           │
│    - Files older than 1 hour removed                             │
│    - Keeps recent files for troubleshooting                      │
│                                                                   │
│  Database:                                                       │
│    - Cleaned after each notification sent                        │
│    - Keeps last 999 incident records                             │
│    - Based on Call ID, not time                                  │
│    - Maintains operational history                               │
│                                                                   │
│  Result: Steady-state resource usage for long-running operation  │
│                                                                   │
└─────────────────────────────────────────────────────────────────┘
```

## Data Flow Diagram

Complete end-to-end data flow from XML arrival to notification delivery:

```
┌──────────────────────────────────────────────────────────────────────────┐
│                         COMPLETE DATA FLOW                                │
└──────────────────────────────────────────────────────────────────────────┘

[CAD System] → exports XML file
                    ↓
[Watch Folder] data/watchfolder/incident_12345.xml
                    ↓
[Monitor Loop] (every 3 seconds)
                    ↓
┌───────────────────────────────────────────────────────────────────────────┐
│ fcn_2_monitorFolder()                                                     │
│   ↓                                                                       │
│ fcn_3_globCaseInsensitivePattern(['xml']) → '*.{[Xx][Mm][Ll]}'          │
│   ↓                                                                       │
│ fcn_4_recursiveGlob() - discovers files                                  │
│   ↓                                                                       │
│ FOR EACH FILE FOUND:                                                     │
│   ↓                                                                       │
│ fcn_5_runExternal()                                                      │
│   ├─ Validate file                                                       │
│   ├─ Calculate relative path                                             │
│   ├─ fcn_10_openConnection() → Database connection                       │
│   ├─ fcn_11_tableExists() → Check schema                                 │
│   ├─ fcn_12_createIncidentsTable() → Create if needed                    │
│   ├─ fcn_13_recordReceived() ────────┐                                   │
│   │                                   │                                   │
│   │  ┌────────────────────────────────┘                                  │
│   │  │ Parse XML with simplexml_load_file()                              │
│   │  │ Extract: agencies, jurisdictions, units                           │
│   │  │ fcn_20_deltaTime() → Calculate age                                │
│   │  │                                                                    │
│   │  │ ┌──── Closed? ────→ fcn_14_deleteRecord() → DELETE → END         │
│   │  │ │                                                                  │
│   │  │ ├──── New? ────────→ fcn_16_insertRecord() → INSERT               │
│   │  │ │                    fcn_21_sendMessage() → NOTIFY ALL → END      │
│   │  │ │                                                                  │
│   │  │ └──── Update? ─────→ Load from database                           │
│   │  │                      Compare: topics, call type, location, alarm  │
│   │  │                      Changes found?                               │
│   │  │                        ├─ Yes → Time check                        │
│   │  │                        │   ├─ Recent → UPDATE + NOTIFY            │
│   │  │                        │   └─ Old → UPDATE only                   │
│   │  │                        └─ No → Skip                               │
│   │  └─ END fcn_13_recordReceived()                                      │
│   │                                                                       │
│   ├─ Close database (finally block)                                      │
│   ├─ fcn_18_unlinkArchiveOld() → Clean old archives                      │
│   ├─ Move file to archive with rename()                                  │
│   └─ END fcn_5_runExternal()                                             │
│                                                                           │
│ CONTINUE TO NEXT FILE                                                    │
└───────────────────────────────────────────────────────────────────────────┘
                    ↓
[Archive Folder] data/archive/incident_12345.xml
                    ↓
[Sleep 3 seconds]
                    ↓
[Repeat Monitoring Loop]

┌───────────────────────────────────────────────────────────────────────────┐
│ fcn_21_sendMessage() - Notification Details                              │
├───────────────────────────────────────────────────────────────────────────┤
│                                                                           │
│ Query database for full incident data                                    │
│   ↓                                                                       │
│ Build Google Maps URL from coordinates                                   │
│   ↓                                                                       │
│ NTFY Enabled?                                                            │
│   ├─ Yes → sendNtfyNotification()                                        │
│   │         ├─ Calculate tags (fire_engine, police_car, alarm medals)    │
│   │         ├─ Calculate priority (3-5 based on alarm level)             │
│   │         ├─ Split topics by |                                         │
│   │         ├─ FOR EACH TOPIC:                                           │
│   │         │   └─ PUT to ntfy.url/topic with headers and body           │
│   │         └─ Continue on per-topic errors                              │
│   │                                                                       │
│   └─ No → Skip                                                           │
│                                                                           │
│ Pushover Enabled?                                                        │
│   ├─ Yes → sendPushoverNotification()                                    │
│   │         ├─ Initialize cURL                                           │
│   │         ├─ Configure POST to Pushover API                            │
│   │         ├─ Send request with incident details                        │
│   │         ├─ Validate JSON response                                    │
│   │         └─ Close cURL (finally)                                      │
│   │                                                                       │
│   └─ No → Skip                                                           │
│                                                                           │
│ fcn_22_removeOldRecords() → Delete old incidents (keep last 999)         │
│                                                                           │
└───────────────────────────────────────────────────────────────────────────┘
                    ↓
[Notifications Delivered]
    ↓               ↓
[ntfy.sh]      [Pushover]
    ↓               ↓
[Subscribers] [Mobile Devices]
```

## Error Handling Strategy

The application implements comprehensive error handling at multiple levels.

### Philosophy
1. **Fail Gracefully**: Individual errors don't stop the entire system
2. **Log Everything**: All errors logged with context for troubleshooting
3. **Continue Processing**: One bad file doesn't prevent processing others
4. **Notify Failures**: Upstream code can handle critical failures

### Error Handling Levels

#### Level 1: Individual File Errors
```php
// In fcn_4_recursiveGlob()
try {
    fcn_5_runExternal($file, ...);
} catch (\Throwable $e) {
    $logger->error("Error processing file {$file}: " . $e->getMessage());
    // Continue processing other files
}
```

**Result**: Bad XML file logged, other files still processed.

#### Level 2: Individual Directory Errors
```php
// In fcn_4_recursiveGlob()
try {
    fcn_4_recursiveGlob($_dir, ...);  // Recursive call
} catch (\Throwable $e) {
    $logger->error("Error processing subdirectory {$_dir}: " . $e->getMessage());
    // Continue processing other directories
}
```

**Result**: Inaccessible directory logged, other directories still scanned.

#### Level 3: Database Errors
```php
// In fcn_5_runExternal()
try {
    // ... database operations
    fcn_13_recordReceived(...);
} finally {
    $db_conn = null;  // Always close connection
    $logger->info("Database connection closed");
}
```

**Result**: Database connection always cleaned up, even on errors.

#### Level 4: Notification Errors (NTFY)
```php
// In sendNtfyNotification()
foreach ($topicArray as $topic) {
    try {
        // Send to this topic
    } catch (Exception $e) {
        $logger->error("Error sending NTFY message to topic {$topic}");
        // Continue with other topics
    }
}
```

**Result**: Failed topic logged, other topics still notified.

#### Level 5: Critical Failures
```php
// In fcn_21_sendMessage()
catch (PDOException $e) {
    $logger->error("Database error in fcn_21_sendMessage for CallId {$CallId}");
    throw $e;  // Re-throw for upstream handling
}
```

**Result**: Critical errors propagated up call stack for handling.

### Error Recovery

| Error Type | Action | System Impact |
|-----------|--------|---------------|
| Bad XML file | Log error, skip file | None - continues |
| Inaccessible directory | Log error, skip directory | None - continues |
| Database connection failure | Log error, throw exception | File processing fails |
| Single topic notification failure | Log error, continue other topics | Partial notification |
| Pushover API failure | Log error, throw exception | File processing continues |
| File move failure | Log error, throw exception | File remains in watch folder (reprocessed) |

## Function Call Chain

Complete function call hierarchy from startup to notification:

```
src/run (main entry point)
│
├─ Startup Phase
│  ├─ Load configuration
│  ├─ Initialize logger (Monolog)
│  ├─ Load all function files (include_once)
│  ├─ Create directories (mkdir)
│  └─ fcn_1_unlinkInputOld()                   # Initial cleanup
│
└─ Monitoring Loop (while true)
   └─ fcn_2_monitorFolder()                    # Main entry point
      ├─ Validate parameters
      ├─ fcn_6_recursiveMkdir()                # Ensure folders exist
      ├─ fcn_3_globCaseInsensitivePattern()    # Build file pattern
      └─ fcn_4_recursiveGlob()                 # Find files
         ├─ glob() - built-in PHP               # Find matching files
         ├─ [Recursive] fcn_4_recursiveGlob()   # Process subdirectories
         └─ [Per File] fcn_5_runExternal()      # Process each file
            ├─ Calculate relative path
            ├─ fcn_6_recursiveMkdir()           # Create output directory
            ├─ fcn_7_renameIfExists()           # Generate unique filename
            │  └─ fcn_8_getValue()               # Safe array access
            │     └─ fcn_9_fileNewname()        # Generate numbered name
            ├─ fcn_10_openConnection()          # Open database
            ├─ fcn_11_tableExists()             # Check schema
            ├─ fcn_12_createIncidentsTable()    # Create if needed
            ├─ fcn_13_recordReceived()          # ** CORE PROCESSING **
            │  ├─ simplexml_load_file()          # Parse XML
            │  ├─ fcn_20_deltaTime()             # Calculate age
            │  │  └─ strtotime()                 # Parse timestamp
            │  │
            │  ├─ [Closed Path]
            │  │  └─ fcn_14_deleteRecord()       # Remove from database
            │  │
            │  ├─ [New Path]
            │  │  ├─ fcn_15_callIdExist()        # Check if exists
            │  │  ├─ fcn_16_insertRecord()       # Add to database
            │  │  │  ├─ Extract XML fields
            │  │  │  ├─ Clean/sanitize data
            │  │  │  └─ INSERT OR REPLACE
            │  │  └─ fcn_21_sendMessage()        # Send notifications
            │  │     ├─ Query database
            │  │     ├─ Build Google Maps URL
            │  │     ├─ sendNtfyNotification()   # NTFY helper
            │  │     │  ├─ Calculate tags
            │  │     │  ├─ Calculate priority
            │  │     │  ├─ Split topics
            │  │     │  └─ [Per Topic]
            │  │     │     └─ file_get_contents() # HTTP PUT
            │  │     ├─ sendPushoverNotification() # Pushover helper
            │  │     │  ├─ curl_init()
            │  │     │  ├─ curl_setopt_array()
            │  │     │  ├─ curl_exec()
            │  │     │  └─ curl_close()
            │  │     └─ fcn_22_removeOldRecords() # Cleanup old incidents
            │  │
            │  └─ [Update Path]
            │     ├─ fcn_15_callIdExist()        # Check if exists
            │     ├─ Load from database
            │     ├─ Compare fields (detect changes)
            │     ├─ fcn_16_insertRecord()       # Update database
            │     └─ fcn_21_sendMessage()        # Notify if recent
            │        └─ [Same as New Path]
            │
            ├─ Close database (finally)
            ├─ fcn_18_unlinkArchiveOld()        # Clean old archives
            ├─ fcn_6_recursiveMkdir()           # Create archive directory
            ├─ fcn_7_renameIfExists()           # Generate unique archive name
            └─ rename()                          # Move to archive
   
   └─ sleep(3)                                  # Wait 3 seconds
      └─ [Loop continues]
```

## Summary

The NWS Endpoints repository implements a comprehensive incident notification system with these key characteristics:

### Design Principles
1. **Continuous Monitoring**: Infinite loop with 3-second interval
2. **Fail-Safe Processing**: Individual errors don't stop the system
3. **Change Detection**: Smart notification only when relevant changes occur
4. **Time-Based Filtering**: Prevents notifications for stale incidents
5. **Automatic Cleanup**: Maintains manageable resource usage
6. **Comprehensive Logging**: Complete audit trail of all operations

### Processing Pipeline
1. **Discover** → Recursive file scanning with case-insensitive matching
2. **Process** → XML parsing and incident data extraction
3. **Decide** → Intelligent change detection and notification logic
4. **Store** → SQLite database with 24-field schema
5. **Notify** → Multi-service notifications (ntfy.sh + Pushover)
6. **Archive** → File preservation with automatic cleanup
7. **Cleanup** → Database and file system maintenance

### Key Features
- **Hierarchical Topics** (ntfy.sh): Agency/Jurisdiction/Unit structure
- **Selective Notifications**: Only relevant topics notified on updates
- **Location Integration**: Google Maps driving directions
- **Priority Calculation**: Based on alarm levels
- **Emoji Tags**: Visual indicators for agency type and alarm level
- **Time Delta Display**: Shows incident age in notifications
- **Automatic Maintenance**: Keeps database at ~999 records, archives at 1 hour

### Function Organization
- **File Monitoring**: fcn_1-4 (cleanup, discovery, pattern matching)
- **File Operations**: fcn_5-9 (processing, directories, filenames)
- **Database**: fcn_10-16 (connection, schema, CRUD operations)
- **Notifications**: fcn_18, 20-22 (cleanup, time, messaging)

This architecture provides a robust, maintainable system for real-time emergency incident notifications with intelligent change detection and multi-service delivery.
