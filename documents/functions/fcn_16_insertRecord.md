# fcn_16_insertRecord Function Documentation

## Purpose
Inserts or updates incident record in the database from New World CAD XML data. Parses XML fields, cleans and sanitizes data, and stores complete incident information for notification processing and historical tracking.

## Location
`src/functions/fcn_16_insertRecord.php`

## Function Signature
```php
function fcn_16_insertRecord(
    PDO $db_conn,
    string $db_incident,
    SimpleXMLElement $xml,
    LoggerInterface $logger,
    string $agencies,
    string $jurisdictions,
    string $units
): void
```

## Parameters

| Parameter | Type | Description |
|-----------|------|-------------|
| `$db_conn` | PDO | Database connection (PDO instance) |
| `$db_incident` | string | Database table name for incident records |
| `$xml` | SimpleXMLElement | XML object containing New World CAD incident data |
| `$logger` | LoggerInterface | Logger instance for database operations |
| `$agencies` | string | Pipe-delimited agency information for categorization |
| `$jurisdictions` | string | Pipe-delimited jurisdiction data for routing |
| `$units` | string | Pipe-delimited unit information for response tracking |

## Return Value
- Returns `void` (no return value)
- Throws `PDOException` if database insertion fails
- Throws `InvalidArgumentException` if XML data is invalid

## Step-by-Step Process

### Step 1: Extract and Validate Required Fields
- Extracts `CallId` from XML and casts to string
- Extracts `CallNumber` from XML
- Extracts `ClosedFlag` from XML
- If `CallId` is empty:
  - Throws `InvalidArgumentException`
  - Prevents inserting records without identifier

### Step 2: Set Agency Type
- Uses pre-extracted `$agencies` parameter
- Stores in `$AgencyContexts_AgencyContext_AgencyType`
- Already processed by calling function (pipe-delimited, deduplicated)

### Step 3: Extract Create DateTime
- Gets `CreateDateTime` from XML
- Timestamp when incident was created in CAD system
- Used for time delta calculations and logging

### Step 4: Extract Call Types
- Loops through all `AgencyContext` elements
- For each context, extracts `CallType` field
- Concatenates with `|` separator
- Example result: "FIRE|MEDICAL|RESCUE"
- Stores in `$AgencyContexts_AgencyContext_CallType`

### Step 5: Extract Location and Incident Details
Uses null coalescing operator `??` for optional fields:
- `AlarmLevel` - Alarm level (1, 2, 3, etc.)
- `NatureOfCall` - Nature/description of call
- `Location->CommonName` - Building/landmark name
- `Location->FullAddress` - Street address
- `Location->State` - State code
- `Location->NearestCrossStreets` - Cross streets
- `Location->AdditionalInfo` - Additional location info
- `Location->FireOri` - Fire department ORI
- `Location->FireQuadrant` - Fire quadrant/district
- `Location->PoliceOri` - Police department ORI
- `Location->PoliceBeat` - Police beat number
- `Location->LatitudeY` - GPS latitude
- `Location->LongitudeX` - GPS longitude
- Defaults to empty string if field missing

### Step 6: Set Jurisdiction
- Uses pre-extracted `$jurisdictions` parameter
- Stores in `$Incidents_Incident_Jurisdiction`
- Already processed by calling function

### Step 7: Extract and Parse Radio Channel
- Uses pre-extracted `$units` parameter for unit numbers
- Searches for radio channel pattern using regex
- Pattern: `/FG-[1-9]/m` (matches FG-1 through FG-9)
- If matches found:
  - Joins matches with `|` separator
  - Stores in `$RadioChannel`
- Otherwise radio channel remains empty

### Step 8: Extract Incident Numbers
- Loops through all `Incident` elements
- For each incident, extracts `Number` field
- Concatenates with `|` separator
- Stores in `$Incidents_Incident_Number`
- Handles multiple incident numbers per call

### Step 9: Extract Narrative Text
- Loops through all `Narrative` elements
- For each narrative, extracts `Text` field
- Concatenates with `|` separator
- Stores in `$Narratives_Narrative_Text`
- Captures all narrative entries

### Step 10: Clean and Sanitize Data
Applies cleaning operations to text fields:

#### Call Type
- Trims whitespace
- Escapes single quotes: `str_replace("'", "''", ...)`
- SQL-safe string handling

#### Nature of Call
- Trims whitespace
- Converts to uppercase using `strtoupper()`
- Escapes single quotes
- Standardizes format

#### Location Fields
- Trims whitespace from:
  - Common name
  - Full address
  - Cross streets
  - Additional info
- Escapes single quotes in all
- Preserves original case

#### Narrative Text
- Removes extra whitespace with `preg_replace("/\s\s+/", ' ', ...)`
- Removes tabs, newlines, carriage returns
- Converts to uppercase
- Trims whitespace
- Escapes single quotes
- Results in clean, normalized text

### Step 11: Prepare INSERT Statement
Constructs SQL:
```sql
INSERT OR REPLACE INTO {table} (
    db_CallId, db_CallNumber, db_ClosedFlag, db_AgencyType, db_CreateDateTime,
    db_CallType, db_AlarmLevel, db_RadioChannel, db_NatureOfCall, db_CommonName,
    db_FullAddress, db_State, db_NearestCrossStreets, db_AdditionalInfo,
    db_FireOri, db_FireQuadrant, db_PoliceOri, db_PoliceBeat,
    db_LatitudeY, db_LongitudeX, db_UnitNumber, db_Incident_Number,
    db_Incident_Jurisdiction, db_Narrative_Text
) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
```

- `INSERT OR REPLACE` - SQLite-specific upsert
- Updates existing record if Call ID matches
- Inserts new record if Call ID doesn't exist
- 24 placeholders for 24 columns

### Step 12: Execute INSERT with Parameters
- Prepares statement using `$db_conn->prepare($sql)`
- Executes with array of 24 values in order
- All values bound as parameters (SQL injection safe)
- Returns success status in `$result`

### Step 13: Check Success
- If `$result` is true:
  - Logs success: "Record successfully inserted/updated for CallId: {$CallId}"
  - Operation completed successfully
- If `$result` is false:
  - Throws `PDOException` with Call ID
  - Indicates database operation failed

### Step 14: Handle Exceptions
Two catch blocks:

#### PDOException
- Database-specific errors
- Logs error with Call ID and exception message
- Re-throws exception for upstream handling

#### General Exception
- Any other exception type
- Logs error with Call ID and exception message  
- Wraps in `RuntimeException` with context
- Provides clear error message about incident record failure

## Usage Example
```php
// Load XML
$xml = simplexml_load_file('./data/watchfolder/incident.xml');

// Extract topic information
$agencies = "FIRE|POLICE";
$jurisdictions = "JURISDICTION_A";
$units = "E1|L2|M3";

// Insert or update record
fcn_16_insertRecord(
    $db_conn,
    'incidents',
    $xml,
    $logger,
    $agencies,
    $jurisdictions,
    $units
);
```

## Data Transformations

| Field | Transformation |
|-------|---------------|
| Call Type | Trim, escape quotes |
| Nature of Call | Trim, uppercase, escape quotes |
| Location text | Trim, escape quotes |
| Narrative | Trim, uppercase, normalize whitespace, escape quotes |
| Agency/Jurisdiction/Units | Pre-processed, pipe-delimited |

## Error Handling
- Validates required fields (Call ID)
- Uses try-catch for PDOException
- Uses try-catch for general exceptions
- Logs errors with Call ID context
- Re-throws exceptions for upstream handling
- Provides detailed error messages

## Integration
- Called by `fcn_13_recordReceived()` for:
  - New incidents (initial insert)
  - Updated incidents (replace existing record)
  - Old incidents that changed (database update without notification)
- Uses `INSERT OR REPLACE` for upsert behavior
- Primary key (Call ID) determines insert vs update
- Essential for maintaining current incident state in database
