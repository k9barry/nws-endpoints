# fcn_6_recursiveMkdir Function Documentation

## Purpose
Recursively creates directories similar to the Unix `mkdir -p` command. Ensures all parent directories exist before creating the target directory. Used to set up folder structure for processing New World CAD files.

## Location
`src/functions/fcn_6_recursiveMkdir.php`

## Function Signature
```php
function fcn_6_recursiveMkdir(
    string $dest,
    ?LoggerInterface $logger = null,
    int $permissions = 0755,
    bool $recursive = true
): bool
```

## Parameters

| Parameter | Type | Description |
|-----------|------|-------------|
| `$dest` | string | Destination directory path to create |
| `$logger` | LoggerInterface\|null | Optional logger instance for directory creation operations |
| `$permissions` | int | Directory permissions in octal (default: 0755) |
| `$recursive` | bool | Whether to recursively create parent directories (default: true) |

## Return Value
- Returns `bool`
  - `true` if directory exists or was successfully created
  - `false` if directory creation failed

## Step-by-Step Process

### Step 1: Normalize Path
- Removes trailing directory separator using `rtrim($dest, DIRECTORY_SEPARATOR)`
- Ensures consistent path format
- Prevents issues with paths ending in `/` or `\`

### Step 2: Check if Directory Already Exists
- Uses `is_dir($dest)` to check if directory exists
- If directory exists, returns `true` immediately
- Avoids unnecessary directory creation attempts
- Most efficient path when directory already present

### Step 3: Check Parent Directory (Recursive Mode)
- Gets parent directory path using `dirname($dest)`
- Checks if parent exists using `is_dir($parent)`
- Only proceeds if `$recursive` is true

### Step 4: Create Parent Directory (If Needed)
- Recursively calls `fcn_6_recursiveMkdir()` for parent directory
- Passes same logger, permissions, and recursive flag
- If parent creation fails:
  - Logs error message if logger available
  - Returns `false` to indicate failure
- This ensures entire directory tree is created bottom-up

### Step 5: Log Directory Creation Attempt
- Logs info message with directory path if logger available
- Includes permissions in octal format using `decoct($permissions)`
- Example: "Creating directory: /path/to/dir with permissions 755"
- Helps track directory creation for debugging

### Step 6: Attempt Directory Creation
- Uses `@mkdir($dest, $permissions, false)` to create directory
- `@` suppresses PHP warnings/errors
- `$permissions` sets directory mode (e.g., 0755)
- Third parameter `false` prevents PHP's built-in recursion (we handle it ourselves)

### Step 7: Set Permissions Explicitly
- If `mkdir()` succeeds:
  - Calls `chmod($dest, $permissions)` to ensure correct permissions
  - This handles cases where umask may have affected mkdir
  - Guarantees exact permissions requested
  - Returns `true` to indicate success

### Step 8: Handle Creation Failure
- If `mkdir()` fails:
  - Logs error message with directory path if logger available
  - Returns `false` to indicate failure
- Caller can check return value to handle failure

## Usage Example
```php
// Create single directory
$success = fcn_6_recursiveMkdir('./data/output', $logger);

// Create nested directories with custom permissions
$success = fcn_6_recursiveMkdir(
    './data/archive/2024/01',
    $logger,
    0775,  // Custom permissions
    true   // Recursive mode
);

// Create directory without logging
$success = fcn_6_recursiveMkdir('./data/temp', null);
```

## Error Handling
- Returns boolean success/failure status
- Logs errors if logger provided
- Handles missing parent directories automatically
- Uses `@` suppression to prevent PHP warnings
- Gracefully handles permission issues
- Does not throw exceptions - returns false instead

## Integration
- Called by `fcn_2_monitorFolder()` to create output and backup folders
- Called by `fcn_5_runExternal()` to create:
  - Output directory for file processing
  - Archive directory for processed files
- Essential for maintaining directory structure that matches watch folder organization
- Ensures application can create required directories on first run
