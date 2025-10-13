# fcn_8_getValue Function Documentation

## Purpose
Safely retrieves a value from an array with default fallback and automatic trimming. Prevents errors when accessing array keys that might not exist and handles empty string values by returning the default instead.

## Location
`src/functions/fcn_8_getValue.php`

## Function Signature
```php
function fcn_8_getValue(
    array $array,
    mixed $index,
    null|string $default = ''
): ?string
```

## Parameters

| Parameter | Type | Description |
|-----------|------|-------------|
| `$array` | array | The array to retrieve the value from |
| `$index` | mixed | The array key/index to access (can be string or int) |
| `$default` | string\|null | Default value to return if key doesn't exist or value is empty (default: empty string) |

## Return Value
- Returns `string|null` - The trimmed value from the array, or the default value if key doesn't exist or value is empty

## Step-by-Step Process

### Step 1: Check if Key Exists
- Uses `isset($array[$index])` to check if the key exists in array
- `isset()` returns false if:
  - Key doesn't exist
  - Value is explicitly null
- If key doesn't exist:
  - Returns `$default` value immediately
  - Prevents "Undefined index" PHP notices
  - Safe access pattern for potentially missing keys

### Step 2: Retrieve and Trim Value
- Gets value from array using `$array[$index]`
- Applies `trim()` to remove leading/trailing whitespace:
  - Spaces
  - Tabs
  - Newlines
  - Carriage returns
- Stores trimmed value in `$value` variable

### Step 3: Check for Empty Value
- Uses `strlen($value) <= 0` to check if trimmed value is empty
- More reliable than `empty()` which treats '0' as empty
- If value length is zero:
  - Returns `$default` value
  - Treats empty strings as missing data
  - Ensures meaningful data is always returned

### Step 4: Return Valid Value
- If value exists and is not empty after trimming:
  - Returns the trimmed value
- This is the normal successful case

## Usage Example
```php
// Array with data
$data = ['name' => '  John Doe  ', 'age' => '', 'city' => 'Seattle'];

// Get existing value with whitespace
$name = fcn_8_getValue($data, 'name', 'Unknown');
// Returns: "John Doe" (trimmed)

// Get empty value with default
$age = fcn_8_getValue($data, 'age', 'N/A');
// Returns: "N/A" (empty string treated as missing)

// Get missing value with default
$country = fcn_8_getValue($data, 'country', 'USA');
// Returns: "USA" (key doesn't exist)

// Get value without default
$city = fcn_8_getValue($data, 'city');
// Returns: "Seattle"

// PathInfo usage (common pattern)
$parts = pathinfo('/path/to/file.xml');
$dir = fcn_8_getValue($parts, 'dirname', '.');
$file = fcn_8_getValue($parts, 'basename', 'file');
```

## Error Handling
- Never throws exceptions
- Returns default value for missing or empty keys
- Safe to use with arrays that may not have expected keys
- Handles null default values correctly
- Always returns a predictable result

## Integration
- Called by `fcn_7_renameIfExists()` to safely access pathinfo components:
  - Extract directory path with '.' fallback
  - Extract basename with 'file' fallback
- Used throughout codebase for safe array access
- Essential for handling XML data that may have optional fields
- Prevents PHP notices about undefined array keys
- Simplifies code by eliminating need for multiple `isset()` checks
