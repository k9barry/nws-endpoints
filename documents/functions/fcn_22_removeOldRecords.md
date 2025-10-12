# fcn_22_removeOldRecords Function Documentation

## Purpose
Maintains database size by keeping only the most recent 999 incident records. Deletes older incident records to prevent the database from growing too large while preserving recent incident history for notifications and reference.

## Location
`src/functions/fcn_22_removeOldRecords.php`

## Function Signature
```php
function fcn_22_removeOldRecords(
    PDO $db_conn,
    string $db_incident,
    int|string $CallId,
    LoggerInterface $logger
): int
```

## Parameters

| Parameter | Type | Description |
|-----------|------|-------------|
| `$db_conn` | PDO | Database connection (PDO instance) |
| `$db_incident` | string | Database table name containing incident records |
| `$CallId` | int\|string | Current Call ID - records with IDs less than (CallId - 999) will be deleted |
| `$logger` | LoggerInterface | Logger instance for database cleanup operations |

## Return Value
- Returns `int` - Number of records deleted
- Throws `PDOException` if database deletion fails

## Step-by-Step Process

### Step 1: Calculate Threshold
- Formula: `(int) $CallId - 999`
- Casts Call ID to integer for arithmetic
- Subtracts 999 to find cutoff point
- Example: Current Call ID 10500 → Threshold 9501
- Stores in `$threshold` variable

### Step 2: Check Threshold Validity
- Checks if `$threshold <= 0`
- If threshold is zero or negative:
  - Logs "No old records to delete (threshold: {value})"
  - Returns 0 (no records deleted)
  - Prevents deleting all records when Call IDs are low

### Step 3: Prepare DELETE Statement
- Constructs SQL: `"DELETE FROM {$db_incident} WHERE db_CallId < ?"`
- Uses less-than comparison (not less-than-or-equal)
- Keeps records with Call ID >= threshold
- Deletes records with Call ID < threshold
- Uses parameterized query for safety

### Step 4: Execute DELETE
- Prepares statement: `$db_conn->prepare($sql)`
- Executes with threshold parameter: `$stmt->execute([$threshold])`
- SQLite deletes all matching rows
- Returns success status in `$result`

### Step 5: Check Execution Success
- Verifies `$result` is true (execution succeeded)
- If false:
  - Throws `PDOException` with table name
  - Indicates database operation failed

### Step 6: Get Deleted Count
- Uses `$stmt->rowCount()` to get affected rows
- Returns number of records actually deleted
- May be 0 if no records met criteria
- Stores in `$deletedCount` variable

### Step 7: Log Results
- Logs info message with:
  - Count of deleted records
  - Table name
  - Threshold value used
- Example: "Deleted 250 old incidents from table incidents where Call ID < 9501"

### Step 8: Return Count
- Returns `$deletedCount` to caller
- Allows calling code to track cleanup activity
- Can be used for metrics or logging

### Step 9: Handle Exceptions
- Catches `PDOException` if deletion fails
- Possible causes:
  - Database connection lost
  - Table doesn't exist
  - Disk I/O error
  - Insufficient permissions
- Logs error message with exception details
- Re-throws exception for upstream handling

## Usage Example
```php
// After processing incident 10500
fcn_22_removeOldRecords($db_conn, 'incidents', 10500, $logger);
// Deletes incidents with Call ID < 9501
// Returns number deleted (e.g., 250)

// Early in system lifecycle (Call ID 500)
fcn_22_removeOldRecords($db_conn, 'incidents', 500, $logger);
// Threshold would be -499 (invalid)
// Returns 0, no records deleted
```

## Record Retention Logic

### How It Works
- **Keeps**: Last 999 incident records based on Call ID
- **Deletes**: Records with Call IDs older than current minus 999
- **Assumption**: Call IDs increment sequentially in CAD system

### Example Scenario
```
Current Call ID: 10500
Threshold: 10500 - 999 = 9501

Database contains:
- Call ID 9000 → DELETED (< 9501)
- Call ID 9200 → DELETED (< 9501)
- Call ID 9500 → DELETED (< 9501)
- Call ID 9501 → KEPT (>= 9501)
- Call ID 9800 → KEPT (>= 9501)
- Call ID 10500 → KEPT (>= 9501)

Result: Oldest 3 records deleted
```

### Why 999 Records?
- Balances history vs database size
- Typical CAD system generates 100-500 calls per day
- 999 records ≈ 2-10 days of history
- Sufficient for:
  - Active incident tracking
  - Recent incident comparison
  - Change detection
  - Troubleshooting

## Integration

### When Called
- Called by `fcn_21_sendMessage()` after notifications sent
- Runs for every notification sent
- Not called for:
  - Closed incidents (already deleted by fcn_14_deleteRecord)
  - Old incidents (no notification sent)
  - Database-only updates

### Frequency
- Potentially runs many times per minute during active periods
- Protected by threshold check (no-op if insufficient records)
- Lightweight operation (indexed primary key)

### Complementary Cleanup
Works alongside other cleanup mechanisms:

1. **fcn_14_deleteRecord()** - Removes closed incidents immediately
2. **fcn_18_unlinkArchiveOld()** - Removes old XML files (1 hour)
3. **fcn_22_removeOldRecords()** - Removes old incidents (999 records)

Combined effect:
- Database contains recent active incidents only
- Archive contains recent XML files only
- System maintains steady-state storage usage

## Performance Considerations

### Efficiency
- Uses indexed primary key (db_CallId) for fast deletion
- Single DELETE statement, not per-row operations
- SQLite handles bulk delete efficiently
- Typically completes in < 10ms

### Impact
- Minimal CPU usage
- Minimal disk I/O
- No table locking issues (SQLite write lock brief)
- Safe to run frequently

### Optimization
- Could add index on db_CallId if not already primary key
- Could batch deletions (only run every N calls)
- Current implementation prioritizes simplicity

## Error Handling
- Validates threshold before attempting deletion
- Uses try-catch for PDOException
- Logs errors with context (table name, threshold)
- Re-throws exceptions for upstream handling
- Returns count on success for tracking

## Limitations

### Assumptions
- Call IDs increment sequentially
- No gaps in Call ID sequence
- Call IDs not reused

### Edge Cases
- **Non-sequential IDs**: May retain more than 999 records
- **ID Gaps**: May delete more than expected if large gaps
- **ID Rollover**: Will delete all if Call ID resets to 0

### Future Enhancements
Could consider:
- Time-based retention instead of count-based
- Configurable retention count
- Archival to separate table instead of deletion
- Verification of Call ID sequence assumptions
