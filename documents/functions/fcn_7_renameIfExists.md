# fcn_7_renameIfExists Function Documentation

## Purpose
Generates a unique filename if the specified file already exists. Prevents file overwrites by creating a new filename with a counter suffix when processing files that might have duplicate names.

## Location
`src/functions/fcn_7_renameIfExists.php`

## Function Signature
```php
function fcn_7_renameIfExists(string $filename): string
```

## Parameters

| Parameter | Type | Description |
|-----------|------|-------------|
| `$filename` | string | Full path to the file to check and potentially rename |

## Return Value
- Returns `string` - The original filename if it doesn't exist, or a unique filename if it does

## Step-by-Step Process

### Step 1: Check if File Exists
- Uses `file_exists($filename)` to check if file already exists
- If file doesn't exist:
  - Returns original `$filename` immediately
  - No renaming needed
- This is the most common case - avoids unnecessary processing

### Step 2: Parse File Path Components
- Uses `pathinfo($filename)` to break path into components
- Returns associative array with keys:
  - `dirname` - Directory path
  - `basename` - Full filename with extension
  - `filename` - Filename without extension
  - `extension` - File extension without dot
- Stores result in `$arrayParts`

### Step 3: Extract Directory Path
- Calls `fcn_8_getValue($arrayParts, 'dirname', '.')`
- Safely retrieves directory path from parsed components
- If 'dirname' key missing or empty, defaults to '.' (current directory)
- Stores result in `$strFolder`

### Step 4: Extract Base Filename
- Calls `fcn_8_getValue($arrayParts, 'basename', 'file')`
- Safely retrieves full filename from parsed components
- If 'basename' key missing or empty, defaults to 'file'
- Stores result in `$basename`

### Step 5: Generate Unique Filename
- Constructs path by combining folder and separator
- Uses `rtrim($strFolder, DIRECTORY_SEPARATOR)` to remove trailing slash
- Adds `DIRECTORY_SEPARATOR` for cross-platform compatibility
- Calls `fcn_9_fileNewname($strFolder, $basename)` to generate unique name
- This function adds counter suffix (_0, _1, _2, etc.) until unique name found

### Step 6: Return New Filename
- Combines folder path and unique filename
- Returns complete path with unique filename
- Example: `/path/to/file.xml` becomes `/path/to/file_0.xml`

## Usage Example
```php
// File doesn't exist - returns original
$newPath = fcn_7_renameIfExists('./data/archive/incident.xml');
// Returns: "./data/archive/incident.xml"

// File exists - returns unique name
$newPath = fcn_7_renameIfExists('./data/archive/incident.xml');
// Returns: "./data/archive/incident_0.xml"

// File exists multiple times
$newPath = fcn_7_renameIfExists('./data/archive/incident.xml');
// Returns: "./data/archive/incident_1.xml"
```

## Error Handling
- Uses `fcn_8_getValue()` to safely access array keys with defaults
- Handles missing path components gracefully
- Never throws exceptions
- Always returns a valid filename path
- Delegates actual uniqueness checking to `fcn_9_fileNewname()`

## Integration
- Called by `fcn_5_runExternal()` twice:
  1. For output file path before processing
  2. For archive file path before moving file
- Prevents overwriting files when:
  - Multiple XML files processed simultaneously
  - XML files with same name from different times
  - Archive contains previously processed files with same name
- Essential for preserving all incident data without loss
