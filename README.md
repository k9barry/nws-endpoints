# nws->endpoints ntfy docker compose

Docker compose file to reate a self hosted ntfy.sh server notification using Tyler Tech New World's CAD - ESO interface by taking the Tyler New World .xml file from the ESO interface and parsing it and passing the necessary variables to a self hosted ntfy server. Ntfy topics will be created based on the list below and will re-send information as the incident changes.

TOPICS will be created for (nested)

- Agency i.e. Police|Fire
- Jurisdiction i.e. ORI|FDID
- Unit i.e. CAR1|FIRETRUCK1

REQUIREMENTS

- 1-TYLER NEW WORLD CAD EXPORTER interface generating .xml files
- 2-Docker Compose

WHAT THIS DOES

This project will recursivelly monitor the watchfolder for new files arriving from the New World System (NWS-CAD) EXPORTER interface matching the $arrayInputFileExtensions extentions (xml). When a file with a matching extention is found it is moved briefly into the output folder and parsed by the included functions then finally moved into the archive folder.

All information is logged to a log file by Monolog and stored in ./data/Logs/ by date. (Three days of logging are kept.)

The parsed incident data is written into a sqlite3 DB while the incident is active and then removed when the incident is closed.

All incidents will be sent to nested ntfy topics based on AGENCY/JURISDICTION/UNIT (i.e. https://docs.ntfy.sh/config/#config-options )

CONFIGURATION

The configuration is set in the file .src/config.php and is fairly simple to set:
Remane config.php.dist to config.php (cp ./config.php.dist config.php)

$ntfySend = true;

$ntfyrUrl = "ntfy.{tld}";

$ntfyToken = "token";

$ntfyUser = "user";

$googleApiKey = "Google_Map_API_Key";
