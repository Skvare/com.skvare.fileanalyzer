# CiviCRM File Analyzer Extension

A comprehensive CiviCRM extension that analyzes file uploads, identifies abandoned files, and provides efficient storage management tools.

### File Analyzer Settings:

(`/civicrm/admin/setting/fileanalyzer`)

![Screenshot](/images/fileanalyzer_settings.png)

### File Analyzer Dashboard:

#### By Storage Size Per Month Chart:

![Screenshot](/images/fileanalyzer_dashboard_storage.png)

#### By File Count Per Month Chart:
![Screenshot](/images/fileanalyzer_dashboard_filecount.png)


## Overview

The File Analyzer extension helps CiviCRM administrators monitor and manage file uploads by:
- Automatically scanning the custom file upload directory
- Identifying abandoned files (files not referenced in the database)
- Providing detailed analytics and visualizations
- Offering automated cleanup capabilities with backup options
- Storing scan results in JSON format for optimal performance

## Key Features

### 🔍 **Comprehensive File Analysis**
- Scans all files in the CiviCRM custom upload directory recursively
- Identifies files referenced vs. abandoned files
- Analyzes file types, sizes, and modification dates
- Generates monthly statistics and trends

### 📊 **Rich Dashboard Analytics**
- Interactive charts showing file type distribution
- Monthly file creation trends
- Top abandoned files by size
- Storage utilization statistics
- Scan history tracking

### 🗑️ **Automated Cleanup**
- Configurable auto-deletion of abandoned files
- Backup files before deletion option
- Customizable retention periods
- Safe deletion with database verification

### ⚡ **Performance Optimized**
- JSON-based caching system for fast dashboard loading
- Scheduled scanning prevents real-time performance impact
- Efficient database queries for file usage detection

## Architecture

### Scheduled Scanning Process
```
CRM_Fileanalyzer_API_FileAnalysis::scheduledScan()
    ↓
1. Scan custom file upload directory recursively
2. Check each file against database references
3. Generate comprehensive analysis data
4. Store results in JSON files:
   - latest_scan_results.json (complete scan data)
   - abandoned_files.json (abandoned files only)
   - scan_results_[timestamp].json (historical data)
5. Perform auto-deletion if configured
```

### Dashboard Display Process
```
CRM_Fileanalyzer_Page_Dashboard::run()
    ↓
1. Read stored JSON files
2. Check if scan data exists
3. If no data: Prompt to run scheduled job
4. If data exists: Process and display analytics
5. Generate charts and statistics
```

## Installation

1. **Download the extension:**
   ```bash
   cd /path/to/civicrm/extensions
   git clone https://github.com/skvare/com.skvare.fileanalyzer.git
   ```

2. **Install via CiviCRM:**
  - Navigate to Administer → System Settings → Extensions
  - Find "File Analyzer" and click Install

3. **Set up scheduled job:**
  - Go to Administer → System Settings → Scheduled Jobs
  - Create a new job with API Entity: `FileAnalyzer` and API Action: `scheduledScan`
  - Set frequency (recommended: daily)

## Configuration

### Extension Settings
Access via Administer → File Analyzer → Settings:

- **Scan Interval**: How often to run the scheduled scan (hours)
- **Auto Delete**: Enable automatic deletion of abandoned files
- **Auto Delete Days**: Files older than X days will be deleted
- **Backup Before Delete**: Create backup copies before deletion
- **Excluded Extensions**: File types to skip during scanning (comma-separated)

### Recommended Settings
```
Auto Delete: Disabled (initially)
Auto Delete Days: 30 days
Backup Before Delete: Enabled
Excluded Extensions: .htaccess,.gitignore
```

## Usage

### Dashboard Access
Navigate to **Administer → System Settings → File Analyzer**

### Dashboard Features

#### Summary Statistics
- Total files and storage usage
- Abandoned files count and size
- Last scan date and active files count

#### Interactive Charts
- **File Types Distribution**: Shows total vs abandoned files by extension
- **Monthly Trends**: File creation patterns over time
- **Top Abandoned Files**: Largest abandoned files for cleanup priority

#### File Management
- View detailed abandoned file listings
- Manual scan trigger for administrators
- Access to scan history and reports

### Generated Data Files
Located in `[customFileUploadDir]/file_analyzer_backups/`:
```
file_analyzer_backups/
├── latest_scan_results.json          # Most recent complete scan
├── abandoned_files.json              # Current abandoned files
├── scan_results_[timestamp].json     # Historical scans
├── deleted_files/                    # Backup of deleted files
├── reports/                          # Generated reports
└── .htaccess                         # Security protection
```

## Database Integration

### File Usage Detection
The extension checks multiple sources to determine if a file is in use:

1. **civicrm_file table**: Direct file attachments
2. **Custom field tables**: File-type custom fields
3. **civicrm_entity_file**: Activity and entity attachments

## Security Considerations

### File Protection
- Backup directory protected with `.htaccess`
- JSON files contain paths but no sensitive content
- Only administrators can access the dashboard
- Deleted files are backed up before removal

### Permissions Required
- **CiviCRM Administer**: View dashboard and reports
- **CiviCRM Administer**: Configure settings and trigger scans

## Troubleshooting

### Common Issues

#### No Scan Results Displayed
**Problem**: Dashboard shows "No files found, ask to run scheduled job"
**Solution**:
1. Check if scheduled job is configured and running
2. Manually trigger scan via API or dashboard
3. Verify custom file upload directory permissions

#### Scheduled Job Fails
**Problem**: Scan job fails with errors
**Solution**:
1. Check CiviCRM log files for detailed errors
2. Verify file system permissions on upload directory
3. Ensure backup directory is writable
4. Check PHP memory limits for large directories

#### Performance Issues
**Problem**: Dashboard loads slowly
**Solution**:
1. Ensure using JSON-cached data, not live scanning
2. Increase PHP memory limit if needed
3. Consider excluding large file types from scanning

## License

This extension is licensed under [AGPL-3.0](LICENSE.txt).

## Support

- **Issues**: Report bugs and feature requests on [GitHub Issues](https://github.com/skvare/com.skvare.fileanalyzer/issues)
- **Documentation**: [CiviCRM Extension Documentation](https://docs.civicrm.org/dev/en/latest/extensions/)
- **Community**: [CiviCRM Stack Exchange](https://civicrm.stackexchange.com/)

## About Skvare

Skvare LLC specializes in CiviCRM development, Drupal integration, and providing technology solutions for nonprofit organizations, professional societies, membership-driven associations, and small businesses. We are committed to developing open source software that empowers our clients and the wider CiviCRM community.

**Contact Information**:
- Website: [https://skvare.com](https://skvare.com)
- Email: info@skvare.com
- GitHub: [https://github.com/Skvare](https://github.com/Skvare)

---

## Related Extensions

You might also be interested in other Skvare CiviCRM extensions:

- **Database Custom Field Check**: Prevents adding custom fields when table limits are reached
- **Image Resize**: Resize images uploaded to CiviCRM with different dimensions
- **Registration Button Label**: Customize button labels on event registration pages

For a complete list of our open source contributions, visit our [GitHub organization page](https://github.com/Skvare).
