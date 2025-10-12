# fcn_20_DeltaTime Function Documentation

## Purpose
Calculates the time difference between incident creation and current time. Used to determine how fresh an incident is for notification purposes and to filter out incidents that are too old to be relevant.

## Location
`src/functions/fcn_20_DeltaTime.php`

## Function Signature
```php
function fcn_20_deltaTime(string $CreateDateTime): int
```

## Parameters

| Parameter | Type | Description |
|-----------|------|-------------|
| `$CreateDateTime` | string | Incident creation timestamp from New World CAD |

## Return Value
- Returns `int` - Time difference in seconds between now and incident creation
- Always returns non-negative value (minimum 0)
- Throws `InvalidArgumentException` if timestamp is invalid

## Step-by-Step Process

### Step 1: Validate Input
- Checks if `$CreateDateTime` is not empty using `empty()`
- If empty:
  - Throws `InvalidArgumentException` with message "CreateDateTime cannot be empty"
  - Prevents processing invalid input

### Step 2: Get Current Time
- Uses `time()` to get current Unix timestamp
- More efficient than `strtotime("now")`
- Returns integer seconds since Unix epoch (1970-01-01 00:00:00 UTC)
- Stores in `$Now` variable

### Step 3: Parse Incident Time
- Uses `strtotime($CreateDateTime)` to convert timestamp string
- Accepts various date/time formats:
  - ISO 8601: "2024-01-15T14:30:00"
  - SQL format: "2024-01-15 14:30:00"
  - Human readable: "January 15, 2024 2:30pm"
- Returns Unix timestamp (integer seconds since epoch)
- Stores in `$IncidentTime` variable

### Step 4: Validate Parse Result
- Checks if `strtotime()` returned `false`
- `false` indicates parsing failure (invalid format)
- If parsing failed:
  - Throws `InvalidArgumentException` with message including the invalid timestamp
  - Prevents calculation with invalid data

### Step 5: Calculate Time Difference
- Subtracts incident time from current time: `$Now - $IncidentTime`
- Result is elapsed time in seconds
- Positive value = incident in past (normal)
- Negative value = incident in future (possible clock skew)

### Step 6: Ensure Non-Negative Result
- Uses `max(0, $Now - $IncidentTime)` to ensure result >= 0
- Prevents negative values from clock synchronization issues
- If incident timestamp is in future (clock skew), returns 0
- Returns final delta in seconds

## Usage Example
```php
// Calculate time since incident creation
$createTime = "2024-01-15 14:30:00";
$delta = fcn_20_deltaTime($createTime);

// Delta is in seconds
echo "Incident is {$delta} seconds old\n";

// Check if incident is recent (< 15 minutes)
if ($delta < 900) {
    echo "Recent incident - send notification\n";
} else {
    echo "Old incident - skip notification\n";
}

// Handle various formats
$delta1 = fcn_20_deltaTime("2024-01-15T14:30:00");  // ISO 8601
$delta2 = fcn_20_deltaTime("2024-01-15 14:30:00");  // SQL format
$delta3 = fcn_20_deltaTime("January 15, 2024");     // Human readable
```

## Time Format Compatibility

Accepted by `strtotime()`:
- **ISO 8601**: "2024-01-15T14:30:00Z"
- **SQL Format**: "2024-01-15 14:30:00"
- **Unix Timestamp**: "1705329000"
- **Relative**: "2 hours ago", "-15 minutes"
- **Human Readable**: "January 15, 2024 2:30pm"

New World CAD typically uses ISO 8601 or SQL format.

## Integration

### Used By
- Called by `fcn_13_recordReceived()` to check incident freshness
- Result passed to `fcn_21_sendMessage()` for notification display

### Decision Logic in fcn_13_recordReceived()
```php
$delta = fcn_20_deltaTime($xml->CreateDateTime);

// Check time threshold (default 900 seconds = 15 minutes)
if ($delta < $config['timeAdjust']) {
    // Incident is recent - send notifications
    fcn_21_sendMessage(..., $delta, ...);
} else {
    // Incident is too old - update database only, no notifications
    fcn_16_insertRecord(...);
}
```

### Why 15 Minutes (900 seconds)?
- Prevents notifications for stale incidents
- CAD exports may be delayed
- Handles XML file processing backlogs
- Avoids notification storms on startup
- Configurable via `$config['timeAdjust']`

## Error Handling
- Throws `InvalidArgumentException` for empty input
- Throws `InvalidArgumentException` for invalid timestamp format
- Never returns negative values (uses `max(0, ...)`)
- Provides clear error messages with problematic input

## Clock Synchronization Considerations

### Potential Issues
- **CAD server time** vs **Application server time**
- Clock drift between systems
- Timezone differences
- Daylight saving time transitions

### Mitigations
- `max(0, ...)` prevents negative deltas from future timestamps
- 15-minute threshold provides buffer for minor clock differences
- ISO 8601 with timezone recommended for CAD exports
- Regular NTP synchronization recommended for both systems

## Performance
- Extremely fast operation (< 1ms)
- No database access
- Simple arithmetic operations
- Called once per XML file processed
- Negligible performance impact
