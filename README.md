# nws->endpoints

Create a combination of Microsoft Teams webhook incident cards, Active911.com snpp notifications, or Pushover.net notifications using Tyler Tech New World's CAD - ESO interface by taking the Tyler New World .xml file from the ESO interface and parsing it and passing the necessary variables to Teams Chat channel via cURL, the Active911.com input via snpp, or Pushover.net.  This includes filtering to only send selective call types and will re-send information as the incident changes.
  
REQUIREMENTS
- 1-PHP with curl
- 2-COMPOSER
- 3-TYLER NEW WORLD CAD ESO interface generating .xml files

OPTIONAL (Endpoints)
- 1-Microsoft Teams account
- 2-Active911.com account (SNPP)
- 3-Pushover.net account

This PHP project runs on any php server or client (Windows, Linux, Mac OS) that supports PHP (coded using php v7.4) and cURL.

WHAT THIS DOES

  When running this project will recursivelly monitor the input folder for new files arriving from the New World System (NWS) computer aided dispatching (CAD) ESO interface matching the $arrayInputFileExtensions file extentions.  When a file with a matching extention is found it is parsed using the included functions and moved briefly into the output folder (for more parsing if necessary) and then finally moved into the archive folder.  
  
  All information is logged to a log file by Monolog and stored in ./data/Logs/ by date.  Three days of logging are kept. 
  
  The parsed incident data is written into a sqlite3 DB while the incident is active and then removed when the incident is closed.  
  
  Specific incidents can be chosed to be sent to these endpoints by setting the active column to 'true" in the csv file - $CfsCsvFilePath = "./src/nwscfstype.csv"
  
  If the incident matches is whitelisted it can be sent to any combination of Teams Webhook, Active911.com, and/or Pushover.com by setting the needed information in the ./src/config.php file.

INSTALLATION (WINDOWS 10)

  While there are many ways to set this server up.  The easiest way I have found is to download and install xampp with only Apache 
  and PHP options selected.  Then place the files into a new folder of your choice (i.e. C:\xampp\nws-webhook).  Do not worry about the 
  other files as they are created automatically if they do not exist in the folder.
  


Installation Steps:
- 1-git clone https://github.com/k9barry/nws-endpoints.git
- 2-cd to the nws-endpoints folder
- 3-Type "composer install"
- 4-!-run.bat  --  This starts the project


CONFIGURATION

The configuration is set in the file .src/config.php and is fairly simple to set:
Remane config.php.dist to config.php (cp ./config.php.dist config.php)

Add your Microsoft Teams Webhook url.
* Create an outgoing webhook
* Select the appropriate team and select Manage team from the (•••) drop-down menu.
* Choose the Apps tab from the navigation bar.
* From the window's lower right corner select Create an outgoing webhook.
* In the resulting popup window complete the required fields:
  * Name - The webhook title and @mention tap.
  * Callback URL - The HTTPS endpoint that accepts JSON payloads and will receive POST requests from Teams.
  * Description - A detailed string that will appear in the profile card and the team-level App dashboard.
  * Profile Picture (optional) an app icon for your webhook.
  * Select the Create button from lower right corner of the pop-up window and the outgoing webhook will be added to the current team's channels.
  * The next dialog window will display an Hash-based Message Authentication Code (HMAC) security token that will be used to authenticate calls between Teams and the designated outside service.
  * If the URL is valid and the server and client authentication tokens are equal (i.e., an HMAC handshake), the outgoing webhook will be available to the team's users.


$snppSend = true;

$snppUrl = "snpp.active911.com";

$snppPort = "444";

$snppPage = "Active911.com_token";

$pushoverSend = true;

$pushoverUrl = "https://api.pushover.net/1/messages.json";

$pushoverToken = "Pushover.net_token";

$pushoverUser = "Pushover.net_user_or_group_ID";

$webhookSend = true;

$webhookUrl = "Microsoft_Teams_Webhook_URL";

$googleApiKey = "Google_Map_API_Key";
