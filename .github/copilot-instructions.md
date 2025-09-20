# NWS Endpoints - GitHub Copilot Instructions

NWS Endpoints is a PHP application that monitors a watch folder for Tyler Tech New World CAD XML files, parses incident data, stores it in SQLite, and sends notifications via ntfy.sh and Pushover. The application runs as a Docker container or standalone PHP process.

Always reference these instructions first and fallback to search or bash commands only when you encounter unexpected information that does not match the info here.

## Working Effectively

### Bootstrap and Run the Application

#### Local PHP Setup (RECOMMENDED)
- Install PHP 8.3+ with required extensions:
  ```bash
  # Ubuntu/Debian
  sudo apt-get update && sudo apt-get install -y php8.3-cli php8.3-curl php8.3-pdo php8.3-sqlite3 php8.3-xml

  # Verify required extensions
  php --modules | grep -E "(curl|pdo|sqlite|xml)"
  ```

- Install Composer dependencies:
  ```bash
  cd src/
  composer install --no-interaction --prefer-dist
  ```
  - **Takes 1-45 seconds** depending on network and cache state
  - **NEVER CANCEL** - wait for completion
  - If prompted for GitHub token, use `--prefer-dist` flag to avoid API calls
  - First run may take longer due to dependency downloads

- Setup configuration and directories:
  ```bash
  cd src/
  cp config.php.dist config.php
  mkdir -p data/watchfolder data/output data/archive data/db
  ```

- Run the application:
  ```bash
  cd src/
  php run
  ```
  - Application runs indefinitely monitoring `./data/watchfolder` for XML files
  - **NEVER CANCEL** - this is a long-running daemon process
  - Press Ctrl+C to stop monitoring
  - Logs output to stdout in real-time

#### Docker Setup (CURRENTLY BROKEN)
- Docker build has network connectivity issues in many environments
- Build failures occur during package downloads (composer, apt-get)
- **DO NOT** attempt Docker builds in environments with restricted internet access
- If Docker is required, manually copy dependencies from successful local builds

### Validation and Testing

#### ALWAYS run these validation steps after making changes
1. **PHP Syntax Check** (< 5 seconds):
   ```bash
   cd src/
   php -l run
   find functions/ -name "*.php" -exec php -l {} \;
   ```

2. **Dependencies Check** (< 10 seconds):
   ```bash
   cd src/
   composer validate
   ```

3. **Application Startup Test** (10-15 seconds):
   ```bash
   cd src/
   timeout 10 php run
   ```
   - Should show logger initialization and folder setup
   - Should create data directories if missing
   - Should show "Watch folder found at ./data/watchfolder"
   - Should show "nws-endpoint logger is now ready"
   - Should show all function includes loading successfully
   - Exit code 124 (timeout) is expected and normal

#### Manual Validation Scenarios

**CRITICAL**: Always test actual functionality after making changes:

1. **File Processing Test**:
   - Start application: `cd src/ && php run` (in background)
   - Create a test XML file in `data/watchfolder/`
   - Verify application processes file (moves to archive)
   - Check database creation in `data/db/db.sqlite`
   - Stop application with Ctrl+C

2. **Configuration Validation**:
   - Modify `src/config.php` settings
   - Verify application loads without errors
   - Test with different notification endpoints

3. **Database Validation**:
   - Check SQLite database is created: `ls -la src/data/db/`
   - Verify table creation occurs on first run
   - Confirm incident data storage functionality

### Linting and CI

- **GitHub Super Linter** runs on all pushes and PRs (.github/workflows/super-linter1.yml)
- **NEVER CANCEL** CI builds - they include comprehensive validation
- Super Linter validates: PHP syntax, JSON, YAML, Dockerfile, Markdown
- CI installs PHP extensions: `php-curl php-pdo php-sqlite3 php-xml`

### Build Times and Timeouts

**CRITICAL TIMEOUT SETTINGS**:
- Composer install: 5+ minutes (set timeout to 300+ seconds) - typically completes in 1-45 seconds
- PHP syntax validation: 10 seconds (typically completes in < 5 seconds)
- Application startup test: 15 seconds (typically completes in < 5 seconds)
- Full application run: INDEFINITE (daemon process)
- **NEVER CANCEL** any composer or long-running PHP processes

## Key Projects and File Structure

### Core Application Files
- `src/run` - Main application entry point and monitoring loop
- `src/config.php.dist` - Configuration template (copy to `config.php`)
- `src/composer.json` - PHP dependencies (Monolog, PDO, SimpleXML)

### Function Library (`src/functions/`)
- `fcn_2_monitorFolder.php` - Main folder monitoring entry point
- `fcn_4_recursiveGlob.php` - File discovery and scanning
- `fcn_5_runExternal.php` - Main file processing orchestration
- `fcn_13_recordReceived.php` - XML parsing and incident processing
- `fcn_21_sendNtfy.php` - ntfy.sh notification sending
- `fcn_21a_sendPushover.php` - Pushover notification sending
- Database functions: `fcn_10_*` through `fcn_16_*`

### Configuration and Deployment
- `Dockerfile` - Container build (currently has network issues)
- `compose.yml` - Docker Compose with ntfy, SQLite browser, log viewer
- `.github/workflows/super-linter1.yml` - CI/CD linting pipeline

### Data Directories (created at runtime)
- `src/data/watchfolder/` - Input XML files from New World CAD
- `src/data/output/` - Temporary processing folder
- `src/data/archive/` - Processed XML files storage
- `src/data/db/` - SQLite database files

## Common Tasks

### Check Application Status
```bash
cd src/
# View recent logs
tail -f /dev/stdout  # if running in background

# Check database
ls -la data/db/
sqlite3 data/db/db.sqlite ".tables"
```

### Configuration Changes
```bash
cd src/
# Edit notification settings
nano config.php

# Validate configuration
php -c config.php -l run
```

### Troubleshooting
```bash
cd src/
# Check PHP extensions
php --modules | grep -E "(curl|pdo|sqlite|xml)"

# Validate XML processing
php -r "var_dump(simplexml_load_string('<test>data</test>'));"

# Check folder permissions
ls -la data/
```

### Testing with Sample Data
```bash
cd src/
# Create minimal test XML (requires proper Tyler CAD structure)
# See fcn_13_recordReceived.php for expected XML schema:
# - AgencyContexts->AgencyContext->AgencyType
# - Incidents->Incident->Jurisdiction  
# - AssignedUnits->Unit->UnitNumber
```

## Critical Notes

- **Application is a daemon** - runs continuously monitoring for XML files
- **XML format is very specific** - must match Tyler Tech New World CAD export schema
- **Network dependencies** - requires internet access for ntfy/Pushover notifications
- **File permissions** - application must read/write to data/ directories
- **Database persistence** - SQLite files maintain incident state between runs
- **Memory usage** - designed for long-running operation with minimal memory footprint

## Docker Environment Notes

- **Docker builds FAIL** in restricted network environments
- **Docker Compose** configuration is valid but requires functioning image build
- **CIFS volume mounting** requires environment variables for shared folder access
- **Port mappings**: Dozzle (8081), SQLite Browser (8082)
- Use local PHP setup for development and testing

## Common File Outputs

### Repository Root Structure
```tree
.
├── .github/workflows/super-linter1.yml
├── .gitignore
├── Dockerfile  
├── LICENSE
├── README.md
├── compose.yml
└── src/
    ├── composer.json
    ├── config.php.dist
    ├── functions/
    ├── run
    └── vendor/
```tree

### composer.json Dependencies
```json
{
  "require": {
    "php": "^8.1",
    "ext-curl": "*",
    "ext-pdo": "*",
    "ext-pdo_sqlite": "*",
    "monolog/monolog": "^3.0",
    "ext-simplexml": "*"
  }
}
```

### Expected Data Directory After First Run
```tree
src/data/
├── archive/
├── db/
│   └── db.sqlite
├── output/
└── watchfolder/
```tree
