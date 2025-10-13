# fcn_15_callIdExist Function Documentation

## Purpose
Checks if a specific Call ID already exists in the incident database. Used to determine whether an incident is new (requiring full notification) or an update to an existing incident (requiring change detection and targeted notifications).

## Location
`src/functions/fcn_15_callIdExist.php`

## Function Signature
```php
function fcn_15_callIdExist(
    PDO $db_conn,
    string $db_incident,
    int|string $CallId,
    LoggerInterface $logger
): bool
```

## Parameters

| Parameter | Type | Description |
|-----------|------|-------------|
| `$db_conn` | PDO | Database connection (PDO instance) |
| `$db_incident` | string | Database table name for incident records |
| `$CallId` | int\|string | New World CAD Call ID to check for existence |
| `$logger` | LoggerInterface | Logger instance for database query operations |

## Return Value
- Returns `bool`
  - `true` if Call ID exists in database
  - `false` if Call ID doesn't exist
- Throws `PDOException` if database query fails

## Step-by-Step Process

### Step 1: Prepare SQL Query
- Constructs SQL: `"SELECT COUNT(1) FROM {$db_incident} WHERE db_CallId = ? LIMIT 1"`
- Uses `COUNT(1)` for efficiency (counts rows without retrieving data)
- Limits to 1 row (optimization, only need to know if any exist)
- Uses parameterized query with `?` placeholder for safety

### Step 2: Prepare Statement
- Uses `$db_conn->prepare($sql)` to create prepared statement
- Returns PDOStatement object stored in `$stmt`
- Prepared statement ready for parameter binding and execution

### Step 3: Execute Query
- Calls `$stmt->execute([$CallId])` with Call ID as parameter
- Binds Call ID to `?` placeholder in SQL
- Executes SELECT against database
- Returns boolean indicating execution success

### Step 4: Fetch Result
- Uses `$stmt->fetchColumn()` to get first column of first row
- Returns the COUNT value directly (integer)
- More efficient than `fetch()` for single values
- Stores result in `$result` variable

### Step 5: Convert to Boolean
- Casts result to boolean: `$exists = (bool) $result`
- Count of 0 → `false` (doesn't exist)
- Count of 1+ → `true` (exists)
- Stores in `$exists` variable for clarity

### Step 6: Log Result
- If `$exists` is true:
  - Logs info: "Call ID {$CallId} exists in database"
- If `$exists` is false:
  - Logs info: "Call ID {$CallId} does not exist in database"
- Helps track database lookup operations

### Step 7: Return Existence Status
- Returns `$exists` boolean value
- Caller uses this to determine processing path:
  - `false` → New incident, insert and notify all
  - `true` → Existing incident, detect changes and notify selectively

### Step 8: Handle Exceptions
- Catches `PDOException` if query fails
- Possible causes:
  - Database connection lost
  - Table doesn't exist
  - Database corruption
  - Permission issues
- Logs error message with Call ID, table name, and exception details
- Re-throws exception for upstream handling

## Usage Example
```php
// Open connection
$db_conn = fcn_10_openConnection('./data/db/db.sqlite', $logger);

// Check if incident exists
if (fcn_15_callIdExist($db_conn, 'incidents', 12345, $logger)) {
    // Existing incident - check for changes
    echo "Updating existing incident 12345";
    // Load previous data, compare, send targeted notifications
} else {
    // New incident - full processing
    echo "Processing new incident 12345";
    // Insert to database, notify all relevant topics
}
```

## Query Optimization

### Why COUNT(1) Instead of COUNT(*)
- `COUNT(1)` counts rows by checking for any non-null value
- `COUNT(*)` counts all rows including null columns
- Performance difference negligible in SQLite, but `COUNT(1)` is clearer intent
- Both return same result for existence check

### Why LIMIT 1
- Only need to know IF record exists, not how many
- SQLite can stop scanning after finding first match
- Improves performance on large tables
- Makes query intent clear (existence check, not count)

### Why Not SELECT *
- Don't need any column data, just existence
- `COUNT(1)` returns single integer
- Much faster than retrieving all columns
- Reduces memory usage

## Integration
- Called by `fcn_13_recordReceived()` to determine processing path
- Critical decision point in incident workflow:

```
XML File → Load XML → Extract Call ID
                          ↓
                   fcn_15_callIdExist()
                          ↓
        ┌─────────────────┴─────────────────┐
        ↓                                   ↓
    Returns false                      Returns true
    (New incident)                     (Existing incident)
        ↓                                   ↓
   Insert to DB                        Load from DB
   Notify all topics                   Compare fields
                                       Detect changes
                                       Notify selectively
```

## Error Handling
- Uses try-catch to handle PDOException
- Logs errors with Call ID context
- Re-throws exceptions to notify calling code
- Allows application to handle database failures appropriately
- Error logs include table name and exception message for debugging
