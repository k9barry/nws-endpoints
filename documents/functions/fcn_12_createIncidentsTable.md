# fcn_12_createIncidentsTable Function Documentation

## Purpose
Creates the incidents table in the SQLite database if it doesn't already exist. Defines the complete schema for storing New World CAD incident data including location, timing, agency information, unit assignments, and narrative details.

## Location
`src/functions/fcn_12_createIncidentsTable.php`

## Function Signature
```php
function fcn_12_createIncidentsTable(
    PDO $db_conn,
    string $db_incident,
    LoggerInterface $logger
): void
```

## Parameters

| Parameter | Type | Description |
|-----------|------|-------------|
| `$db_conn` | PDO | Database connection (PDO instance) |
| `$db_incident` | string | Database table name to create (e.g., 'incidents') |
| `$logger` | LoggerInterface | Logger instance for database schema operations |

## Return Value
- Returns `void` (no return value)
- Throws `PDOException` if table creation fails

## Database Schema

The function creates a table with the following columns:

| Column Name | Type | Description |
|-------------|------|-------------|
| `db_CallId` | INTEGER PRIMARY KEY | Unique identifier for the call/incident |
| `db_CallNumber` | INTEGER | CAD call number assigned by dispatch |
| `db_ClosedFlag` | TEXT | Whether incident is closed ("true"/"false") |
| `db_AgencyType` | TEXT | Agency type(s) handling incident (Fire/Police/EMS) |
| `db_CreateDateTime` | TEXT | Timestamp when incident was created |
| `db_CallType` | TEXT | Type of call (Medical, Fire, Traffic, etc.) |
| `db_AlarmLevel` | TEXT | Alarm level (1, 2, 3, etc.) for multi-alarm incidents |
| `db_RadioChannel` | TEXT | Radio channel(s) assigned for incident |
| `db_NatureOfCall` | TEXT | Nature/description of the call |
| `db_CommonName` | TEXT | Common name of location (building, landmark) |
| `db_FullAddress` | TEXT | Complete street address of incident |
| `db_State` | TEXT | State where incident occurred |
| `db_NearestCrossStreets` | TEXT | Cross streets near incident location |
| `db_AdditionalInfo` | TEXT | Additional location information |
| `db_FireOri` | TEXT | Fire department ORI (Originating Agency Identifier) |
| `db_FireQuadrant` | TEXT | Fire department quadrant/district |
| `db_PoliceOri` | TEXT | Police department ORI |
| `db_PoliceBeat` | TEXT | Police beat/patrol area |
| `db_LatitudeY` | TEXT | GPS latitude coordinate |
| `db_LongitudeX` | TEXT | GPS longitude coordinate |
| `db_UnitNumber` | TEXT | Unit(s) assigned to incident |
| `db_Incident_Number` | TEXT | Official incident number(s) |
| `db_Incident_Jurisdiction` | TEXT | Jurisdiction(s) handling incident |
| `db_Narrative_Text` | TEXT | Incident narrative/notes |

## Step-by-Step Process

### Step 1: Define SQL CREATE TABLE Statement
- Constructs SQL with `CREATE TABLE IF NOT EXISTS` clause
- `IF NOT EXISTS` prevents errors if table already exists
- Defines all 24 columns with appropriate data types
- Sets `db_CallId` as PRIMARY KEY for unique identification
- Uses TEXT type for most fields (SQLite best practice)
- Uses INTEGER for numeric identifiers

### Step 2: Execute SQL Statement
- Uses `$db_conn->exec($sql)` to execute DDL statement
- `exec()` is appropriate for DDL (doesn't return result set)
- More efficient than `query()` for schema operations
- Wrapped in try-catch block for error handling

### Step 3: Log Success
- Logs info message confirming table creation
- Message: "Created table {$db_incident} if it did not exist"
- Indicates operation completed successfully
- Helpful for tracking database initialization

### Step 4: Handle Exceptions
- Catches `PDOException` if table creation fails
- Possible causes:
  - Invalid SQL syntax
  - Permission issues
  - Disk full
  - Database corruption
- Logs error message with table name and exception details
- Re-throws exception for upstream handling

## Usage Example
```php
// Open connection
$db_conn = fcn_10_openConnection('./data/db/db.sqlite', $logger);

// Create table if needed
fcn_12_createIncidentsTable($db_conn, 'incidents', $logger);

// Table is now ready for use
$stmt = $db_conn->prepare("INSERT INTO incidents (db_CallId, db_CallType) VALUES (?, ?)");
$stmt->execute([12345, 'Fire']);
```

## Schema Design Decisions

### Primary Key
- `db_CallId` used as primary key
- Corresponds to New World CAD's unique call identifier
- INTEGER type for efficient indexing
- Automatically indexed by SQLite

### TEXT vs INTEGER
- Most fields use TEXT type for flexibility
- SQLite TEXT type can store any string data
- Numbers stored as TEXT for:
  - Call numbers with leading zeros
  - Coordinates with decimal precision
  - ORI codes that may contain letters

### Pipe-Delimited Fields
Several fields store multiple values separated by `|`:
- `db_AgencyType` - Multiple agencies (Fire|Police|EMS)
- `db_UnitNumber` - Multiple units (E1|L2|M3)
- `db_Incident_Jurisdiction` - Multiple jurisdictions
- Allows storage without complex joins or additional tables

### No Foreign Keys
- Single-table design for simplicity
- No relationships to other tables
- Self-contained incident records
- Easier backup and migration

## Error Handling
- Uses try-catch to handle PDOException
- Logs errors with table name and exception message
- Re-throws exception to notify calling code
- `IF NOT EXISTS` clause prevents errors on duplicate creation
- Safe to call multiple times

## Integration
- Called by `fcn_5_runExternal()` if `fcn_11_tableExists()` returns false
- Run once per file processing (but protected by IF NOT EXISTS)
- Ensures database schema ready before data insertion
- Called automatically on first file processed
- Safe to call on every file (no performance impact)
