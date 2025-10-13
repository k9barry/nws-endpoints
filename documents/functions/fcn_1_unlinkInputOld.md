# fcn_1_unlinkInputOld Function Documentation

## Purpose
Cleans up old files from the input directory by removing files older than a specified time threshold. This prevents the watch folder from accumulating too many old files over time.

## Location
`src/functions/fcn_1_unlinkInputOld.php`

## Function Signature
```php
function fcn_1_unlinkInputOld(
    string $path,
    int $TimeAdjust,
    LoggerInterface $logger
): void
```

## Parameters

| Parameter | Type | Description |
|-----------|------|-------------|
| `$path` | string | Path to the directory to clean up |
| `$TimeAdjust` | int | Maximum age in seconds - files older than this will be deleted |
| `$logger` | LoggerInterface | Logger instance for logging cleanup operations |

## Return Value
- Returns `void` (no return value)

## Step-by-Step Process

### Step 1: Validate Directory
- Checks if the provided path is a valid directory using `is_dir()`
- If directory does not exist, logs an error and returns early
- This prevents attempting to read from non-existent directories

### Step 2: Initialize Counters
- Creates a `$removedCount` variable set to 0
- This tracks how many files are successfully removed during cleanup

### Step 3: Open Directory Handle
- Opens the directory using `opendir($path)`
- If directory cannot be opened, logs an error and returns
- The handle is used to iterate through directory contents

### Step 4: Iterate Through Files
- Uses `readdir()` in a while loop to read each file in the directory
- Skips special directory entries `.` (current) and `..` (parent)
- For each file:
  1. Constructs full file path using `DIRECTORY_SEPARATOR`
  2. Checks if entry is actually a file (not a subdirectory) using `is_file()`
  3. If not a file, continues to next entry

### Step 5: Check File Age
- Gets file modification time using `@filemtime($filePath)`
- If modification time cannot be retrieved, logs a warning and continues
- Calculates file age: `time() - $filelastmodified`
- Compares age against `$TimeAdjust` threshold

### Step 6: Delete Old Files
- If file age exceeds `$TimeAdjust`:
  1. Attempts to delete file using `@unlink($filePath)`
  2. If successful:
     - Increments `$removedCount`
     - Logs info message about removed file
  3. If deletion fails:
     - Logs error message with file path

### Step 7: Close Directory and Report
- Closes directory handle using `closedir($handle)`
- Logs final summary with total count of removed files
- Reports the time threshold used for cleanup

## Usage Example
```php
// Remove files older than 15 minutes (900 seconds) from watch folder
fcn_1_unlinkInputOld('./data/watchfolder', 900, $logger);
```

## Error Handling
- Uses `@` error suppression for `filemtime()` and `unlink()` to prevent PHP warnings
- Logs errors and warnings for:
  - Non-existent directories
  - Failed directory opens
  - Files with inaccessible modification times
  - Failed file deletions
- Continues processing remaining files even if individual operations fail

## Integration
- Called once at application startup in `src/run` before monitoring loop begins
- Uses the same `$TimeAdjust` value (900 seconds = 15 minutes) as incident filtering
- Ensures watch folder stays clean before processing new XML files
