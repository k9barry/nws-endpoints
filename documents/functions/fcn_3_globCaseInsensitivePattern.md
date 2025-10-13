# fcn_3_globCaseInsensitivePattern Function Documentation

## Purpose
Builds a case-insensitive glob pattern for matching file extensions. This allows the application to find files regardless of how the extension is capitalized (e.g., .XML, .xml, .Xml all match).

## Location
`src/functions/fcn_3_globCaseInsensitivePattern.php`

## Function Signature
```php
function fcn_3_globCaseInsensitivePattern(array $arr_extensions): string
```

## Parameters

| Parameter | Type | Description |
|-----------|------|-------------|
| `$arr_extensions` | array | Array of file extensions without the dot (e.g., ['jpg', 'gif', 'xml']) |

## Return Value
- Returns `string` - A glob pattern for case-insensitive file matching
- Example: `*.{[Xx][Mm][Ll]}` for xml extension
- Returns `*` if no valid extensions provided

## Step-by-Step Process

### Step 1: Check for Empty Input
- Checks if `$arr_extensions` array is empty
- If empty, returns wildcard `*` to match all files
- This provides safe fallback behavior

### Step 2: Initialize Pattern Array
- Creates empty `$patterns` array to store individual extension patterns
- This will hold one pattern per extension

### Step 3: Process Each Extension
For each extension in the input array:

#### 3.1: Clean Extension String
- Uses `trim($ext, " .")` to remove leading/trailing spaces and dots
- Skips empty extensions by checking `if ($ext === '')`
- This handles malformed input gracefully

#### 3.2: Build Case-Insensitive Pattern
- Splits extension by `.` to support multi-part extensions (e.g., `tar.gz`)
- For each part after the first, adds literal dot `\\.` to pattern
- For each character in the part:
  1. Creates character class with both upper and lower case
  2. Example: 'x' becomes '[Xx]'
  3. Example: 'm' becomes '[Mm]'
  4. Builds complete pattern character by character

#### 3.3: Store Pattern
- Appends completed pattern to `$patterns` array
- Each extension gets its own complete pattern

### Step 4: Validate Patterns
- Checks if `$patterns` array is empty after processing
- If no valid patterns were created, returns `*` wildcard
- Handles case where all extensions were invalid or empty

### Step 5: Format Final Pattern
- Checks count of patterns in array

#### Single Extension
- If only one pattern: returns `*.` + pattern
- Example: `*.{[Xx][Mm][Ll]}`
- No braces needed for single extension

#### Multiple Extensions
- Wraps patterns in `*.{pattern1,pattern2}` format
- Uses `implode(',', $patterns)` to join with commas
- Example: `*.{[Jj][Pp][Gg],[Gg][Ii][Ff]}`
- Glob braces allow matching any of the listed patterns

## Usage Example
```php
// Single extension
$pattern = fcn_3_globCaseInsensitivePattern(['xml']);
// Returns: "*.{[Xx][Mm][Ll]}"

// Multiple extensions
$pattern = fcn_3_globCaseInsensitivePattern(['jpg', 'gif', 'png']);
// Returns: "*.{[Jj][Pp][Gg],[Gg][Ii][Ff],[Pp][Nn][Gg]}"

// Multi-part extension
$pattern = fcn_3_globCaseInsensitivePattern(['tar.gz']);
// Returns: "*.{[Tt][Aa][Rr]\.[Gg][Zz]}"
```

## Error Handling
- Gracefully handles empty input arrays - returns `*`
- Skips invalid/empty extension strings
- Always returns a valid glob pattern
- Never throws exceptions

## Integration
- Called by `fcn_2_monitorFolder()` to generate file matching pattern
- Result is passed to `fcn_4_recursiveGlob()` for file discovery
- Allows flexible file extension matching without case sensitivity concerns
- Essential for handling CAD exports that may vary in filename casing
