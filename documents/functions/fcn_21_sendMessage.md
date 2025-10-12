# fcn_21_sendMessage Function Documentation

## Purpose
Sends incident notifications to both ntfy.sh and Pushover services with complete incident details. Creates hierarchical topic structure for ntfy (Agency/Jurisdiction/Unit) and formats notification messages with Google Maps location links and incident information for both notification services.

## Location
`src/functions/fcn_21_sendMessage.php`

## Function Signature
```php
function fcn_21_sendMessage(
    PDO $db_conn,
    string $db_incident,
    SimpleXMLElement $xml,
    string $delta,
    LoggerInterface $logger,
    string $topics,
    int $resendAll,
    array $config
): void
```

## Parameters

| Parameter | Type | Description |
|-----------|------|-------------|
| `$db_conn` | PDO | Database connection (PDO instance) |
| `$db_incident` | string | Database incident table name |
| `$xml` | SimpleXMLElement | XML data containing CallId |
| `$delta` | string | Time delta in seconds (incident age) |
| `$logger` | LoggerInterface | Logger instance for notification operations |
| `$topics` | string | Pipe-delimited topic hierarchy for ntfy routing |
| `$resendAll` | int | Whether to resend to all topics (1) or just new ones (0) |
| `$config` | array | Configuration array containing notification settings |

## Return Value
- Returns `void` (no return value)
- Throws `PDOException` if database query fails
- Throws `RuntimeException` if notification sending fails

## Step-by-Step Process

### Step 1: Extract Call ID
- Gets Call ID from XML: `$xml->CallId`
- Casts to string for consistency
- Used to query database for full incident data

### Step 2: Query Database for Full Incident Data
- Prepares SQL: `SELECT * FROM {$db_incident} WHERE db_CallId = ?`
- Executes with Call ID parameter
- Fetches all matching rows as associative array
- Stores in `$incidentData`

### Step 3: Validate Data Retrieved
- Checks if `$incidentData` is empty
- If no data found:
  - Logs warning with Call ID
  - Returns early (no notifications sent)
- This can happen if record deleted between processing steps

### Step 4: Extract Data Fields
- Uses `extract($incidentData[0])` to create variables
- Creates variable for each database column:
  - `$db_CallId`, `$db_CallNumber`, `$db_CallType`, etc.
- All 24 database fields now available as PHP variables

### Step 5: Build Google Maps URL
- Constructs URL: `https://www.google.com/maps/dir/?api=1&destination={lat},{lon}`
- Uses `$db_LatitudeY` and `$db_LongitudeX` coordinates
- Creates driving directions link to incident location
- Users can tap link to navigate from current location

### Step 6: Send NTFY Notifications (If Enabled)
- Checks config: `$config['ntfy']['send']`
- Accepts boolean `true` or string `"true"`
- If enabled:
  - Calls `sendNtfyNotification()` with:
    - Incident data array
    - Google Maps URL
    - Time delta
    - Topics string
    - Resend all flag
    - Logger
    - Config array
  - See "NTFY Notification Process" section below

### Step 7: Send Pushover Notification (If Enabled)
- Checks config: `$config['pushover']['send']`
- Accepts boolean `true` or string `"true"`
- If enabled:
  - Calls `sendPushoverNotification()` with:
    - Incident data array
    - Google Maps URL
    - Time delta
    - Logger
    - Config array
  - See "Pushover Notification Process" section below

### Step 8: Clean Up Old Records
- Calls `fcn_22_removeOldRecords()` after notifications sent
- Removes old incidents from database (keeps last 999)
- Prevents database from growing indefinitely
- Maintains manageable database size

### Step 9: Handle Exceptions
Two exception handlers:

#### PDOException
- Database errors (connection lost, query failed)
- Logs error with Call ID and message
- Re-throws exception for upstream handling

#### General Exception
- Any other exception type
- Logs error with Call ID and message
- Wraps in `RuntimeException` with context
- Provides clear "Failed to send notifications" message

## NTFY Notification Process

### Helper Function: sendNtfyNotification()

#### Step 1: Extract Data
- Uses `extract($incidentData)` to get all fields
- Logs preparing NTFY notification

#### Step 2: Determine Tags
- Uses `match` expression on `$db_AgencyType`:
  - "Fire" → `fire_engine` emoji
  - "Police" → `police_car` emoji
  - Default → Both emojis
- Adds alarm level tag if present:
  - Alarm 1 → `1st_place_medal`
  - Alarm 2 → `2nd_place_medal`
  - Alarm 3 → `3rd_place_medal`
- Combines tags with commas

#### Step 3: Rebuild Topics (If Resend All)
- If `$resendAll === 1`:
  - Reconstructs topics from database: `{Agency}|{Jurisdiction}|{Unit}`
  - Ensures all relevant topics notified
  - Used when critical fields change (call type, location, alarm level)

#### Step 4: Calculate Priority
- Formula: `((int) $db_AlarmLevel ?? 1) + 2`
- Alarm 1 → Priority 3
- Alarm 2 → Priority 4
- Alarm 3 → Priority 5
- Clamps between 1-5 using `max(1, min(5, ...))`

#### Step 5: Log Notification Details
- Logs separator line (53 equals signs)
- Logs "NTFY {call_type} at {address} will be sent to topics: {topics}"

#### Step 6: Split Topics
- Explodes topics by `|` separator
- Uses `array_unique()` to remove duplicates
- Uses `array_filter()` to remove empty values
- Creates array of individual topics

#### Step 7: Skip "New Call" Type
- If `$db_CallType !== "New Call"`:
  - Proceeds with notification
- Otherwise:
  - Skips notification
  - "New Call" is placeholder, not actual incident

#### Step 8: Send to Each Topic
For each topic in array:

1. **Trim Topic** - Remove whitespace
2. **Skip Empty** - Continue if topic is empty string
3. **Build HTTP Request** using `stream_context_create()`:
   - Method: PUT
   - Timeout: 10 seconds
   - Headers:
     - Content-Type: text/plain
     - Authorization: Bearer token from config
     - Title: "Call: {number} {type} ({delta})"
     - Tags: Emoji tags from step 2
     - Attach: Google Maps URL
     - Icon: Fire/police icon URL
     - Priority: Calculated priority
   - Body: Multi-line incident details:
     - Common Name, Location, Incident Type
     - Nature of Call, Cross Roads
     - Beat, Quadrant, Units
     - Create Time, Narrative
4. **Send Request** using `file_get_contents()` with context
5. **Check Response**:
   - If success: Log "NTFY message sent successfully"
   - If failure: Throw RuntimeException with error
6. **Handle Errors**:
   - Catch exceptions per topic
   - Log error but continue with other topics
   - Don't fail entire notification for one topic failure

## Pushover Notification Process

### Helper Function: sendPushoverNotification()

#### Step 1: Extract Data
- Uses `extract($incidentData)` to get all fields
- Logs preparing Pushover notification

#### Step 2: Initialize cURL
- Creates cURL handle with `curl_init()`
- If initialization fails, throws RuntimeException
- Stored in `$ch` variable

#### Step 3: Configure cURL Options
Uses `curl_setopt_array()` with:
- **URL**: Pushover API endpoint from config
- **RETURNTRANSFER**: true (return response as string)
- **TIMEOUT**: 30 seconds
- **CONNECTTIMEOUT**: 10 seconds
- **SSL_VERIFYPEER**: true (verify SSL certificate)
- **SSL_VERIFYHOST**: 2 (verify hostname matches certificate)
- **POSTFIELDS**: Array with:
  - token: Pushover app token
  - user: Pushover user key
  - title: "MCCD Call: {number} {type} ({delta})"
  - message: Multi-line incident details
  - sound: "bike" (notification sound)
  - html: "1" (enable HTML formatting)
  - url: Google Maps URL
  - url_title: "Driving Directions"

#### Step 4: Execute Request
- Calls `curl_exec($ch)` to send notification
- Returns response as string in `$result`
- If fails, throws RuntimeException with cURL error

#### Step 5: Check HTTP Status
- Gets HTTP response code with `curl_getinfo($ch, CURLINFO_HTTP_CODE)`
- If not 200, throws RuntimeException with status and response

#### Step 6: Validate JSON Response
- Decodes JSON response: `json_decode($result, true)`
- Checks for JSON errors with `json_last_error()`
- If invalid JSON, throws RuntimeException

#### Step 7: Check API Response
- Verifies `status` field equals 1 (success)
- If not success:
  - Extracts error message from response
  - Throws RuntimeException with Pushover API error

#### Step 8: Log Success
- Logs "Pushover message sent successfully"
- Includes full response for debugging

#### Step 9: Close cURL (Finally Block)
- Uses finally block to ensure cURL handle closed
- Calls `curl_close($ch)` even if exceptions occur
- Frees system resources

## Configuration Requirements

### NTFY Config
```php
$config['ntfy'] = [
    'send' => true,                              // Enable/disable
    'url' => 'https://ntfy.your-domain.com',    // NTFY server URL
    'authToken' => 'Bearer tk_your_token'       // Auth token
];
```

### Pushover Config
```php
$config['pushover'] = [
    'send' => true,                                    // Enable/disable
    'url' => 'https://api.pushover.net/1/messages.json', // API endpoint
    'token' => 'your_app_token',                       // App token
    'user' => 'your_user_key'                          // User key
];
```

## Error Handling
- Database errors logged and re-thrown
- NTFY errors logged per-topic, don't stop other topics
- Pushover errors logged and thrown
- cURL cleanup guaranteed with finally block
- Detailed error messages for troubleshooting

## Integration
- Called by `fcn_13_recordReceived()` when:
  - New incident detected
  - Existing incident updated with significant changes
  - Incident age is within threshold (< 15 minutes)
- Both notification services can be enabled simultaneously
- Either or both can be disabled via configuration
- Automatically cleans up old database records after notifications
