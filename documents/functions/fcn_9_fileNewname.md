# fcn_9_fileNewname Function Documentation

## Purpose
Generates a unique filename by appending a counter suffix if the original filename already exists. Creates filenames like `file_0.txt`, `file_1.txt`, etc. until a non-existing filename is found.

## Location
`src/functions/fcn_9_fileNewname.php`

## Function Signature
```php
function fcn_9_fileNewname(string $path, string $filename): string
```

## Parameters

| Parameter | Type | Description |
|-----------|------|-------------|
| `$path` | string | The directory path where the file will be placed |
| `$filename` | string | The original filename to make unique |

## Return Value
- Returns `string` - A unique filename that doesn't already exist in the specified path

## Step-by-Step Process

### Step 1: Find File Extension
- Uses `strrpos($filename, '.')` to find last occurrence of dot
- This handles files with multiple dots (e.g., `backup.tar.gz`)
- Stores position in `$pos` variable
- Returns `false` if no dot found (files without extension)

### Step 2: Split Filename and Extension
- If dot was found (`$pos !== false`):
  - `$name` = everything before the dot using `substr($filename, 0, $pos)`
  - `$ext` = dot and everything after using `substr($filename, $pos)`
  - Example: `incident.xml` → name=`incident`, ext=`.xml`
- If no dot found:
  - `$name` = entire filename
  - `$ext` = empty string
  - Example: `README` → name=`README`, ext=``

### Step 3: Normalize Directory Path
- Uses `rtrim($path, DIRECTORY_SEPARATOR)` to remove trailing separator
- Ensures consistent path format
- Prevents double slashes in constructed path

### Step 4: Initialize Variables
- Constructs initial full path: `$path . DIRECTORY_SEPARATOR . $filename`
- Stores in `$newpath` variable
- Sets `$newname` to original filename
- Initializes `$counter` to 0
- These will be updated in the loop if file exists

### Step 5: Find Unique Filename (Loop)
- Enters `while (file_exists($newpath))` loop
- Continues as long as file exists at current path

#### Loop Iteration:
1. Constructs new filename: `$name . '_' . $counter . $ext`
   - Example: `incident_0.xml`, `incident_1.xml`, etc.
   - Counter is appended before extension
2. Updates `$newname` with new filename
3. Constructs full path: `$path . DIRECTORY_SEPARATOR . $newname`
4. Updates `$newpath` with new full path
5. Increments `$counter` for next iteration
6. Repeats until `file_exists($newpath)` returns false

### Step 6: Return Unique Filename
- Returns `$newname` (just the filename, not full path)
- This will be either:
  - Original filename if it didn't exist
  - Filename with counter suffix if original existed

## Usage Example
```php
// File doesn't exist
$unique = fcn_9_fileNewname('./data/archive', 'incident.xml');
// Returns: "incident.xml"

// File exists, counter 0 doesn't exist
$unique = fcn_9_fileNewname('./data/archive', 'incident.xml');
// Returns: "incident_0.xml"

// Files through counter 2 exist
$unique = fcn_9_fileNewname('./data/archive', 'incident.xml');
// Returns: "incident_3.xml"

// File without extension
$unique = fcn_9_fileNewname('./data/archive', 'README');
// Returns: "README_0" (if README exists)

// Multi-extension file
$unique = fcn_9_fileNewname('./data/archive', 'backup.tar.gz');
// Returns: "backup.tar_0.gz" (counter before last extension)
```

## Error Handling
- Handles files with and without extensions
- Works with multi-dot filenames correctly
- Always finds a unique name (counter can increment indefinitely)
- Never throws exceptions
- Safe for concurrent file operations (checks current filesystem state)

## Integration
- Called by `fcn_7_renameIfExists()` to generate unique filename
- Used when archiving processed XML files
- Prevents data loss from file overwrites
- Essential for handling:
  - Multiple incidents with same filename
  - Re-processing of files with same name
  - Concurrent processing of duplicate filenames
- Ensures complete audit trail in archive folder
