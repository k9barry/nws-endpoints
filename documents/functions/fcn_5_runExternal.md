# fcn_5_runExternal Function Documentation

## Purpose
Processes a single New World CAD XML file through the complete workflow. This is the main processing coordinator that handles file movement, database operations, incident parsing, and notification triggering.

## Location
`src/functions/fcn_5_runExternal.php`

## Function Signature
```php
function fcn_5_runExternal(
    string $strInFile,
    string $strInRootFolder,
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
| `$strInFile` | string | Full path to the input XML file to process |
| `$strInRootFolder` | string | Root input folder for relative path calculations |
| `$strOutFolder` | string | Output folder for temporary file processing |
| `$strBackupFolder` | string | Archive folder for storing processed files |
| `$logger` | LoggerInterface | Logger instance for processing operations |
| `$db` | string | Database file path for incident storage |
| `$db_table` | string | Database table name for incident records |
| `$config` | array | Configuration array containing notification and timing settings |

## Return Value
- Returns `void` (no return value)

## Step-by-Step Process

### Step 1: Validate Input File
- Checks if file exists using `is_file($strInFile)`
- Checks if file is readable using `is_readable($strInFile)`
- If either check fails, throws `InvalidArgumentException` with file path
- Ensures file can be accessed before processing

### Step 2: Validate Folder Paths
- Checks if `$strInRootFolder` is not empty
- Checks if `$strOutFolder` is not empty
- Checks if `$strBackupFolder` is not empty
- Throws `InvalidArgumentException` if any folder path is empty
- Prevents processing with incomplete configuration

### Step 3: Validate Database Parameters
- Checks if `$db` path is not empty
- Checks if `$db_table` name is not empty
- Throws `InvalidArgumentException` if either is empty
- Ensures database operations will work correctly

### Step 4: Calculate Relative Filename
- Normalizes root folder path with `rtrim()` and adds `DIRECTORY_SEPARATOR`
- Removes root folder path from full file path using `str_replace()`
- Removes leading separator with `ltrim()`
- Stores result in `$strRelativeFileName`
- Example: `/path/to/watchfolder/subdir/file.xml` → `subdir/file.xml`
- Logs relative filename for tracking

### Step 5: Prepare Output File Path
- Constructs output path: `$strOutFolder` + `/` + `$strRelativeFileName`
- Normalizes path separators using `str_replace('/', DIRECTORY_SEPARATOR, ...)`
- Ensures cross-platform compatibility (Windows vs Linux)

### Step 6: Create Output Directory
- Extracts directory path from output file using `dirname($strOutFile)`
- Calls `fcn_6_recursiveMkdir()` to create directory if needed
- If directory creation fails, throws `RuntimeException`
- Ensures destination directory exists before file operations

### Step 7: Generate Unique Output Filename
- Calls `fcn_7_renameIfExists($strOutFile)`
- If output file already exists, generates unique name (e.g., file_0.xml, file_1.xml)
- Updates `$strOutFile` with unique filename
- Prevents overwriting existing files

### Step 8: Open Database Connection
- Calls `fcn_10_openConnection($db, $logger)`
- Returns PDO connection object stored in `$db_conn`
- Connection configured with error mode and fetch mode
- Logs successful connection

### Step 9: Ensure Database Table Exists
- Calls `fcn_11_tableExists($db_conn, $db_table, $logger)`
- If table doesn't exist:
  - Calls `fcn_12_createIncidentsTable($db_conn, $db_table, $logger)`
  - Creates complete incidents table schema
- Ensures database is ready for data storage

### Step 10: Process Incident Record
- Calls `fcn_13_recordReceived($db_conn, $db_table, $strInFile, $logger, $config)`
- This is the main incident processing function:
  - Parses XML data
  - Extracts agency, jurisdiction, unit information
  - Determines if incident is new or updated
  - Triggers notifications if appropriate
  - Updates database records

### Step 11: Close Database Connection
- Uses finally block to ensure connection always closed
- Sets `$db_conn = null` to close PDO connection
- Logs connection closure
- Happens even if exceptions occur during processing

### Step 12: Clean Up Old Archives
- Calls `fcn_18_unlinkArchiveOld($strBackupFolder, $logger)`
- Removes files older than 1 hour from archive
- Manages disk space by preventing archive folder growth

### Step 13: Prepare Archive File Path
- Constructs archive path: `$strBackupFolder` + `/` + `$strRelativeFileName`
- Normalizes path separators for cross-platform compatibility
- Maintains directory structure in archive matching watch folder

### Step 14: Create Archive Directory
- Extracts directory path from archive file using `dirname($strBackupFile)`
- Calls `fcn_6_recursiveMkdir()` to create directory if needed
- If directory creation fails, throws `RuntimeException`
- Ensures archive subdirectory exists

### Step 15: Generate Unique Archive Filename
- Calls `fcn_7_renameIfExists($strBackupFile)`
- If archive file already exists, generates unique name
- Updates `$strBackupFile` with unique filename
- Prevents overwriting previously archived files

### Step 16: Move File to Archive
- Uses `@rename($strInFile, $strBackupFile)` to move file
- `@` suppresses PHP warnings, errors handled manually
- If rename fails:
  - Gets last error using `error_get_last()`
  - Logs error message with file paths
  - Throws `RuntimeException` with error details
- If successful:
  - Logs success message with source and destination paths

### Step 17: Handle Exceptions
- Entire process wrapped in try-catch block
- Catches all `Throwable` exceptions
- Logs exception message with function context
- Re-throws exception for upstream handling
- Allows calling code to decide how to handle failures

## Usage Example
```php
fcn_5_runExternal(
    './data/watchfolder/incident_12345.xml',
    './data/watchfolder',
    './data/output',
    './data/archive',
    $logger,
    './data/db/db.sqlite',
    'incidents',
    $config
);
```

## Error Handling
- Validates all parameters before processing
- Uses try-finally block to ensure database cleanup
- Catches and logs all exceptions with context
- Re-throws exceptions to allow upstream error handling
- Uses `@` suppression with manual error checking for file operations
- Provides detailed error messages for troubleshooting

## Integration
- Called by `fcn_4_recursiveGlob()` for each XML file found
- Orchestrates the complete file processing workflow
- Calls multiple sub-functions:
  - `fcn_6_recursiveMkdir()` - Directory creation
  - `fcn_7_renameIfExists()` - Unique filename generation
  - `fcn_10_openConnection()` - Database connection
  - `fcn_11_tableExists()` - Table validation
  - `fcn_12_createIncidentsTable()` - Table creation
  - `fcn_13_recordReceived()` - Incident processing
  - `fcn_18_unlinkArchiveOld()` - Archive cleanup
- Central coordinator for file-to-notification pipeline
