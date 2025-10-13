# fcn_13_recordReceived Function Documentation

## Purpose
Main processing function for New World CAD incident records. Parses XML data, extracts agency/jurisdiction/unit information, determines if record is new or updated, and triggers appropriate notification workflows. Handles complete incident lifecycle including creation, updates, and closure.

## Location
`src/functions/fcn_13_recordReceived.php`

## Function Signature
```php
function fcn_13_recordReceived(
    mixed $db_conn,
    string $db_incident,
    string $strInFile,
    LoggerInterface $logger,
    array $config
): void
```

## Parameters

| Parameter | Type | Description |
|-----------|------|-------------|
| `$db_conn` | mixed | Database connection (PDO instance) |
| `$db_incident` | string | Database table name for incident records |
| `$strInFile` | string | Full path to the XML file to process |
| `$logger` | LoggerInterface | Logger instance for record processing operations |
| `$config` | array | Configuration array containing notification and timing settings |

## Return Value
- Returns `void` (no return value)

## Step-by-Step Process

### Step 1: Load XML File
- Uses `simplexml_load_file($strInFile)` to parse XML
- Terminates with `die()` if XML parsing fails
- Stores SimpleXMLElement object in `$xml` variable
- Logs successful XML reading

### Step 2: Extract Agency Information
- Loops through `$xml->AgencyContexts->AgencyContext` elements
- Gets count using `$xml->AgencyContexts->AgencyContext->count()`
- For each agency context:
  1. Extracts `AgencyType` field (Fire, Police, EMS, etc.)
  2. Appends to `$agencies` string with `|` separator
- Removes duplicates using `array_unique(explode("|", $agencies))`
- Joins back with `implode("|", ...)` for final agency list

### Step 3: Extract Jurisdiction Information
- Loops through `$xml->Incidents->Incident` elements
- Gets count of incident elements
- For each incident:
  1. Extracts `Jurisdiction` field
  2. Appends to `$jurisdictions` string with `|` separator
- Removes duplicates using same technique as agencies
- Creates pipe-delimited list of jurisdictions

### Step 4: Extract Unit Information
- Loops through `$xml->AssignedUnits->Unit` elements
- Gets count of assigned units
- For each unit:
  1. Extracts `UnitNumber` field (unit identifier)
  2. Appends to `$units` string with `|` separator
- Does NOT remove duplicates (units may be listed multiple times intentionally)

### Step 5: Build Topic List from XML
- Combines all three: `$agencies . "|" . $jurisdictions . "|" . $units`
- Explodes by `|` and removes duplicates with `array_unique()`
- Stores in `$arr_Topics_Xml` array
- These are all potential notification topics from current XML

### Step 6: Calculate Time Delta
- Calls `fcn_20_deltaTime($xml->CreateDateTime)`
- Returns seconds between incident creation and current time
- Used to filter out old incidents
- Stored in `$delta` variable

### Step 7: Handle Closed Incidents
- Checks if `$xml->ClosedFlag == "true"`
- If incident is closed:
  1. Logs that record will be removed
  2. Calls `fcn_14_deleteRecord()` to remove from database
  3. Returns (no further processing)
- Removes resolved incidents from active tracking

### Step 8: Handle New Incidents
- Checks if Call ID exists using `fcn_15_callIdExist()`
- If record does NOT exist in database (new incident):
  1. Logs "New record to enter into the DB"
  2. Calls `fcn_16_insertRecord()` to add to database
  3. Calls `fcn_21_sendMessage()` with:
     - All topics from XML
     - `$resendAll = 0` (only to relevant topics)
  4. Returns (processing complete)

### Step 9: Handle Existing Incidents (Updates)
- If record already exists, need to detect what changed
- Logs "Record exists in DB - gathering topic changes"

#### 9.1: Load Existing Data from Database
- Prepares SQL: `SELECT * FROM $db_incident WHERE db_CallId = ?`
- Executes with Call ID parameter
- Fetches all columns as associative array
- Uses `extract()` to create variables from array keys
  - Creates `$db_AgencyType`, `$db_CallType`, etc.

#### 9.2: Build Database Topic List
- Extracts topics from database fields:
  - `$topics_arrDb_Agency` from `$db_AgencyType`
  - `$topics_arrDb_Jurisdiction` from `$db_Incident_Jurisdiction`
  - `$topics_arrDb_Unit` from `$db_UnitNumber`
- Merges all three arrays into `$arr_Topics_Db`
- These are topics from previous version of incident

#### 9.3: Find New Topics (New Units Dispatched)
- Uses `array_diff($arr_Topics_Xml, $arr_Topics_Db)`
- Finds topics in XML that weren't in database
- Joins with `implode("|", $topics)`
- Initializes flags: `$saveToDb = 0`, `$resendAll = 0`
- If new topics found:
  - Logs "New units dispatched"
  - Sets `$saveToDb = 1` to trigger database update

#### 9.4: Check for Call Type Changes
- Loops through agency contexts to build current call type string
- Compares with `$db_CallType` from database
- If different:
  - Logs call type change
  - Sets `$saveToDb = 1`
  - Sets `$resendAll = 1` (notify all topics, not just new ones)

#### 9.5: Check for Location Changes
- Compares `$xml->Location->FullAddress` with `$db_FullAddress`
- If different:
  - Logs address change
  - Sets `$saveToDb = 1`
  - Sets `$resendAll = 1` (important update, notify everyone)

#### 9.6: Check for Alarm Level Increase
- Compares `$xml->AlarmLevel` with `$db_AlarmLevel`
- Only triggers if NEW alarm level is HIGHER (escalation)
- If alarm increased:
  - Logs alarm level increase
  - Sets `$saveToDb = 1`
  - Sets `$resendAll = 1` (critical update, notify everyone)

### Step 10: Process Updates
- If `$saveToDb` flag is set (something changed):

#### 10.1: Check Time Delta
- Compares `$delta` with `$config['timeAdjust']`
- If incident is too old (delta >= 900 seconds):
  - Logs "Time delta is too high"
  - Updates database with `fcn_16_insertRecord()`
  - Does NOT send notifications
  - Prevents notifications for stale incidents

#### 10.2: Send Notifications for Recent Incidents
- If incident is recent (delta < 900 seconds):
  - Logs time delta and threshold
  - Updates database with `fcn_16_insertRecord()`
  - Calls `fcn_21_sendMessage()` with:
    - Topics (new or all, depending on `$resendAll`)
    - `$resendAll` flag (0 for new topics only, 1 for all topics)
  - Sends notifications via ntfy and/or Pushover

### Step 11: Handle No Changes
- If `$saveToDb` flag NOT set:
  - Logs "saveToDb flag not set - nothing passed to Ntfy"
  - No database update
  - No notifications
  - Returns

## Usage Example
```php
// Open connection
$db_conn = fcn_10_openConnection('./data/db/db.sqlite', $logger);

// Process incident XML file
fcn_13_recordReceived(
    $db_conn,
    'incidents',
    './data/watchfolder/incident_12345.xml',
    $logger,
    $config
);
```

## Change Detection Logic

The function detects and responds to:

| Change Type | Action | Notify All Topics? |
|------------|--------|-------------------|
| New incident | Insert + Send | N/A (new) |
| Closed incident | Delete only | No (removed) |
| New units dispatched | Update + Send | No (only new units) |
| Call type changed | Update + Send | Yes (everyone needs to know) |
| Location changed | Update + Send | Yes (critical info) |
| Alarm level increased | Update + Send | Yes (escalation) |
| No changes | None | No |
| Old incident (>15 min) | Update only | No (too stale) |

## Error Handling
- Dies with error message if XML parsing fails
- Relies on database functions to handle their own errors
- No explicit exception handling in this function
- Assumes XML structure is valid (New World CAD format)

## Integration
- Called by `fcn_5_runExternal()` for each XML file
- Core intelligence of the entire system
- Determines what notifications to send and when
- Calls multiple other functions:
  - `fcn_14_deleteRecord()` - Remove closed incidents
  - `fcn_15_callIdExist()` - Check for existing records
  - `fcn_16_insertRecord()` - Add/update database
  - `fcn_20_deltaTime()` - Calculate incident age
  - `fcn_21_sendMessage()` - Send notifications
- Maintains incident state across multiple XML updates
