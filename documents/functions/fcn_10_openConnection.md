# fcn_10_openConnection Function Documentation

## Purpose
Opens a connection to the SQLite database for incident storage. Creates a properly configured PDO connection that will be used for storing and retrieving New World CAD incident data throughout the processing workflow.

## Location
`src/functions/fcn_10_openConnection.php`

## Function Signature
```php
function fcn_10_openConnection(string $db, LoggerInterface $logger): PDO
```

## Parameters

| Parameter | Type | Description |
|-----------|------|-------------|
| `$db` | string | Database file path (SQLite database file) |
| `$logger` | LoggerInterface | Logger instance for database connection operations |

## Return Value
- Returns `PDO` - Database connection object configured for SQLite
- Throws `PDOException` if connection fails

## Step-by-Step Process

### Step 1: Create PDO Connection
- Constructs SQLite DSN: `"sqlite:$db"`
- Creates new PDO instance with DSN
- Example: `new PDO("sqlite:./data/db/db.sqlite")`
- SQLite will create database file if it doesn't exist
- Stores connection in `$db_conn` variable

### Step 2: Set Error Mode
- Uses `setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION)`
- Configures PDO to throw exceptions on errors
- Benefits:
  - Automatic error handling
  - Detailed error messages
  - Stack traces for debugging
  - Consistent error handling across codebase
- Without this, PDO would silently fail on errors

### Step 3: Set Fetch Mode
- Uses `setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC)`
- Configures default fetch mode to return associative arrays
- Results will be arrays with column names as keys
- Example: `['db_CallId' => '12345', 'db_CallType' => 'Fire']`
- More convenient than numeric indexed arrays

### Step 4: Log Success
- Logs info message with database path
- Example: "Connection opened to database ./data/db/db.sqlite"
- Confirms successful connection establishment
- Helpful for troubleshooting connection issues

### Step 5: Return Connection
- Returns configured PDO connection object
- Caller can use this to execute queries
- Connection remains open until object destroyed

### Step 6: Handle Connection Failure (Exception)
- Catches `PDOException` if connection fails
- Logs error message with database path and exception details
- Re-throws exception for upstream handling
- Allows calling code to decide how to handle failure

## Usage Example
```php
// Open connection
$db_conn = fcn_10_openConnection('./data/db/db.sqlite', $logger);

// Use connection for queries
$stmt = $db_conn->prepare("SELECT * FROM incidents WHERE db_CallId = ?");
$stmt->execute([12345]);
$result = $stmt->fetchAll();

// Close connection (automatic when variable goes out of scope)
$db_conn = null;
```

## Database Configuration
The connection is configured with:
- **DSN**: `sqlite:{filepath}` - SQLite file-based database
- **Error Mode**: `ERRMODE_EXCEPTION` - Throws exceptions on errors
- **Fetch Mode**: `FETCH_ASSOC` - Returns associative arrays
- **Auto-create**: SQLite creates file if it doesn't exist
- **No authentication**: SQLite doesn't require username/password

## Error Handling
- Wraps connection in try-catch block
- Logs connection failures with database path
- Re-throws PDOException for upstream handling
- Detailed exception messages include:
  - Database file path
  - PDO error message
  - Stack trace (from exception)

## Integration
- Called by `fcn_5_runExternal()` before processing each file
- Connection used for:
  - Checking if incidents table exists (`fcn_11_tableExists`)
  - Creating incidents table if needed (`fcn_12_createIncidentsTable`)
  - Processing incident records (`fcn_13_recordReceived`)
- Connection closed in finally block of `fcn_5_runExternal()`
- New connection created for each file (not reused across files)
- Ensures clean state for each incident processing
