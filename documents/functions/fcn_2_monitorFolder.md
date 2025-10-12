# fcn_2_monitorFolder Function Documentation

## Purpose
Main entry point for file monitoring that initiates the recursive file discovery and processing workflow for New World CAD XML files. This function continuously monitors a folder for new files with specified extensions.

## Location
`src/functions/fcn_2_monitorFolder.php`

## Function Signature
```php
function fcn_2_monitorFolder(
    string $strInFolder,
    array $extensions,
    string $strOutFolder,
    string $strBackupFolder,
    LoggerInterface $logger,
    string $db,
    string $db_table,
    array $config
): void
```

## Parameters

| Parameter | Type | Description |
|-----------|------|-------------|
| `$strInFolder` | string | Input folder path to monitor for new files |
| `$extensions` | array | Array of file extensions to monitor (e.g., ['xml']) |
| `$strOutFolder` | string | Output folder for processed files |
| `$strBackupFolder` | string | Archive folder for storing processed files |
| `$logger` | LoggerInterface | Logger instance for monitoring operations |
| `$db` | string | Database file path for incident storage |
| `$db_table` | string | Database table name for incident records |
| `$config` | array | Configuration array containing notification and timing settings |

## Return Value
- Returns `void` (no return value)

## Step-by-Step Process

### Step 1: Validate Input Parameters
- Checks if `$strInFolder` is not empty, throws `InvalidArgumentException` if empty
- Checks if `$extensions` array is not empty, throws `InvalidArgumentException` if empty
- Validates `$strOutFolder` is not empty
- Validates `$strBackupFolder` is not empty
- Validates `$db` path is not empty
- Validates `$db_table` name is not empty
- All validation errors are thrown as exceptions with descriptive messages

### Step 2: Ensure Output Folder Exists
- Checks if `$strOutFolder` directory exists using `is_dir()`
- If output folder doesn't exist:
  1. Calls `fcn_6_recursiveMkdir()` to create directory with 0755 permissions
  2. If directory creation fails, throws `RuntimeException`
  3. Logs any exceptions that occur during creation
  4. Re-throws exception for upstream handling

### Step 3: Ensure Backup Folder Exists
- Checks if `$strBackupFolder` directory exists using `is_dir()`
- If backup folder doesn't exist:
  1. Calls `fcn_6_recursiveMkdir()` to create directory with 0755 permissions
  2. If directory creation fails, throws `RuntimeException`
  3. Logs any exceptions that occur during creation
  4. Re-throws exception for upstream handling

### Step 4: Validate Input Folder Exists
- Confirms `$strInFolder` actually exists using `is_dir()`
- If input folder doesn't exist, throws `InvalidArgumentException`
- This is checked last because input folder should already exist (created by main script)

### Step 5: Generate Case-Insensitive File Pattern
- Calls `fcn_3_globCaseInsensitivePattern($extensions)` to create glob pattern
- Converts array of extensions like `['xml']` into pattern like `*.{[Xx][Mm][Ll]}`
- Pattern allows matching files regardless of case (XML, xml, Xml, etc.)
- Stores result in `$strFilterFormat`

### Step 6: Initiate Recursive File Processing
- Calls `fcn_4_recursiveGlob()` with all necessary parameters:
  - Current directory: `$strInFolder`
  - File pattern: `$strFilterFormat`
  - Root folder: `$strInFolder` (for relative path calculations)
  - Output folder: `$strOutFolder`
  - Backup folder: `$strBackupFolder`
  - Logger instance
  - Database path and table name
  - Configuration array
- This triggers the recursive search and processing of all matching files

### Step 7: Handle Exceptions
- Catches `InvalidArgumentException` exceptions
- Logs error message with exception details
- Returns gracefully without crashing the monitoring loop
- This allows the application to continue monitoring even if a scan fails

## Usage Example
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

## Error Handling
- Validates all input parameters before processing
- Creates required directories automatically if missing
- Catches and logs `InvalidArgumentException` for parameter validation errors
- Does not throw exceptions for missing directories - creates them instead
- Returns gracefully on parameter validation errors to keep monitoring loop running

## Integration
- Called continuously from main monitoring loop in `src/run`
- Executed every 3 seconds (default `$sleep` value)
- Acts as the orchestrator for the entire file processing workflow
- Delegates file discovery to `fcn_4_recursiveGlob()`
- Each call scans the watch folder and processes any new XML files found
