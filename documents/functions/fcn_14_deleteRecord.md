# fcn_14_deleteRecord Function Documentation

## Purpose
Removes a specific incident record from the database when it's marked as closed. Called when New World CAD sends an incident with ClosedFlag = true, indicating the incident is resolved and should be removed from active monitoring.

## Location
`src/functions/fcn_14_deleteRecord.php`

## Function Signature
```php
function fcn_14_deleteRecord(
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
| `$CallId` | int\|string | New World CAD Call ID to delete from database |
| `$logger` | LoggerInterface | Logger instance for record deletion operations |

## Return Value
- Returns `bool`
  - `true` if record was successfully deleted
  - `false` if no record found to delete
- Throws `PDOException` if database deletion fails

## Step-by-Step Process

### Step 1: Prepare DELETE SQL Statement
- Constructs SQL: `"DELETE FROM {$db_incident} WHERE db_CallId = ?"`
- Uses parameterized query with `?` placeholder
- Table name interpolated directly (sanitized by application logic)
- Call ID passed as parameter (safe from SQL injection)

### Step 2: Prepare Statement
- Uses `$db_conn->prepare($sql)` to create prepared statement
- Returns PDOStatement object stored in `$stmt`
- Prepares statement for execution with parameter binding

### Step 3: Execute DELETE
- Calls `$stmt->execute([$CallId])` with Call ID as parameter
- Binds Call ID to `?` placeholder in SQL
- Executes DELETE against database
- Returns boolean success status stored in `$result`

### Step 4: Check Affected Rows
- Uses `$stmt->rowCount()` to get number of rows deleted
- If `$result` is true AND rowCount > 0:
  - At least one row was deleted (success)
  - Logs info message: "Deleted record {$CallId} from table {$db_incident}"
  - Returns `true`

### Step 5: Handle No Records Found
- If `$result` is true but rowCount is 0:
  - No matching record existed in database
  - Logs warning: "No record found to delete for CallId {$CallId}"
  - Returns `false` (not an error, just no-op)
  - This can happen if:
    - Record already deleted
    - Call ID never existed
    - Database was cleared

### Step 6: Handle Exceptions
- Catches `PDOException` if deletion fails
- Possible causes:
  - Database connection lost
  - Table doesn't exist
  - Disk I/O error
  - Permission issues
- Logs error message with Call ID, table name, and exception details
- Re-throws exception for upstream handling
- Allows calling code to decide how to respond

## Usage Example
```php
// Open connection
$db_conn = fcn_10_openConnection('./data/db/db.sqlite', $logger);

// Delete closed incident
$deleted = fcn_14_deleteRecord($db_conn, 'incidents', 12345, $logger);

if ($deleted) {
    echo "Incident 12345 removed from database";
} else {
    echo "Incident 12345 not found in database";
}
```

## When This Function Is Called

Called by `fcn_13_recordReceived()` when:
1. XML file is processed
2. `ClosedFlag` in XML is set to "true"
3. Indicates incident has been resolved/closed in CAD system

Typical incident lifecycle:
1. **New incident** - XML with ClosedFlag="false" → Insert to database
2. **Updates** - Additional XML files with changes → Update database
3. **Closed** - XML with ClosedFlag="true" → Delete from database

## Database Implications

### Why Delete Instead of Flag?
- Database only tracks **active** incidents
- Closed incidents no longer need monitoring
- Reduces database size and query complexity
- Archive contains historical XML files if needed

### Cascade Effects
- No foreign keys, so no cascade deletes
- Single-table design means simple deletion
- Other functions won't find deleted Call IDs
- `fcn_15_callIdExist()` will return false after deletion

## Error Handling
- Uses try-catch to handle PDOException
- Logs detailed error messages with context
- Re-throws exceptions for upstream handling
- Distinguishes between:
  - Successful deletion (returns true)
  - Record not found (returns false, logs warning)
  - Database error (throws exception, logs error)

## Integration
- Called exclusively by `fcn_13_recordReceived()` 
- Part of incident lifecycle management
- Keeps database focused on active incidents only
- Complemented by `fcn_22_removeOldRecords()` which removes old records by Call ID threshold
- Returns value allows calling code to track deletion status
