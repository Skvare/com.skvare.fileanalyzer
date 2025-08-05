<?php

use CRM_Fileanalyzer_ExtensionUtil as E;

/**
 * CRM File Analyzer API Class
 *
 * This class provides comprehensive functionality for analyzing and managing file usage
 * within a CiviCRM system. It includes scheduled scanning for abandoned files,
 * automatic cleanup capabilities, backup functionality, and detailed reporting.
 *
 * Main features:
 * - Scheduled scanning for unused/abandoned files with comprehensive analysis
 * - Automatic deletion of old files with configurable retention periods
 * - Backup functionality before file deletion with organized storage
 * - Integration with CiviCRM settings system
 * - JSON-based reporting and data persistence
 * - File type and temporal analysis (monthly statistics)
 * - Database integrity checking across multiple CiviCRM tables
 */
class CRM_FileAnalyzer_API_FileAnalysis {

  /**
   * Main scheduled job entry point for file analysis and cleanup
   *
   * This method orchestrates the complete file analysis workflow:
   * 1. Sets up required directories and backup structure
   * 2. Performs comprehensive file system scan
   * 3. Analyzes files for database references
   * 4. Generates detailed reports with statistics
   * 5. Optionally performs automatic cleanup of abandoned files
   * 6. Persists results to JSON files for later access
   *
   * @return array Status array with detailed execution results
   *   - is_error: 0 for success, 1 for error
   *   - messages: Array of detailed status messages including counts
   *   - data: Optional scan results data
   */
  public static function scheduledScan() {
    // Load extension configuration settings from CiviCRM
    $settings = self::getSettings();

    // Get paths for file operations
    $customPath = CRM_Core_Config::singleton()->customFileUploadDir;
    $backupDir = $customPath . 'file_analyzer_backups';

    // Ensure all required directories exist before proceeding
    self::createDirectories();

    // Execute comprehensive file system scan and analysis
    // This returns detailed statistics and categorized file lists
    $scanResults = self::performFileScan();

    // Persist abandoned files list to separate JSON file for quick access
    // This allows UI components to display abandoned files without full scan
    $abandonedFilesPath = $backupDir . '/abandoned_files.json';
    file_put_contents($abandonedFilesPath, json_encode($scanResults['abandoned_files'], JSON_PRETTY_PRINT));

    // Initialize deletion counter for reporting
    $deletedCount = 0;

    // Execute automatic cleanup if enabled in settings
    if ($settings['auto_delete'] && $settings['auto_delete_days']) {
      // Calculate cutoff date based on retention policy
      // Files modified before this date are eligible for deletion
      $cutoffDate = date('Y-m-d H:i:s', strtotime("-{$settings['auto_delete_days']} days"));

      // Perform deletion of abandoned files older than cutoff date
      // Pass pre-identified abandoned files list to avoid re-scanning
      $deletedCount = self::autoDeleteAbandonedFiles($cutoffDate, $settings['backup_before_delete'], $scanResults['abandoned_files']);

      // Log deletion activity for audit trail and troubleshooting
      CRM_Core_Error::debug_log_message("File Analyzer: Auto-deleted {$deletedCount} abandoned files");
    }

    // Count abandoned files for reporting before removing from results
    $abandonedFilesCount = count($scanResults['abandoned_files']);

    // Remove abandoned files list from main results to reduce JSON size
    // (already saved separately above)
    unset($scanResults['abandoned_files']);

    // Create timestamped scan results file for historical tracking
    $scanResultsPath = $backupDir . '/scan_results_' . date('Y-m-d_H-i-s') . '.json';
    file_put_contents($scanResultsPath, json_encode($scanResults, JSON_PRETTY_PRINT));

    // Maintain latest scan results file for quick UI access
    // This overwrites the previous "latest" results
    $latestScanPath = $backupDir . '/latest_scan_results.json';
    file_put_contents($latestScanPath, json_encode($scanResults, JSON_PRETTY_PRINT));

    // Return comprehensive status information
    return [
      'is_error' => 0,
      'messages' => [
        "Scan completed. Found " . $abandonedFilesCount . " abandoned files",
        "Total files scanned: " . $scanResults['directoryStats']['totalFiles'],
        "Auto-deleted: {$deletedCount} files"
      ],
      // Uncomment the following line to return full scan data in API response
      //'data' => $scanResults
    ];
  }

  /**
   * Perform comprehensive file system scan and analysis
   *
   * This method executes the core file analysis logic:
   * 1. Scans all files in the custom upload directory recursively
   * 2. Filters files based on excluded extensions
   * 3. Checks database references to identify abandoned files
   * 4. Generates statistical analysis by file type and time period
   * 5. Calculates storage usage metrics
   *
   * @return array Comprehensive scan results including:
   *   - scan_date: Timestamp of scan execution
   *   - abandoned_files: Array of files not referenced in database
   *   - fileAnalysis: Statistical breakdowns by month and file type
   *   - directoryStats: Total file count and storage usage
   *   - active_files: Count of files still in use
   */
  private static function performFileScan() {
    // Get the root directory for file scanning
    $customPath = CRM_Core_Config::singleton()->customFileUploadDir;

    // Recursively discover all files in directory tree
    $files = self::scanDirectoryRecursive($customPath);

    // Load current extension settings for filtering
    $settings = self::getSettings();

    // Initialize comprehensive results structure
    $scanResults = [
      'scan_date' => date('Y-m-d H:i:s'),           // When this scan was performed
      'abandoned_files' => [],                      // Files not referenced in database
      'fileAnalysis' => [                          // Statistical analysis
        'monthly' => [],                            // Files grouped by modification month
        'fileTypes' => []                           // Files grouped by extension
      ],
      'directoryStats' => [                        // Overall directory metrics
        'totalSize' => 0,                          // Total bytes consumed
        'totalFiles' => 0                          // Total file count
      ],
    ];

    // Process each discovered file
    foreach ($files as $file) {
      $fullPath = $customPath . '/' . $file;

      // Skip invalid files or directories
      // Additional safety check to ensure we're processing actual files
      if (!str_contains($file, $customPath) || !is_file($file)) {
        continue;
      }

      // Get file system metadata
      $stat = stat($file);
      $extension = strtolower(pathinfo($file, PATHINFO_EXTENSION));
      $month = date('Y-m', $stat['mtime']); // Group by year-month for trends

      // Skip files with excluded extensions (e.g., system files, logs)
      if (in_array($extension, $settings['excluded_extensions'])) {
        continue;
      }

      // Build comprehensive file information record
      $fileInfo = [
        'filename' => $file,                        // Full file path
        'filenameOnly' => basename($file),          // Just the filename
        'size' => $stat['size'],                    // File size in bytes
        'modified' => date('Y-m-d H:i:s', $stat['mtime']), // Last modification
        'extension' => $extension,                  // File extension for categorization
        'in_use' => self::isFileInUse(basename($file)), // Database reference check
        'path' => $file,                           // File path for operations
      ];

      // Update overall statistics
      $scanResults['directoryStats']['totalFiles']++;
      $scanResults['directoryStats']['totalSize'] += $stat['size'];

      // Categorize file based on database usage
      if ($fileInfo['in_use']) {
        // File is referenced in database - increment active counter
        $scanResults['active_files']++;
      }
      else {
        // File is not referenced - add to abandoned list for potential cleanup
        $scanResults['abandoned_files'][] = $fileInfo;
      }

      // Build file type statistics for reporting
      if (!isset($scanResults['fileAnalysis']['fileTypes'][$extension])) {
        $scanResults['fileAnalysis']['fileTypes'][$extension] = [
          'count' => 0,           // Total files of this type
          'size' => 0,            // Total bytes for this file type
          'abandoned_count' => 0  // How many of this type are abandoned
        ];
      }
      $scanResults['fileAnalysis']['fileTypes'][$extension]['count']++;
      $scanResults['fileAnalysis']['fileTypes'][$extension]['size'] += $stat['size'];
      if (!$fileInfo['in_use']) {
        $scanResults['fileAnalysis']['fileTypes'][$extension]['abandoned_count']++;
      }

      // Build monthly statistics for trend analysis
      if (!isset($scanResults['fileAnalysis']['monthly'][$month])) {
        $scanResults['fileAnalysis']['monthly'][$month] = [
          'count' => 0,           // Files created/modified this month
          'size' => 0,            // Total bytes for this month
          'abandoned_count' => 0  // Abandoned files from this month
        ];
      }
      $scanResults['fileAnalysis']['monthly'][$month]['count']++;
      $scanResults['fileAnalysis']['monthly'][$month]['size'] += $stat['size'];
      if (!$fileInfo['in_use']) {
        $scanResults['fileAnalysis']['monthly'][$month]['abandoned_count']++;
      }
    }

    return $scanResults;
  }

  /**
   * Retrieve extension configuration settings from CiviCRM
   *
   * Loads all configurable parameters for the File Analyzer extension
   * from CiviCRM's settings system, providing defaults for missing values.
   *
   * @return array Configuration settings with defaults applied:
   *   - auto_delete: Boolean flag to enable automatic deletion
   *   - auto_delete_days: Retention period in days (default: 30)
   *   - backup_before_delete: Whether to backup before deletion (default: true)
   *   - excluded_extensions: Array of file extensions to skip during analysis
   */
  private static function getSettings() {
    return [
      // Enable/disable automatic deletion feature (safety default: false)
      'auto_delete' => Civi::settings()->get('fileanalyzer_auto_delete') ?: FALSE,

      // Number of days to retain abandoned files (default: 30 days)
      'auto_delete_days' => Civi::settings()->get('fileanalyzer_auto_delete_days') ?: 30,

      // Create backups before deletion for safety (default: enabled)
      'backup_before_delete' => Civi::settings()->get('fileanalyzer_backup_before_delete') ?: TRUE,

      // File extensions to exclude from analysis (comma-separated, filtered for empty values)
      'excluded_extensions' => array_filter(explode(',', Civi::settings()->get('fileanalyzer_excluded_extensions') ?: '')),
    ];
  }

  /**
   * Execute automatic deletion of abandoned files based on age criteria
   *
   * Processes a pre-filtered list of abandoned files and deletes those
   * that exceed the configured retention period. Includes optional backup
   * functionality for safety.
   *
   * @param string $cutoffDate Date threshold in 'Y-m-d H:i:s' format
   * @param bool $backup Whether to create backup copies before deletion
   * @param array $abandonedFiles Pre-filtered list of abandoned files from scan
   * @return int Number of files successfully deleted
   */
  private static function autoDeleteAbandonedFiles($cutoffDate, $backup = TRUE, $abandonedFiles = []) {
    $deletedCount = 0;

    // Process each abandoned file for potential deletion
    foreach ($abandonedFiles as $fileInfo) {
      // Only delete files older than the configured cutoff date
      if ($fileInfo['modified'] < $cutoffDate) {
        $filePath = $fileInfo['filename'];

        // Create safety backup if requested (recommended for production)
        if ($backup) {
          self::backupFile($filePath);
        }

        // Attempt file deletion and track success
        if (unlink($filePath)) {
          $deletedCount++;
        }
      }
    }

    return $deletedCount;
  }

  /**
   * Create timestamped backup copy of file before deletion
   *
   * Implements a safety mechanism by creating backup copies of files
   * before deletion. Backups are organized in a dedicated directory
   * structure with timestamps to prevent conflicts.
   *
   * @param string $filePath Full filesystem path to the file being backed up
   */
  private static function backupFile($filePath) {
    // Define backup directory within the CiviCRM custom upload area
    $backupDir = CRM_Core_Config::singleton()->customFileUploadDir . 'file_analyzer_backups';

    // Ensure backup directory exists (should already be created by createDirectories)
    if (!is_dir($backupDir)) {
      mkdir($backupDir, 0755, TRUE);
    }

    // Create timestamped filename to prevent backup conflicts
    // Format: YYYY-MM-DD_HH-MM-SS_originalfilename.ext
    $backupPath = $backupDir . '/deleted_files/' . date('Y-m-d_H-i-s_') . basename($filePath);

    // Ensure the deleted files subdirectory exists
    $deletedDir = dirname($backupPath);
    if (!is_dir($deletedDir)) {
      mkdir($deletedDir, 0755, TRUE);
    }

    // Create the backup copy
    copy($filePath, $backupPath);
  }

  /**
   * Check if a file is referenced in CiviCRM database tables
   *
   * Performs comprehensive database queries across multiple CiviCRM tables
   * to determine if a file is still being used by the system. This prevents
   * deletion of files that are still needed.
   *
   * Tables checked:
   * - civicrm_file: Main file registry
   * - civicrm_entity_file: File-entity relationship mappings
   * - Custom field tables: Dynamic tables storing file references
   *
   * @param string $filename Name of the file to check (basename only)
   * @return bool TRUE if file is referenced anywhere, FALSE if abandoned
   */
  public static function isFileInUse($filename) {
    // Check main civicrm_file table for direct file references
    // Uses both exact match and wildcard to catch different path formats
    $query = "
      SELECT COUNT(*) as count
      FROM civicrm_file
      WHERE uri = %1 OR uri LIKE %2
    ";
    $params = [
      1 => [$filename, 'String'],           // Exact filename match
      2 => ['%' . $filename, 'String'],     // Filename anywhere in path
    ];

    $result = CRM_Core_DAO::executeQuery($query, $params);
    $result->fetch();

    // If found in main file table, definitely in use
    if ($result->count > 0) {
      return TRUE;
    }

    // Check custom field tables for file references
    // Custom fields with data_type='File' store file references
    $customFieldQuery = "
      SELECT cg.table_name, cf.column_name
      FROM civicrm_custom_field cf
      INNER JOIN civicrm_custom_group cg ON cf.custom_group_id = cg.id
      WHERE cf.data_type = 'File' AND cg.table_name IS NOT NULL
    ";

    $customFields = CRM_Core_DAO::executeQuery($customFieldQuery);

    // Check each custom field table for file references
    while ($customFields->fetch()) {
      if ($customFields->table_name && $customFields->column_name) {
        // Verify custom table exists before querying (safety check)
        $tableExistsQuery = "SHOW TABLES LIKE %1";
        $tableCheck = CRM_Core_DAO::executeQuery($tableExistsQuery, [
          1 => [$customFields->table_name, 'String']
        ]);

        // Query custom table for file references if table exists
        if ($tableCheck->N > 0) {
          $fileQuery = "
            SELECT COUNT(*) as count
            FROM `{$customFields->table_name}`
            WHERE `{$customFields->column_name}` = %1 OR `{$customFields->column_name}` LIKE %2
          ";

          $fileResult = CRM_Core_DAO::executeQuery($fileQuery, $params);
          $fileResult->fetch();

          // If found in any custom field, file is in use
          if ($fileResult->count > 0) {
            return TRUE;
          }
        }
      }
    }

    // Check activity attachments through entity-file relationships
    // This catches files attached to activities, cases, etc.
    $activityQuery = "
      SELECT COUNT(*) as count
      FROM civicrm_entity_file ef
      INNER JOIN civicrm_file f ON ef.file_id = f.id
      WHERE f.uri = %1 OR f.uri LIKE %2
    ";

    $activityResult = CRM_Core_DAO::executeQuery($activityQuery, $params);
    $activityResult->fetch();

    // Return true if found in entity-file relationships
    return $activityResult->count > 0;
  }

  /**
   * Recursively scan directory tree for all files
   *
   * Performs comprehensive directory traversal while filtering out
   * system directories and handling errors gracefully. Uses PHP's
   * RecursiveIterator classes for efficient scanning.
   *
   * @param string $dir Root directory path to scan
   * @return array List of relative file paths found in directory tree
   */
  private static function scanDirectoryRecursive($dir) {
    $files = [];

    // Validate directory exists before attempting scan
    if (!is_dir($dir)) {
      return $files;
    }

    try {
      // Define directories to skip during scan (performance and relevance)
      $skipDirs = ['vendor', 'node_modules', 'tests', 'file_analyzer_backups'];

      // Create recursive directory iterator
      $directoryIterator = new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS);

      // Apply filter to skip unwanted directories
      $filterIterator = new RecursiveCallbackFilterIterator($directoryIterator, function ($current, $key, $iterator) use ($skipDirs) {
        if ($iterator->hasChildren()) {
          // For directories: skip if in exclusion list
          return !in_array($current->getFilename(), $skipDirs);
        }
        // Include all files (filtering happens later)
        return TRUE;
      });

      // Create iterator that processes only leaf nodes (files)
      $iterator = new RecursiveIteratorIterator($filterIterator, RecursiveIteratorIterator::LEAVES_ONLY);

      // Process each file found
      foreach ($iterator as $file) {
        if ($file->isFile()) {
          // Convert to relative path for consistent handling
          $relativePath = str_replace($dir . '/', '', $file->getPathname());
          $files[] = $relativePath;
        }
      }
    }
    catch (Exception $e) {
      // Log scanning errors but continue operation
      CRM_Core_Error::debug_log_message("File Analyzer: Error scanning directory {$dir}: " . $e->getMessage());
    }

    return $files;
  }

  /**
   * Create required directory structure for File Analyzer extension
   *
   * Sets up the complete directory hierarchy needed for extension operation,
   * including backup storage, reports, and security measures.
   *
   * @return bool TRUE on success, FALSE if directory creation fails
   */
  private static function createDirectories() {
    // Define main backup directory path
    $backupDir = CRM_Core_Config::singleton()->customFileUploadDir . 'file_analyzer_backups';

    // Create main backup directory with appropriate permissions
    if (!is_dir($backupDir)) {
      if (!mkdir($backupDir, 0755, TRUE)) {
        CRM_Core_Error::debug_log_message('FileAnalyzer: Failed to create backup directory');
        return FALSE;
      }
    }

    // Create organized subdirectory structure
    $subdirs = [
      'deleted_files',  // Stores backup copies of deleted files
      'reports'         // Stores generated reports and analysis data
    ];

    foreach ($subdirs as $subdir) {
      $path = $backupDir . '/' . $subdir;
      if (!is_dir($path)) {
        mkdir($path, 0755, TRUE);
      }
    }

    // Create .htaccess file for web security
    // Prevents direct HTTP access to backup files and sensitive data
    $htaccessPath = $backupDir . '/.htaccess';
    if (!file_exists($htaccessPath)) {
      // Apache directives to block all web access
      $htaccessContent = "Order deny,allow\nDeny from all\n";
      file_put_contents($htaccessPath, $htaccessContent);
    }

    return TRUE;
  }

  /**
   * Retrieve the most recent scan results from persistent storage
   *
   * Loads the latest scan results from JSON file for use by UI components
   * or other system processes that need current file analysis data.
   *
   * @return array|null Decoded scan results array, or null if no results exist
   */
  public static function getLatestScanResults() {
    // Construct path to latest results file
    $backupDir = CRM_Core_Config::singleton()->customFileUploadDir . 'file_analyzer_backups';
    $latestScanPath = $backupDir . '/latest_scan_results.json';

    // Load and decode results if file exists
    if (file_exists($latestScanPath)) {
      $content = file_get_contents($latestScanPath);
      return json_decode($content, TRUE);
    }

    // Return null if no scan results available yet
    return NULL;
  }

  /**
   * Retrieve abandoned files list from persistent storage
   *
   * Loads the list of abandoned files from JSON storage. This provides
   * quick access to abandoned files without requiring a full directory scan.
   *
   * @return array List of abandoned file information arrays, empty if none found
   */
  public static function getAbandonedFilesFromJson() {
    // Construct path to abandoned files data
    $backupDir = CRM_Core_Config::singleton()->customFileUploadDir . 'file_analyzer_backups';
    $abandonedPath = $backupDir . '/abandoned_files.json';

    // Load and decode abandoned files list if available
    if (file_exists($abandonedPath)) {
      $content = file_get_contents($abandonedPath);
      return json_decode($content, TRUE);
    }

    // Return empty array if no abandoned files data exists
    return [];
  }
}
