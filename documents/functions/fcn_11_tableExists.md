# fcn_11_tableExists Function Documentation

## Purpose
Checks if a specific table exists in the SQLite database. Uses proper SQLite system table query instead of trying to select from a potentially non-existent table. Essential for determining if database schema needs to be initialized.

## Location
`src/functions/fcn_11_tableExists.php`

## Function Signature
```php
function fcn_11_tableExists(
    PDO $db_conn,
    string $db_incident,
    LoggerInterface $logger
): bool
```

## Parameters

| Parameter | Type | Description |
|-----------|------|-------------|
| `$db_conn` | PDO | PDO instance connected to the database |
| `$db_incident` | string | Table name to search for (e.g., 'incidents') |
| `$logger` | LoggerInterface | Logger instance for database operations |

## Return Value
- Returns `bool`
  - `true` if table exists in database
  - `false` if table not found or error occurred

## Step-by-Step Process

### Step 1: Prepare SQL Query
- Uses SQLite system table: `sqlite_master`
- Query: `"SELECT name FROM sqlite_master WHERE type='table' AND name=?"`
- `sqlite_master` contains metadata about all database objects
- Filters for:
  - `type='table'` - Only look for tables (not indexes, views, etc.)
  - `name=?` - Match specific table name (parameterized for safety)

### Step 2: Prepare Statement
- Uses `$db_conn->prepare($sql)` to create prepared statement
- Parameterized query prevents SQL injection
- More efficient than building SQL string with concatenation
- Stores prepared statement in `$stmt`

### Step 3: Execute Query
- Calls `$stmt->execute([$db_incident])` with table name as parameter
- Binds table name to `?` placeholder
- Executes query against database
- SQLite returns matching rows from sqlite_master

### Step 4: Fetch Result
- Uses `$stmt->fetch()` to get first result row
- Returns associative array if table found
- Returns `false` if no matching table found
- Only one row needed (table names are unique)

### Step 5: Check Result and Log
- If `$result` is truthy (table found):
  - Logs info: "Table {$db_incident} exists"
  - Returns `true`
- If `$result` is false (table not found):
  - Logs info: "Table {$db_incident} not found"
  - Returns `false`

### Step 6: Handle Exceptions
- Catches `PDOException` if query fails
- Possible causes:
  - Database connection lost
  - Database file corrupted
  - Permission issues
- Logs error message with table name and exception details
- Returns `false` (assumes table doesn't exist)
- Doesn't re-throw exception (calling code can proceed)

## Usage Example
```php
// Open connection
$db_conn = fcn_10_openConnection('./data/db/db.sqlite', $logger);

// Check if table exists
if (fcn_11_tableExists($db_conn, 'incidents', $logger)) {
    // Table exists, proceed with operations
    echo "Table is ready";
} else {
    // Table doesn't exist, create it
    fcn_12_createIncidentsTable($db_conn, 'incidents', $logger);
}
```

## SQLite System Tables
SQLite maintains several system tables:
- **sqlite_master** - Main system table (used by this function)
  - Contains: tables, indexes, views, triggers
  - Columns: type, name, tbl_name, rootpage, sql
- **sqlite_sequence** - Auto-increment counters
- **sqlite_stat1** - Query optimizer statistics

This function specifically queries `sqlite_master` for table existence.

## Error Handling
- Uses try-catch to handle PDOException
- Logs errors without throwing exceptions
- Returns `false` on error (safe default)
- Calling code can create table if function returns false
- Prevents application crash from database errors

## Integration
- Called by `fcn_5_runExternal()` before processing each file
- If table doesn't exist:
  - Triggers call to `fcn_12_createIncidentsTable()`
  - Creates complete database schema
- If table exists:
  - Proceeds directly to incident processing
- Ensures database schema is ready before any data operations
- Run for every file processed (not just once at startup)
- Handles case where database file deleted during operation
