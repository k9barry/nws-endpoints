# fcn_18_unlinkArchiveOld Function Documentation

## Purpose
Cleans up old files from the archive directory to manage disk space. Removes processed incident XML files older than 1 hour (3600 seconds) to prevent the archive folder from accumulating too many files over time.

## Location
`src/functions/fcn_18_unlinkArchiveOld.php`

## Function Signature
```php
function fcn_18_unlinkArchiveOld(string $path, LoggerInterface $logger): void
```

## Parameters

| Parameter | Type | Description |
|-----------|------|-------------|
| `$path` | string | Path to the archive directory to clean up |
| `$logger` | LoggerInterface | Logger instance for archive cleanup operations |

## Return Value
- Returns `void` (no return value)

## Step-by-Step Process

### Step 1: Open Directory
- Uses `opendir($path)` to get directory handle
- Stores handle in `$handle` variable
- If directory cannot be opened, function returns silently
- No error handling if opendir fails (relies on caller validation)

### Step 2: Iterate Through Directory Entries
- Uses `readdir($handle)` in while loop
- Reads each file/directory entry one at a time
- Continues until `readdir()` returns `false` (no more entries)
- Stores current entry name in `$file` variable

### Step 3: Get File Modification Time
- Constructs full file path: `$path . "/" . $file`
- Gets modification time using `filemtime()`
- Stores timestamp in `$filelastmodified` variable
- Note: No validation that entry is a file (processes all entries)

### Step 4: Calculate File Age
- Gets current time using `time()`
- Calculates age: `time() - $filelastmodified`
- Compares age against 3600 seconds (1 hour)
- Age threshold defined in code comment but not enforced:
  - Comment says "3 days * 24 hours * 3600 seconds"
  - Actual code uses 3600 (1 hour)

### Step 5: Check Age Threshold
- If `(time() - $filelastmodified) > 3600`:
  - File is older than 1 hour
  - Proceed to deletion check
- Otherwise:
  - File is recent, skip to next entry

### Step 6: Filter Special Entries
- Checks if `$file != "."` AND `$file != ".."`
- Skips current directory (`.`) and parent directory (`..`) references
- These are special filesystem entries that should never be deleted

### Step 7: Delete Old File
- Uses `unlink($path . "/" . $file)` to delete the file
- If deletion successful:
  - Logs info: "File {$file} removed from {$path}"
- If deletion fails:
  - No error handling, fails silently
  - May leave old files if permissions insufficient

### Step 8: Close Directory Handle
- Uses `closedir($handle)` to close directory
- Frees system resources
- Completes directory iteration

## Usage Example
```php
// Clean up archive folder
fcn_18_unlinkArchiveOld('./data/archive', $logger);

// Files older than 1 hour will be deleted
// Recent files (< 1 hour) will be preserved
```

## Timing Configuration

### Current Behavior
- **Threshold**: 3600 seconds (1 hour)
- **When Run**: After each file processed
- **Frequency**: Every file triggers cleanup

### Code Comment vs Implementation
```php
// Comment says: "3 days * 24 hours in a day * 3600 seconds per hour"
// This would be: 3 * 24 * 3600 = 259,200 seconds
// Actual code: if ((time() - $filelastmodified) > 3600)
// This is: 1 hour = 3,600 seconds
```

**Discrepancy**: Comment suggests 3 days but code implements 1 hour

## Limitations and Issues

### No Error Handling
- Silent failure if directory doesn't exist
- Silent failure if `opendir()` fails
- Silent failure if `unlink()` fails
- No validation that path is a directory

### Deletes All Old Entries
- No file type checking
- Will attempt to delete directories if they exist
- May fail on subdirectories (unlink doesn't remove dirs)
- No recursion for nested structures

### Fixed Threshold
- Hardcoded 3600 second threshold
- Not configurable via parameters
- Comment suggests different value than code

### Path Construction
- Uses forward slash `/` instead of `DIRECTORY_SEPARATOR`
- May cause issues on Windows systems
- Other functions use proper cross-platform separators

## Integration
- Called by `fcn_5_runExternal()` after database operations
- Runs for every XML file processed
- Prevents archive folder from growing indefinitely
- Keeps storage manageable for long-running deployments
- Complements database cleanup (fcn_22_removeOldRecords)

## Disk Space Management Strategy

The application manages disk space at two levels:

1. **Archive Files** (this function)
   - Deletes XML files older than 1 hour
   - Keeps recent files for troubleshooting
   - Prevents disk full from XML accumulation

2. **Database Records** (fcn_22_removeOldRecords)
   - Keeps last 999 incident records
   - Maintains operational data
   - Prevents database bloat

## Potential Improvements

To address current limitations:
1. Add directory existence check before `opendir()`
2. Add error handling for failed operations
3. Verify entries are files before attempting delete
4. Use `DIRECTORY_SEPARATOR` for cross-platform compatibility
5. Make threshold configurable via parameter or config
6. Match code to comment (clarify intended retention period)
7. Add subdirectory handling if archive has nested structure
