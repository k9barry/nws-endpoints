# NWS Endpoints - New World CAD Notification System

A PHP application that monitors Tyler Tech New World CAD XML exports and sends real-time incident notifications via ntfy.sh and Pushover. The system processes emergency dispatch data and creates hierarchical notification topics based on agency, jurisdiction, and unit assignments.

## Table of Contents

- [Overview](#overview)
- [Features](#features)
- [Requirements](#requirements)
- [Installation](#installation)
- [Configuration](#configuration)
- [Usage](#usage)
- [Architecture](#architecture)
- [Core Functions](#core-functions)
- [Notification System](#notification-system)
- [Docker Deployment](#docker-deployment)
- [Troubleshooting](#troubleshooting)

## Overview

This application bridges Tyler Tech's New World CAD system with modern notification services. It continuously monitors a watch folder for XML files exported from the CAD system, parses incident data, stores it in SQLite, and sends notifications through multiple channels.

### Key Workflow
1. **Monitor** - Continuously watches for new XML files from New World CAD exporter
2. **Parse** - Extracts incident details, agency info, and unit assignments
3. **Store** - Maintains active incidents in SQLite database
4. **Notify** - Sends structured notifications via ntfy.sh and Pushover
5. **Archive** - Moves processed files to archive folder

## Features

- **Real-time Monitoring** - Continuous folder monitoring with 3-second intervals
- **Hierarchical Topics** - Creates nested notification topics (Agency/Jurisdiction/Unit)
- **Multiple Notifications** - Supports both ntfy.sh and Pushover simultaneously
- **Database Storage** - SQLite database for incident lifecycle management
- **Google Maps Integration** - Automatic location links in notifications
- **Comprehensive Logging** - Detailed logging with Monolog
- **Docker Support** - Complete containerized deployment
- **Automatic Cleanup** - Removes old files and closed incidents

## Requirements

### Core Requirements
- **Tyler Tech New World CAD** with XML export interface
- **PHP 8.1+** with required extensions:
  - `ext-curl` - For HTTP notifications
  - `ext-pdo` - Database connectivity
  - `ext-pdo_sqlite` - SQLite support
  - `ext-simplexml` - XML parsing

### Optional Requirements
- **Docker & Docker Compose** - For containerized deployment
- **ntfy.sh server** - Self-hosted or hosted notification service
- **Pushover account** - Mobile push notifications
- **Google Maps API key** - Location links in notifications

## Installation

### Local PHP Setup (Recommended)

1. **Install PHP and extensions:**
   ```bash
   # Ubuntu/Debian
   sudo apt-get update && sudo apt-get install -y \
     php8.3-cli php8.3-curl php8.3-pdo php8.3-sqlite3 php8.3-xml
   
   # Verify extensions
   php --modules | grep -E "(curl|pdo|sqlite|xml)"
   ```

2. **Clone and setup:**
   ```bash
   git clone https://github.com/k9barry/nws-endpoints.git
   cd nws-endpoints/src
   composer install --no-interaction --prefer-dist
   ```

3. **Configure the application:**
   ```bash
   cp config.php.dist config.php
   mkdir -p data/watchfolder data/output data/archive data/db
   ```

4. **Edit configuration:**
   ```bash
   nano config.php
   ```

### Docker Setup

```bash
git clone https://github.com/k9barry/nws-endpoints.git
cd nws-endpoints
docker-compose up -d
```

## Configuration

### Basic Configuration (`src/config.php`)

```php
<?php
// Enable/disable ntfy notifications
$ntfySend = true;
$ntfyUrl = "https://ntfy.your-domain.com";
$ntfyAuthToken = "Bearer tk_your_token_here";

// Enable/disable Pushover notifications  
$pushoverSend = true;
$pushoverUrl = "https://api.pushover.net/1/messages.json";
$pushoverToken = "your_pushover_app_token";
$pushoverUser = "your_pushover_user_key";
```

### Directory Structure
```
src/
├── data/
│   ├── watchfolder/    # XML files from New World CAD
│   ├── output/         # Temporary processing folder
│   ├── archive/        # Processed XML files
│   └── db/            # SQLite database files
├── functions/         # Core processing functions
├── config.php        # Configuration file
└── run               # Main application entry point
```

## Usage

### Start the Application
```bash
cd src/
php run
```

The application runs as a daemon, continuously monitoring the watch folder. Output shows:

```
[INFO] nws-endpoint logger is now ready
[INFO] Watch folder found at ./data/watchfolder  
[INFO] Output folder found at ./data/output
[INFO] Backup folder found at ./data/archive
```

### Testing with Sample Data

Place New World CAD XML files in `src/data/watchfolder/`. The application will:
1. Detect the new file
2. Parse incident data
3. Send notifications
4. Move file to archive

## Architecture

### Core Processing Flow

```
XML File → Monitor → Parse → Database → Notify → Archive
    ↓         ↓        ↓        ↓         ↓        ↓
Watch     fcn_2    fcn_13   fcn_12    fcn_21   fcn_5
Folder    Monitor  Record   Create    Send     Run
          Folder   Received Table     Message  External
```

### Database Schema

The SQLite database stores incident data with this structure:

```sql
CREATE TABLE incidents (
    db_CallId INTEGER PRIMARY KEY,
    db_CallNumber INTEGER,
    db_ClosedFlag TEXT,
    db_AgencyType TEXT,
    db_CreateDateTime TEXT,
    db_CallType TEXT,
    db_AlarmLevel TEXT,
    db_RadioChannel TEXT,
    db_NatureOfCall TEXT,
    db_CommonName TEXT,
    db_FullAddress TEXT,
    db_State TEXT,
    db_NearestCrossStreets TEXT,
    db_AdditionalInfo TEXT,
    db_FireOri TEXT,
    db_FireQuadrant TEXT,
    db_PoliceOri TEXT,
    db_PoliceBeat TEXT,
    db_LatitudeY TEXT,
    db_LongitudeX TEXT,
    db_UnitNumber TEXT,
    db_Narrative_Text TEXT
);
```

## Core Functions

### File Monitoring Functions

#### `fcn_2_monitorFolder` - Main Entry Point
```php
/**
 * Monitors a folder for new files with specified extensions and processes them.
 * This is the main entry point for file monitoring that initiates the recursive
 * file discovery and processing workflow for New World CAD XML files.
 */
function fcn_2_monitorFolder(
    string $strInFolder,      // Input folder path to monitor
    array $extensions,        // File extensions to monitor (e.g., ['xml'])
    string $strOutFolder,     // Output folder for processed files
    string $strBackupFolder,  // Archive folder for storing processed files
    LoggerInterface $logger,  // Logger instance
    string $db,              // Database file path
    string $db_table,        // Database table name
    array $config            // Configuration array
): void
```

**Example usage:**
```php
fcn_2_monitorFolder(
    './data/watchfolder', 
    ['xml'], 
    './data/output', 
    './data/archive', 
    $logger, 
    './data/db/db.sqlite', 
    'incidents', 
    $config
);
```

#### `fcn_4_recursiveGlob` - File Discovery
```php
/**
 * Recursively searches for files matching specified patterns in directories.
 * Processes each found file through the New World CAD workflow.
 */
function fcn_4_recursiveGlob(
    string $dir,              // Current directory being searched
    string $ext,              // File extension pattern to match
    string $strInRootFolder,  // Root folder path
    string $strOutFolder,     // Output folder
    string $strBackupFolder,  // Archive folder
    LoggerInterface $logger,  // Logger instance
    string $db,              // Database path
    string $db_table,        // Database table
    array $config            // Configuration
): void
```

### File Processing Functions

#### `fcn_5_runExternal` - Main Processing Coordinator
```php
/**
 * Processes a single New World CAD file through the complete workflow.
 * Handles file movement, database operations, and triggers notification sending.
 */
function fcn_5_runExternal(
    string $strInFile,        // Full path to input file
    string $strInRootFolder,  // Root input folder
    string $strOutFolder,     // Output folder
    string $strBackupFolder,  // Archive folder
    LoggerInterface $logger,  // Logger instance
    string $db,              // Database path
    string $db_table,        // Database table
    array $config            // Configuration
): void
```

**Processing workflow:**
```php
// 1. Validate input file exists and is readable
if (!is_file($strInFile) || !is_readable($strInFile)) {
    throw new InvalidArgumentException("Input file not readable: {$strInFile}");
}

// 2. Setup database connection and create table if needed
$db_conn = fcn_10_openConnection($db, $logger);
if (!fcn_11_tableExists($db_conn, $db_table, $logger)) {
    fcn_12_createIncidentsTable($db_conn, $db_table, $logger);
}

// 3. Process the incident record
fcn_13_recordReceived($db_conn, $db_table, $strInFile, $logger, $config);

// 4. Move file to archive
$strBackupFile = rtrim($strBackupFolder, DIRECTORY_SEPARATOR) . 
                DIRECTORY_SEPARATOR . $strRelativeFileName;
rename($strInFile, $strBackupFile);
```

#### `fcn_13_recordReceived` - XML Processing Engine
```php
/**
 * Main processing function for New World CAD incident records.
 * Parses XML data, extracts agency/jurisdiction/unit information, 
 * determines if record is new or updated, and triggers notifications.
 */
function fcn_13_recordReceived(
    mixed $db_conn,          // Database connection (PDO)
    string $db_incident,     // Database table name
    string $strInFile,       // XML file path
    LoggerInterface $logger, // Logger instance
    array $config           // Configuration array
): void
```

**XML parsing example:**
```php
// Load and parse XML file
$xml = simplexml_load_file($strInFile) or die("Error: Cannot create object");

// Extract agency information
$agencies = $sep = '';
$nrOfRows = $xml->AgencyContexts->AgencyContext->count();
for ($n = 0; $n < $nrOfRows; $n++) {
    $value = $xml->AgencyContexts->AgencyContext[$n]->AgencyType;
    $agencies .= $sep . $value;
    $sep = '|';
}
$agencies = implode("|", array_unique(explode("|", $agencies)));

// Extract jurisdictions
$jurisdictions = $sep = '';
$nrOfRows = $xml->Incidents->Incident->count();
for ($n = 0; $n < $nrOfRows; $n++) {
    $value = $xml->Incidents->Incident[$n]->Jurisdiction;
    $jurisdictions .= $sep . $value;
    $sep = '|';
}

// Extract assigned units
$units = $sep = '';
$nrOfRows = $xml->AssignedUnits->Unit->count();
for ($n = 0; $n < $nrOfRows; $n++) {
    $value = $xml->AssignedUnits->Unit[$n]->UnitNumber;
    $units .= $sep . $value;
    $sep = '|';
}
```

### Database Functions

#### `fcn_12_createIncidentsTable` - Schema Creation
```php
/**
 * Creates the incidents table in SQLite database if it doesn't exist.
 * Defines complete schema for storing New World CAD incident data.
 */
function fcn_12_createIncidentsTable(
    PDO $db_conn,            // Database connection
    string $db_incident,     // Table name to create
    LoggerInterface $logger  // Logger instance
): void
```

#### `fcn_8_getValue` - Safe Array Access
```php
/**
 * Safely retrieves a value from an array with default fallback.
 * Prevents errors when accessing array keys that might not exist.
 */
function fcn_8_getValue(
    array $array,           // Array to retrieve value from
    mixed $index,           // Array key/index to access
    null|string $default = '' // Default value if key doesn't exist
): ?string
```

**Usage example:**
```php
// Safely extract XML values with defaults
$callNumber = fcn_8_getValue($incidentData, 'CallNumber', 'Unknown');
$address = fcn_8_getValue($incidentData, 'FullAddress', 'Location Unknown');
$callType = fcn_8_getValue($incidentData, 'CallType', 'General Call');
```

## Notification System

### Topic Hierarchy

Notifications are sent to hierarchical topics following this pattern:
```
Agency/Jurisdiction/Unit
└── Police/MCPD/CAR1
└── Fire/MCFD/ENGINE1
└── EMS/MCEMS/MEDIC1
```

### ntfy.sh Integration

#### `fcn_21_sendMessage` - Unified Notification Sender
```php
/**
 * Sends notifications via both ntfy.sh and Pushover services.
 * Creates hierarchical topics and formatted messages with incident details.
 */
function fcn_21_sendMessage(
    PDO $db_conn,           // Database connection
    string $db_incident,    // Database table name
    int $CallId,           // Incident call ID
    LoggerInterface $logger, // Logger instance
    array $config          // Configuration array
): void
```

#### ntfy.sh Message Format
```php
// Create HTTP context for ntfy.sh
$context = stream_context_create([
    'http' => [
        'method' => 'PUT',
        'timeout' => 10,
        'header' => implode("\r\n", [
            "Content-Type: text/plain",
            "Authorization: {$config['ntfy']['authToken']}",
            "Title: Call: {$db_CallNumber} {$db_CallType} ({$delta})",
            "Tags: fire,police,ems",
            "Attach: {$mapUrl}",
            "Icon: https://example.com/icon.jpg",
            "Priority: {$priority}"
        ]),
        'content' => "C-Name: {$db_CommonName}\n" .
                    "Loc: {$db_FullAddress}\n" .
                    "Inc: {$db_CallType}\n" .
                    "Nature: {$db_NatureOfCall}\n" .
                    "Cross Rd: {$db_NearestCrossStreets}\n" .
                    "Beat: {$db_PoliceBeat}\n" .
                    "Quad: {$db_FireQuadrant}\n" .
                    "Unit: {$db_UnitNumber}\n" .
                    "Time: {$db_CreateDateTime}\n" .
                    "Narr: {$db_Narrative_Text}"
    ]
]);

$result = file_get_contents("{$config['ntfy']['url']}/{$topic}", false, $context);
```

### Pushover Integration

```php
// Setup cURL for Pushover
$ch = curl_init();
curl_setopt_array($ch, [
    CURLOPT_URL => $config['pushover']['url'],
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT => 30,
    CURLOPT_POSTFIELDS => [
        "token" => $config['pushover']['token'],
        "user" => $config['pushover']['user'],
        "title" => "MCCD Call: {$db_CallNumber} {$db_CallType} ({$delta})",
        "message" => "C-Name: {$db_CommonName}\n" .
                    "Loc: {$db_FullAddress}\n" .
                    "Inc: {$db_CallType}\n" .
                    "Nature: {$db_NatureOfCall}\n",
        "sound" => "bike",
        "html" => "1",
        "url" => $mapUrl,
        "url_title" => "Driving Directions"
    ]
]);
```

### Sample Notification Output

**ntfy.sh notification:**
```
Title: Call: 2024001234 Structure Fire (New Call)
Tags: fire,emergency

C-Name: MAIN ST FIRE
Loc: 123 MAIN ST, ANYTOWN, ST 12345
Inc: Structure Fire
Nature: RESIDENTIAL STRUCTURE FIRE
Cross Rd: ELM ST / OAK ST
Beat: B2
Quad: Q1
Unit: ENGINE1|TRUCK1|MEDIC1
Time: 2024-01-15 14:30:15
Narr: Smoke showing from 2nd floor window
```

## Docker Deployment

### Services Overview

The `compose.yml` defines three services:

#### 1. nws-endpoints (Main Application)
```yaml
nws-endpoints:
  build: .
  container_name: nws-endpoints
  restart: unless-stopped
  command: ["php", "run"]
  volumes:
    - db:/app/data/db
    - watchfolder:/app/data/watchfolder
```

#### 2. Dozzle (Log Viewer)
```yaml
dozzle:
  image: amir20/dozzle:latest
  container_name: dozzle
  restart: unless-stopped
  ports:
    - "8081:8080"
  volumes:
    - /var/run/docker.sock:/var/run/docker.sock
```

**Web Browser Access:** http://localhost:8081

Dozzle provides a real-time web interface for viewing Docker container logs. Once the containers are running, you can:

1. **Access the Interface:** Open your web browser and navigate to `http://localhost:8081`
2. **View Container Logs:** 
   - Select the `nws-endpoints` container to view application logs
   - Monitor real-time log output as XML files are processed
   - Filter logs by time range or search for specific entries
3. **Features Available:**
   - Real-time log streaming
   - Multi-container log viewing
   - Search and filter capabilities
   - Dark/light theme toggle
   - Download logs as text files

**Prerequisites:** Docker Compose must be running (`docker-compose up -d`) and port 8081 must be available.

#### 3. SQLite Browser (Database Viewer)
```yaml
sqlitebrowser:
  image: lscr.io/linuxserver/sqlitebrowser:3.12.2
  container_name: sqlitebrowser
  restart: unless-stopped
  ports:
    - "8082:3000"
  volumes:
    - db:/config
```

**Web Browser Access:** http://localhost:8082

SQLite Browser provides a web-based interface for viewing and managing the incident database. Once the containers are running, you can:

1. **Access the Interface:** Open your web browser and navigate to `http://localhost:8082`
2. **Open the Database:** 
   - Click "Open Database" in the web interface
   - Navigate to `/config/` folder (this is the mounted `db` volume)
   - Select `db.sqlite` to open the incidents database
3. **View Incident Data:**
   - Browse the `incidents` table to see all processed CAD records
   - Filter and search incident records by call number, type, or location
   - Export data to CSV or other formats
   - View database schema and table structure
4. **Features Available:**
   - Browse table data with pagination
   - Execute custom SQL queries
   - View database structure and indexes
   - Import/export database data
   - Real-time data updates as new incidents are processed

**Prerequisites:** Docker Compose must be running (`docker-compose up -d`) and port 8082 must be available.

### Volume Configuration

```yaml
volumes:
  db:                    # SQLite database storage
  watchfolder:          # CIFS mount for CAD exports
    driver_opts:
      type: cifs
      o: "username=${CIFS_USERNAME},password=${CIFS_PASSWORD}"
      device: ${SHARED_FOLDER_PATH}
```

### Web Browser Access Guide

#### Starting the Docker Services

Before accessing the web interfaces, ensure all containers are running:

```bash
# Start all services in detached mode
docker-compose up -d

# Verify services are running
docker-compose ps

# Expected output should show all three containers as "running"
```

#### Accessing the Web Interfaces

| Service | URL | Purpose | Default Credentials |  
|---------|-----|---------|-------------------|
| **Dozzle** | http://localhost:8081 | Real-time container log viewer | None required |
| **SQLite Browser** | http://localhost:8082 | Database management interface | None required |

#### Web Browser Troubleshooting

**Port Already in Use:**
```bash
# Check what's using the ports
netstat -tulpn | grep :8081
netstat -tulpn | grep :8082

# Modify compose.yml to use different ports if needed
ports:
  - "8083:8080"  # Change 8081 to 8083 for Dozzle
  - "8084:3000"  # Change 8082 to 8084 for SQLite Browser
```

**Cannot Access Web Interface:**
1. Verify containers are running: `docker-compose ps`
2. Check container logs: `docker-compose logs dozzle` or `docker-compose logs sqlitebrowser`
3. Ensure firewall allows connections to ports 8081 and 8082
4. Try accessing via server IP instead of localhost if using remote Docker host

**Database Not Visible in SQLite Browser:**
1. Ensure the `nws-endpoints` container has created the database by processing at least one XML file
2. Check the `/config` directory in the SQLite Browser interface
3. The database file should appear as `db.sqlite` after the first incident is processed

#### Container Management Commands

```bash
# View service status
docker-compose ps

# Start services
docker-compose up -d

# Stop services  
docker-compose down

# Restart a specific service
docker-compose restart dozzle
docker-compose restart sqlitebrowser

# View logs without Dozzle web interface
docker-compose logs -f nws-endpoints    # Follow logs in real-time
docker-compose logs --tail=100 dozzle   # View last 100 log lines

# Access container shell for debugging
docker exec -it nws-endpoints /bin/bash
```

### Environment Variables

Create `.env` file:
```env
CIFS_USERNAME=your_username
CIFS_PASSWORD=your_password
CIFS_DOMAIN=your_domain
SHARED_FOLDER_PATH=//server/share/cadexports
```

## Troubleshooting

### Common Issues

#### 1. PHP Extensions Missing
```bash
# Check required extensions
php --modules | grep -E "(curl|pdo|sqlite|xml)"

# Install missing extensions (Ubuntu/Debian)
sudo apt-get install php8.3-curl php8.3-pdo php8.3-sqlite3 php8.3-xml
```

#### 2. Permission Issues
```bash
# Fix data directory permissions
chmod -R 755 src/data/
chown -R www-data:www-data src/data/  # If running under web server
```

#### 3. Database Connection Errors
```bash
# Check SQLite file permissions
ls -la src/data/db/
sqlite3 src/data/db/db.sqlite ".tables"
```

#### 4. Notification Failures
- Verify ntfy.sh server accessibility
- Check authentication tokens
- Validate Pushover credentials
- Review firewall settings

### Logging and Debugging

The application uses Monolog for comprehensive logging:

```bash
# Monitor logs in real-time
cd src/
php run

# Check specific function execution
grep "fcn_13_recordReceived" logs/app.log
```

### Performance Monitoring

- Monitor folder scan frequency (default: 3 seconds)
- Watch database file size growth
- Check notification delivery times
- Review archive folder cleanup

---

**Author:** k9barry@gmail.com  
**License:** [View LICENSE](LICENSE)  
**Repository:** https://github.com/k9barry/nws-endpoints
