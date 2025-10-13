# fcn_4_recursiveGlob Function Documentation

## Purpose
Recursively searches for files matching specified patterns in directories and subdirectories. Processes each found file through the New World CAD workflow for incident notification. This is the core file discovery engine.

## Location
`src/functions/fcn_4_recursiveGlob.php`

## Function Signature
```php
function fcn_4_recursiveGlob(
    string $dir,
    string $ext,
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
| `$dir` | string | Current directory being searched |
| `$ext` | string | File extension pattern to match (from fcn_3_globCaseInsensitivePattern) |
| `$strInRootFolder` | string | Root input folder path for relative path calculations |
| `$strOutFolder` | string | Output folder for processed files |
| `$strBackupFolder` | string | Archive folder for storing processed files |
| `$logger` | LoggerInterface | Logger instance for file processing operations |
| `$db` | string | Database file path for incident storage |
| `$db_table` | string | Database table name for incident records |
| `$config` | array | Configuration array containing notification and timing settings |

## Return Value
- Returns `void` (no return value)

## Step-by-Step Process

### Step 1: Validate Directory
- Checks if `$dir` is a valid directory using `is_dir()`
- If not a directory, throws `InvalidArgumentException` with directory path
- Prevents attempting to scan non-existent or invalid paths

### Step 2: Validate Extension Pattern
- Checks if `$ext` pattern is not empty
- If empty, throws `InvalidArgumentException`
- Ensures file matching will work correctly

### Step 3: Normalize Directory Path
- Removes trailing directory separator using `rtrim($dir, DIRECTORY_SEPARATOR)`
- Ensures consistent path format across different operating systems
- Prevents double slashes in constructed paths

### Step 4: Find Matching Files
- Uses `glob($dir . DIRECTORY_SEPARATOR . $ext, GLOB_NOSORT | GLOB_BRACE)`
- `GLOB_NOSORT` - Don't sort results (faster performance)
- `GLOB_BRACE` - Expand braces for patterns like `{jpg,gif,png}`
- Returns array of file paths matching the extension pattern
- Stores result in `$globFiles`

### Step 5: Find Subdirectories
- Uses `glob($dir . DIRECTORY_SEPARATOR . "*", GLOB_ONLYDIR | GLOB_NOSORT)`
- `GLOB_ONLYDIR` - Only return directories, not files
- `GLOB_NOSORT` - Don't sort results (faster performance)
- Returns array of subdirectory paths
- Stores result in `$globDirs`

### Step 6: Process Subdirectories (Depth-First)
- Checks if `$globDirs` is not empty and is an array
- Iterates through each subdirectory
- For each subdirectory:
  1. Recursively calls `fcn_4_recursiveGlob()` with subdirectory path
  2. Passes all original parameters to maintain context
  3. Wraps call in try-catch block
  4. If exception occurs:
     - Logs error message with subdirectory path
     - Continues processing other directories (doesn't fail entire scan)

### Step 7: Process Files in Current Directory
- Checks if `$globFiles` is not empty and is an array
- Iterates through each file found

#### For Each File:

##### 7.1: Validate File Accessibility
- Checks if path is actually a file using `is_file()`
- Checks if file is readable using `is_readable()`
- If either check fails:
  - Logs warning with file path
  - Skips to next file

##### 7.2: Validate File Size
- Gets file size using `filesize($file)`
- Checks if size is false (error) or <= 0 (empty)
- If invalid:
  - Logs warning about empty/invalid file
  - Skips to next file
- Prevents processing corrupted or incomplete files

##### 7.3: Log File Discovery
- Logs info message with file path and size
- Example: "Found file: ./data/watchfolder/incident.xml (size: 2048 bytes)"
- Helps track processing activity

##### 7.4: Process File
- Calls `fcn_5_runExternal()` with:
  - File path
  - Root folder
  - Output folder
  - Backup folder
  - Logger
  - Database path and table
  - Configuration
- Wraps in try-catch block
- If exception occurs:
  - Logs error message with file path and exception details
  - Continues processing other files (doesn't fail entire scan)

## Usage Example
```php
fcn_4_recursiveGlob(
    './data/watchfolder',
    '*.{[Xx][Mm][Ll]}',
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
- Validates directory and extension pattern at start
- Individual file errors don't stop processing of other files
- Individual subdirectory errors don't stop processing of other directories
- Logs all errors with context for troubleshooting
- Uses try-catch blocks to isolate failures
- Continues processing even when individual operations fail

## Integration
- Called by `fcn_2_monitorFolder()` to initiate file discovery
- Recursively calls itself to handle nested directory structures
- Calls `fcn_5_runExternal()` for each file found
- Depth-first traversal ensures subdirectories processed before parent directory files
- Essential for handling organized CAD export structures with subfolders
