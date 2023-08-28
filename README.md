# nws->endpoints ntfy

Create a ntfy (self hosted ntfy.sh server) notification using Tyler Tech New World's CAD - ESO interface by taking the Tyler New World .xml file from the ESO interface and parsing it and passing the necessary variables to a self hosted ntfy server.  Ntfy topics will be created based on the list below and will re-send information as the incident changes.

TOPICS will be created for (nested)
- Agency i.e. Police|Fire
- Jurisdiction i.e. ORI|FDID
- Unit i.e. CAR1|FIRETRUCK1
  
REQUIREMENTS
- 1-PHP (coded with v7.4) with curl
- 2-COMPOSER  <==========  This may no longer be needed  ================
- 3-TYLER NEW WORLD CAD ESO interface generating .xml files
- 4-Self hosted NTFY server preferbally behind reverse proxy

This PHP project runs on any php server or client (Windows, Linux, Mac OS) that supports PHP (coded using php v7.4) and cURL.

WHAT THIS DOES

  When running this project will recursivelly monitor the input folder for new files arriving from the New World System (NWS) computer aided dispatching (CAD) ESO interface matching the $arrayInputFileExtensions file extentions.  When a file with a matching extention is found it is parsed using the included functions and moved briefly into the output folder (for more parsing if necessary) and then finally moved into the archive folder.  
  
  All information is logged to a log file by Monolog and stored in ./data/Logs/ by date.  (Three days of logging are kept.)
  
  The parsed incident data is written into a sqlite3 DB while the incident is active and then removed when the incident is closed.  
  
  All incidents will be sent to nested ntfy topics based on AGENCY/JURISDICTION/UNIT (i.e. https://ntfy.sh/{topic1}/{topic2}/{topic3} )
  
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

$ntfySend = true;

$ntfyrUrl = "ntfy.{tld}";

$ntfyToken = "token";

$ntfyUser = "user";

$googleApiKey = "Google_Map_API_Key";
