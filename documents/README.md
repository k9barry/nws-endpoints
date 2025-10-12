# NWS Endpoints Documentation

This directory contains comprehensive documentation for the NWS Endpoints repository, explaining how the entire system works from beginning to end.

## Documentation Structure

### Master Guide
- **[HOW THIS REPO WORKS.md](HOW%20THIS%20REPO%20WORKS.md)** - Complete step-by-step walkthrough of the entire system
  - System architecture overview
  - Application startup sequence
  - Main processing loop
  - File discovery and processing workflows
  - Database operations
  - Incident processing logic
  - Notification system (ntfy.sh and Pushover)
  - Cleanup and maintenance strategies
  - Data flow diagrams
  - Error handling strategy
  - Complete function call chain

### Individual Function Documentation

The `functions/` directory contains detailed documentation for each of the 20 core functions:

#### File Monitoring Functions
- [fcn_1_unlinkInputOld.md](functions/fcn_1_unlinkInputOld.md) - Cleanup old files from watch folder
- [fcn_2_monitorFolder.md](functions/fcn_2_monitorFolder.md) - Main entry point for file monitoring
- [fcn_3_globCaseInsensitivePattern.md](functions/fcn_3_globCaseInsensitivePattern.md) - Build case-insensitive file patterns
- [fcn_4_recursiveGlob.md](functions/fcn_4_recursiveGlob.md) - Recursive file discovery

#### File Processing Functions
- [fcn_5_runExternal.md](functions/fcn_5_runExternal.md) - Main processing coordinator for each file
- [fcn_6_recursiveMkdir.md](functions/fcn_6_recursiveMkdir.md) - Recursive directory creation
- [fcn_7_renameIfExists.md](functions/fcn_7_renameIfExists.md) - Generate unique filenames
- [fcn_8_getValue.md](functions/fcn_8_getValue.md) - Safe array value retrieval
- [fcn_9_fileNewname.md](functions/fcn_9_fileNewname.md) - Generate numbered unique filenames

#### Database Functions
- [fcn_10_openConnection.md](functions/fcn_10_openConnection.md) - Open SQLite database connection
- [fcn_11_tableExists.md](functions/fcn_11_tableExists.md) - Check if database table exists
- [fcn_12_createIncidentsTable.md](functions/fcn_12_createIncidentsTable.md) - Create incidents table schema
- [fcn_13_recordReceived.md](functions/fcn_13_recordReceived.md) - **Main incident processing logic**
- [fcn_14_deleteRecord.md](functions/fcn_14_deleteRecord.md) - Delete closed incidents
- [fcn_15_callIdExist.md](functions/fcn_15_callIdExist.md) - Check if incident exists in database
- [fcn_16_insertRecord.md](functions/fcn_16_insertRecord.md) - Insert or update incident records

#### Notification and Cleanup Functions
- [fcn_18_unlinkArchiveOld.md](functions/fcn_18_unlinkArchiveOld.md) - Clean old files from archive
- [fcn_20_DeltaTime.md](functions/fcn_20_DeltaTime.md) - Calculate incident age
- [fcn_21_sendMessage.md](functions/fcn_21_sendMessage.md) - Send notifications (ntfy.sh and Pushover)
- [fcn_22_removeOldRecords.md](functions/fcn_22_removeOldRecords.md) - Database maintenance

## How to Use This Documentation

### For New Users
Start with [HOW THIS REPO WORKS.md](HOW%20THIS%20REPO%20WORKS.md) to understand the complete system workflow from end to end.

### For Developers
1. Read the master guide for overall architecture
2. Reference individual function documentation when working on specific features
3. Follow the function call chain to understand execution flow
4. Review error handling strategies for troubleshooting

### For Troubleshooting
1. Check the relevant function documentation for expected behavior
2. Review the error handling section in the master guide
3. Follow the data flow diagrams to trace issues
4. Cross-reference with application logs

## Documentation Features

Each function document includes:
- **Purpose** - What the function does
- **Location** - Where to find it in the codebase
- **Function Signature** - Complete parameter list and types
- **Parameters** - Detailed parameter descriptions
- **Return Value** - What the function returns
- **Step-by-Step Process** - Detailed walkthrough of function logic
- **Usage Examples** - Code examples showing how to use the function
- **Error Handling** - How errors are handled
- **Integration** - How the function fits into the larger system

## Quick Reference

### System Entry Point
```
src/run → while(true) → fcn_2_monitorFolder()
```

### Core Processing Flow
```
fcn_2_monitorFolder() 
  → fcn_4_recursiveGlob() 
  → fcn_5_runExternal() 
  → fcn_13_recordReceived() 
  → fcn_21_sendMessage()
```

### Database Operations
```
fcn_10_openConnection() → fcn_11_tableExists() → fcn_12_createIncidentsTable()
fcn_15_callIdExist() → fcn_16_insertRecord() or fcn_14_deleteRecord()
```

### Notification Services
```
fcn_21_sendMessage() → sendNtfyNotification() + sendPushoverNotification()
```

## Additional Resources

- **Main README**: [../README.md](../README.md) - Project overview and setup instructions
- **Source Code**: [../src/](../src/) - PHP source code
- **Functions Directory**: [../src/functions/](../src/functions/) - Function implementations

## Contributing to Documentation

When updating documentation:
1. Keep step-by-step explanations clear and detailed
2. Include code examples where helpful
3. Update cross-references when functions change
4. Maintain consistent formatting across all documents
5. Test all links to ensure they work correctly

---

**Last Updated**: 2025-10-12  
**Documentation Version**: 1.0  
**Repository**: https://github.com/k9barry/nws-endpoints
