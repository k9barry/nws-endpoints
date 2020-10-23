# nws-webhook

  Create Microsoft Teams webhook incident cards from Tyler Tech New World ESO interface by taking the Tyler New World .xml file and parsing it 
  and passing the necessary variables to Teams Chat channel via cURL.
  
REQUIREMENTS
- 1-PHP with curl
- 2-COMPOSER

This PHP project runs on a php server or client (Windows, Linux, Mac OS) that supports PHP (coded using php v7.4) and cURL.

WHAT THIS DOES

  When running this project will recursivelly monitor the input folder for new files matching the $arrayInputFileExtensions file 
  extentions.  When a file with a matching extention is found it is parsed using the included (functions.php) functions and moved 
  briefly into the output folder (for more parsing if necessary) and then finally moved into the archive folder.  
  
  All moves are logged in a log file by Monolog and stored in ./data/Logs/  
  
  The parsed data is written into a sqlite3 DB while the incident is active and then removed.  The data if whitelisted is sent to a Mictosoft Teams chat webhook .

INSTALLATION (WINDOWS 10)

  While there are many ways to set this server up.  The easiest way I have found is to download and install xampp with only Apache 
  and PHP options selected.  Then place the files into a new folder of your choice (i.e. C:\xampp\nws-webhook).  Do not worry about the 
  other files as they are created automatically if they do not exist in the folder.
  


Installation Steps:
- 1-git clone https://github.com/k9barry/nws-webhook.git
- 2-cd to the nws-webhook folder
- 3-Type "composer install"
- 4-!-run.bat  --  This starts the project


CONFIGURATION

The configuration is set in the file monitor.php and is fairly simple to set:

// Data folder for files created by scripts
$strDataFolder = "./data"; // No ending '/'

// Monitor folder this script is watching for file additions
$strInFolder = "Y:";  // mapped drive to NWS xml file folder

// Filename extensions to be monitored for in the monitor folder
$arrayInputFileExtensions = array('xml');

// Set time to wait before checking input folder for new files (in seconds)
$sleep = 3;

// Set the database table name to store incidents into
$db_table = 'incidents';
