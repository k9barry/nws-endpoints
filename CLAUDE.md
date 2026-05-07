# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project

A PHP 8.1+ daemon that watches a folder for Tyler Tech New World CAD XML exports, parses incident records into a SQLite database, and pushes notifications via ntfy.sh and Pushover. Designed to run continuously — either as `php run` from `src/` or as a Docker container.

## Common commands

```bash
# First-time local setup
cd src/
composer install --no-interaction --prefer-dist
cp config.php.dist config.php   # then edit ntfy/Pushover credentials
mkdir -p data/watchfolder data/output data/archive data/db

# Run the daemon (long-lived; Ctrl+C to stop, exit code 124 from `timeout` is normal)
cd src/ && php run

# Quick validation
cd src/
php -l run                                            # syntax check entry point
find functions/ -name "*.php" -exec php -l {} \;      # syntax check all functions
composer validate
timeout 10 php run                                    # boot smoke test

# Docker stack (main app + Dozzle log viewer + SQLite Browser)
docker-compose up -d
docker-compose ps
docker-compose logs -f nws-endpoints
```

There is **no test suite** — CI is GitHub Super Linter (`.github/workflows/super-linter1.yml`), which validates PHP/JSON/YAML/Dockerfile/Markdown.

## Architecture

`src/run` is the entry point. It bootstraps Monolog (writing to stdout), loads `config.php`, **`include_once`s every file in `src/functions/` via glob**, ensures the data directories exist, runs a one-time `fcn_1_unlinkInputOld` cleanup, then loops forever calling `fcn_2_monitorFolder` every 3 seconds.

There are no classes — the codebase is a numbered set of procedural functions. Each function lives in its own file named `fcn_<N>_<purpose>.php` and the number roughly mirrors the call order through the pipeline:

```
fcn_2_monitorFolder         main entry into the per-tick loop
  → fcn_3_globCaseInsensitivePattern, fcn_4_recursiveGlob   discover XML files
    → fcn_5_runExternal      orchestrate one file
      → fcn_10_openConnection, fcn_11_tableExists, fcn_12_createIncidentsTable
      → fcn_13_recordReceived  parse XML, decide new vs. update, write rows
        → fcn_15_callIdExist, fcn_16_insertRecord, fcn_14_deleteRecord
        → fcn_8_getValue       safe array/XML access with default
        → fcn_20_DeltaTime     age check vs. config.timeAdjust
        → fcn_21_sendMessage   unified notifier: ntfy + Pushover
      → fcn_7_renameIfExists, fcn_9_fileNewname  archive the file
fcn_18_unlinkArchiveOld, fcn_22_removeOldRecords   periodic cleanup
```

When adding a new step, follow the existing naming/numbering convention and the auto-include glob will pick it up.

### XML schema expectations

`fcn_13_recordReceived` parses Tyler New World CAD exports and reads (at minimum):

- `AgencyContexts/AgencyContext/AgencyType`
- `Incidents/Incident/Jurisdiction`
- `AssignedUnits/Unit/UnitNumber`
- Plus the columns mirrored in the `incidents` table (CallId, CallNumber, ClosedFlag, CreateDateTime, CallType, AlarmLevel, RadioChannel, NatureOfCall, CommonName, FullAddress, State, NearestCrossStreets, AdditionalInfo, FireOri, FireQuadrant, PoliceOri, PoliceBeat, LatitudeY, LongitudeX, Narrative_Text)

Multi-valued fields are concatenated with `|` and de-duplicated.

### Notifications

`fcn_21_sendMessage` sends to **both** ntfy.sh and Pushover when their respective `send` flags are true in `config.php`. ntfy topics are hierarchical: `Agency/Jurisdiction/Unit` (one PUT per leaf). Both send flags, URLs, tokens, and `timeAdjust` (incidents older than this many seconds are suppressed) are gathered into the `$config` array in `run` and threaded through every function — do not reintroduce globals.

### Database

Single-table SQLite at `data/db/db.sqlite`, table `incidents`, schema defined in `fcn_12_createIncidentsTable`. PK is `db_CallId`; the table is auto-created on first run.

### Data directories (auto-created at runtime)

- `src/data/watchfolder/` — input XML files (in Docker, mounted from a CIFS share via `SHARED_FOLDER_PATH`)
- `src/data/output/` — temporary
- `src/data/archive/` — processed files (rotated by `fcn_18_unlinkArchiveOld`)
- `src/data/db/` — SQLite DB

## Conventions

- Memory and execution limits are unbounded in `run` (`memory_limit=-1`, `set_time_limit(0)`) — required for the daemon loop; do not "fix" these.
- All functions take `LoggerInterface $logger` and the `$config` array; pass them through, don't reach for globals.
- Use `fcn_8_getValue($array, $key, $default)` for any optional XML/array field — the XML schema is loose and missing keys are normal.
- Keep one function per file, named to match.

## Docker notes

`compose.yml` defines three services:

| Service | Port | Purpose |
|---------|------|---------|
| `nws-endpoints` | — | The daemon (`php run`) |
| `dozzle` | 8081 → 8080 | Live container log viewer at http://localhost:8081 |
| `sqlitebrowser` | 8082 → 3000 | Web SQLite browser at http://localhost:8082 (open `/config/db.sqlite`) |

The `watchfolder` volume is a CIFS mount; `.env` must define `CIFS_USERNAME`, `CIFS_PASSWORD`, `CIFS_DOMAIN`, `SHARED_FOLDER_PATH`.

Docker builds may fail in restricted-network environments (composer/apt downloads); local PHP runs are the reliable fallback during development. Composer install can take up to a few minutes on a cold cache — let it finish.

## Further docs

- `README.md` — full feature/config/troubleshooting reference
- `.github/copilot-instructions.md` — overlapping setup notes and validation steps
