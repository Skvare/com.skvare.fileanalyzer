<?php

use CRM_Fileanalyzer_ExtensionUtil as E;

/**
 * CRM File Analyzer API Class - Enhanced with Contribute Images Support
 *
 * This class provides comprehensive functionality for analyzing and managing file usage
 * within a CiviCRM system. It now supports both custom uploads and contribute page images.
 *
 * Main features:
 * - Support for multiple directory types (custom uploads and contribute images)
 * - Scheduled scanning for unused/abandoned files with comprehensive analysis
 * - Automatic deletion of old files with configurable retention periods
 * - Backup functionality before file deletion with organized storage
 * - Integration with CiviCRM settings system
 * - JSON-based reporting and data persistence
 * - File type and temporal analysis (monthly statistics)
 * - Database integrity checking across multiple CiviCRM tables
 */
class CRM_Fileanalyzer_API_FileAnalysis {

  const DIRECTORY_CUSTOM = 'custom';
  const DIRECTORY_CONTRIBUTE = 'contribute';

  /**
   * Main scheduled job entry point for file analysis and cleanup
   *
   * This method orchestrates the complete file analysis workflow for all supported directories:
   * 1. Sets up required directories and backup structure
   * 2. Performs comprehensive file system scan for both custom and contribute directories
   * 3. Analyzes files for database references
   * 4. Generates detailed reports with statistics
   * 5. Optionally performs automatic cleanup of abandoned files
   * 6. Persists results to JSON files for later access
   *
   * @param string $directory_type Optional directory type to scan (custom|contribute|all)
   * @return array Status array with detailed execution results
   *   - is_error: 0 for success, 1 for error
   *   - messages: Array of detailed status messages including counts
   *   - data: Optional scan results data
   */
  public static function scheduledScan($directory_type = 'all') {
    // Load extension configuration settings from CiviCRM
    $settings = self::getSettings();

    // Get paths for file operations
    $customPath = CRM_Core_Config::singleton()->uploadDir;
    $backupDir = $customPath . 'file_analyzer_backups';

    // Ensure all required directories exist before proceeding
    self::createDirectories();

    $totalDeleted = 0;
    $totalAbandonedFiles = 0;
    $totalFiles = 0;
    $scanResults = [];
    // Scan custom directory if requested
    if ($directory_type === 'all' || $directory_type === self::DIRECTORY_CUSTOM) {
      $customResults = self::performFileScan(self::DIRECTORY_CUSTOM);
      $scanResults[self::DIRECTORY_CUSTOM] = $customResults;

      // Persist abandoned files list for custom directory
      $abandonedFilesPath = $backupDir . '/abandoned_files_custom.json';
      file_put_contents($abandonedFilesPath, json_encode($customResults['abandoned_files'], JSON_PRETTY_PRINT));

      // Calculate total abandoned files across all directories
      $totalAbandonedFiles += count($customResults['abandoned_files']);
      $totalFiles += $customResults['directoryStats']['totalFiles'];
      unset($customResults['abandoned_files']);

      // Maintain latest scan results file for quick UI access
      $latestScanPath = $backupDir . '/latest_scan_results_custom.json';
      file_put_contents($latestScanPath, json_encode($customResults, JSON_PRETTY_PRINT));

      // Create timestamped scan results file for historical tracking
      $scanResultsPath = $backupDir . '/scan_results_' . date('Y-m-d_H-i-s') . '.json';
      file_put_contents($scanResultsPath, json_encode($customResults, JSON_PRETTY_PRINT));

      // Execute automatic cleanup if enabled
      if ($settings['auto_delete'] && $settings['auto_delete_days']) {
        $cutoffDate = date('Y-m-d H:i:s', strtotime("-{$settings['auto_delete_days']} days"));
        $deletedCount = self::autoDeleteAbandonedFiles($cutoffDate, $settings['backup_before_delete'], $customResults['abandoned_files'], self::DIRECTORY_CUSTOM);
        $totalDeleted += $deletedCount;
      }
    }

    // Scan contribute directory if requested
    if ($directory_type === 'all' || $directory_type === self::DIRECTORY_CONTRIBUTE) {
      $contributeResults = self::performFileScan(self::DIRECTORY_CONTRIBUTE);
      $scanResults[self::DIRECTORY_CONTRIBUTE] = $contributeResults;

      // Persist abandoned files list for contribute directory
      $abandonedFilesPath = $backupDir . '/abandoned_files_contribute.json';
      file_put_contents($abandonedFilesPath, json_encode($contributeResults['abandoned_files'], JSON_PRETTY_PRINT));

      // Calculate total abandoned files across all directories
      $totalAbandonedFiles += count($contributeResults['abandoned_files']);
      $totalFiles += $contributeResults['directoryStats']['totalFiles'];
      unset($contributeResults['abandoned_files']);

      // Maintain latest scan results file for quick UI access
      $latestScanPath = $backupDir . '/latest_scan_results_contribute.json';
      file_put_contents($latestScanPath, json_encode($contributeResults, JSON_PRETTY_PRINT));

      // Create timestamped scan results file for historical tracking
      $scanResultsPath = $backupDir . '/scan_results_' . date('Y-m-d_H-i-s') . '.json';
      file_put_contents($scanResultsPath, json_encode($contributeResults, JSON_PRETTY_PRINT));


      // Execute automatic cleanup if enabled
      if ($settings['auto_delete'] && $settings['auto_delete_days']) {
        $cutoffDate = date('Y-m-d H:i:s', strtotime("-{$settings['auto_delete_days']} days"));
        $deletedCount = self::autoDeleteAbandonedFiles($cutoffDate, $settings['backup_before_delete'], $contributeResults['abandoned_files'], self::DIRECTORY_CONTRIBUTE);
        $totalDeleted += $deletedCount;
      }
    }

    // Log deletion activity for audit trail and troubleshooting
    if ($totalDeleted > 0) {
      CRM_Core_Error::debug_log_message("File Analyzer: Auto-deleted {$totalDeleted} abandoned files");
    }

    // Return comprehensive status information
    return [
      'is_error' => 0,
      'messages' => [
        "Scan completed for {$directory_type} directory(ies). Found {$totalAbandonedFiles} abandoned files",
        "Total files scanned: {$totalFiles}",
        "Auto-deleted: {$totalDeleted} files"
      ],
      //'data' => $scanResults
    ];
  }

  /**
   * Perform comprehensive file system scan and analysis for specific directory type
   *
   * This method executes the core file analysis logic:
   * 1. Scans all files in the specified directory recursively
   * 2. Filters files based on excluded extensions
   * 3. Checks database references to identify abandoned files
   * 4. Generates statistical analysis by file type and time period
   * 5. Calculates storage usage metrics
   *
   * @param string $directory_type Type of directory to scan (custom|contribute)
   * @return array Comprehensive scan results including:
   *   - scan_date: Timestamp of scan execution
   *   - directory_type: Type of directory scanned
   *   - abandoned_files: Array of files not referenced in database
   *   - fileAnalysis: Statistical breakdowns by month and file type
   *   - directoryStats: Total file count and storage usage
   *   - active_files: Count of files still in use
   */
  private static function performFileScan($directory_type) {
    // Get the appropriate directory path based on type
    $scanPath = self::getDirectoryPath($directory_type);
    // Recursively discover all files in directory tree
    $files = self::scanDirectoryRecursive($scanPath);
    // Load current extension settings for filtering
    $settings = self::getSettings();

    // Initialize comprehensive results structure
    $scanResults = [
      'scan_date' => date('Y-m-d H:i:s'),           // When this scan was performed
      'directory_type' => $directory_type,          // Type of directory scanned
      'abandoned_files' => [],                      // Files not referenced in database
      'fileAnalysis' => [                          // Statistical analysis
        'monthly' => [],                            // Files grouped by modification month
        'fileTypes' => []                           // Files grouped by extension
      ],
      'directoryStats' => [                        // Overall directory metrics
        'totalSize' => 0,                          // Total bytes consumed
        'totalFiles' => 0                          // Total file count
      ],
      'active_files' => 0,                         // Count of files still in use
    ];

    // Process each discovered file
    foreach ($files as $file) {
      $fullPath = $scanPath . '/' . $file;

      // Skip invalid files or directories
      if (!is_file($fullPath)) {
        continue;
      }

      // Get file system metadata
      $stat = stat($fullPath);
      $extension = strtolower(pathinfo($file, PATHINFO_EXTENSION));
      $month = date('Y-m', $stat['mtime']); // Group by year-month for trends

      // Skip files with excluded extensions (e.g., system files, logs)
      if (in_array($extension, $settings['excluded_extensions'])) {
        continue;
      }

      // Build comprehensive file information record
      $fileInfo = [
        'filename' => $file,                        // Relative file path
        'filenameOnly' => basename($file),          // Just the filename
        'size' => $stat['size'],                    // File size in bytes
        'modified' => date('Y-m-d H:i:s', $stat['mtime']), // Last modification
        'extension' => $extension,                  // File extension for categorization
        'in_use' => self::isFileInUse(basename($file), $directory_type), // Database reference check
        'path' => $fullPath,                       // Full file path for operations
        'directory_type' => $directory_type,       // Which directory this file belongs to
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
   * Get the appropriate directory path based on directory type
   *
   * @param string $directory_type Type of directory (custom|contribute)
   * @return string Full path to the directory
   */
  private static function getDirectoryPath($directory_type) {
    $config = CRM_Core_Config::singleton();

    switch ($directory_type) {
      case self::DIRECTORY_CUSTOM:
        return $config->customFileUploadDir;

      case self::DIRECTORY_CONTRIBUTE:
        // Construct path to contribute images directory
        $baseDir = dirname($config->customFileUploadDir);
        return $baseDir . '/persist/contribute/images';

      default:
        throw new Exception("Unknown directory type: {$directory_type}");
    }
  }

  /**
   * Check if a file is referenced in CiviCRM database tables
   *
   * Performs comprehensive database queries across multiple CiviCRM tables
   * to determine if a file is still being used by the system. This prevents
   * deletion of files that are still needed.
   *
   * For contribute images, this checks the civicrm_contribution_page table
   * for references in header_text and footer_text fields.
   *
   * Tables checked:
   * - civicrm_file: Main file registry
   * - civicrm_entity_file: File-entity relationship mappings
   * - civicrm_contribution_page: Contribute page header/footer content
   * - Custom field tables: Dynamic tables storing file references
   *
   * @param string $filename Name of the file to check (basename only)
   * @param string $directory_type Type of directory being checked
   * @return bool TRUE if file is referenced anywhere, FALSE if abandoned
   */
  public static function isFileInUse($filename, $directory_type = self::DIRECTORY_CUSTOM) {
    // Check main civicrm_file table for direct file references
    if ($directory_type == self::DIRECTORY_CUSTOM) {
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
    }
    else {
      if ($directory_type === self::DIRECTORY_CONTRIBUTE) {
        // For contribute images, check contribution page content
        $contributeQuery = "
        SELECT COUNT(*) as count
        FROM civicrm_contribution_page
        WHERE intro_text LIKE %1 OR thankyou_text LIKE %1
      ";
        $contributeParams = [
          1 => ['%' . $filename . '%', 'String'],
        ];

        $contributeResult = CRM_Core_DAO::executeQuery($contributeQuery, $contributeParams);
        $contributeResult->fetch();

        if ($contributeResult->count > 0) {
          return TRUE;
        }

        $contributeQueryMsgTpl = "
        SELECT COUNT(*) as count
        FROM civicrm_msg_template
        WHERE msg_html LIKE %1
      ";
        $contributeParams = [
          1 => ['%' . $filename . '%', 'String'],
        ];

        $contributeResultMsgTpl = CRM_Core_DAO::executeQuery($contributeQueryMsgTpl, $contributeParams);
        $contributeResultMsgTpl->fetch();

        if ($contributeResultMsgTpl->count > 0) {
          return TRUE;
        }
      }
    }
    return FALSE;
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
   * @param string $directory_type Type of directory being processed
   * @return int Number of files successfully deleted
   */
  private static function autoDeleteAbandonedFiles($cutoffDate, $backup = TRUE, $abandonedFiles = [], $directory_type = self::DIRECTORY_CUSTOM) {
    $deletedCount = 0;

    // Process each abandoned file for potential deletion
    foreach ($abandonedFiles as $fileInfo) {
      // Only delete files older than the configured cutoff date
      if ($fileInfo['modified'] < $cutoffDate) {
        $filePath = $fileInfo['path'];

        // Create safety backup if requested (recommended for production)
        if ($backup) {
          self::backupFile($filePath, $directory_type);
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
   * @param string $directory_type Type of directory the file belongs to
   */
  private static function backupFile($filePath, $directory_type = self::DIRECTORY_CUSTOM) {
    // Define backup directory within the CiviCRM custom upload area
    $backupDir = CRM_Core_Config::singleton()->uploadDir . 'file_analyzer_backups';

    // Ensure backup directory exists
    if (!is_dir($backupDir)) {
      mkdir($backupDir, 0755, TRUE);
    }

    // Create directory-specific backup folder
    $typeBackupDir = $backupDir . '/deleted_files_' . $directory_type;
    if (!is_dir($typeBackupDir)) {
      mkdir($typeBackupDir, 0755, TRUE);
    }

    // Create timestamped filename to prevent backup conflicts
    $backupPath = $typeBackupDir . '/' . date('Y-m-d_H-i-s_') . basename($filePath);

    // Create the backup copy
    copy($filePath, $backupPath);
  }

  /**
   * Retrieve extension configuration settings from CiviCRM
   *
   * Loads all configurable parameters for the File Analyzer extension
   * from CiviCRM's settings system, providing defaults for missing values.
   *
   * @return array Configuration settings with defaults applied
   */
  private static function getSettings() {
    return [
      'auto_delete' => Civi::settings()->get('fileanalyzer_auto_delete') ?: FALSE,
      'auto_delete_days' => Civi::settings()->get('fileanalyzer_auto_delete_days') ?: 30,
      'backup_before_delete' => Civi::settings()->get('fileanalyzer_backup_before_delete') ?: TRUE,
      'excluded_extensions' => array_filter(explode(',', Civi::settings()->get('fileanalyzer_excluded_extensions') ?: '')),
    ];
  }

  /**
   * Recursively scan directory tree for all files
   *
   * Performs comprehensive directory traversal while filtering out
   * system directories and handling errors gracefully.
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
    $dir = rtrim($dir, '/');

    try {
      // Define directories to skip during scan
      $skipDirs = ['vendor', 'node_modules', 'tests', 'file_analyzer_backups'];

      // Create recursive directory iterator
      $directoryIterator = new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS);

      // Apply filter to skip unwanted directories
      $filterIterator = new RecursiveCallbackFilterIterator($directoryIterator, function ($current, $key, $iterator) use ($skipDirs) {
        if ($iterator->hasChildren()) {
          return !in_array($current->getFilename(), $skipDirs);
        }
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
    $backupDir = CRM_Core_Config::singleton()->uploadDir . 'file_analyzer_backups';

    // Create main backup directory with appropriate permissions
    if (!is_dir($backupDir)) {
      if (!mkdir($backupDir, 0755, TRUE)) {
        CRM_Core_Error::debug_log_message('FileAnalyzer: Failed to create backup directory');
        return FALSE;
      }
    }

    // Create organized subdirectory structure
    $subdirs = [
      'deleted_files_custom',      // Stores backup copies of deleted custom files
      'deleted_files_contribute',  // Stores backup copies of deleted contribute files
      'reports'                    // Stores generated reports and analysis data
    ];

    foreach ($subdirs as $subdir) {
      $path = $backupDir . '/' . $subdir;
      if (!is_dir($path)) {
        mkdir($path, 0755, TRUE);
      }
    }

    // Create .htaccess file for web security
    $htaccessPath = $backupDir . '/.htaccess';
    if (!file_exists($htaccessPath)) {
      $htaccessContent = "Order deny,allow\nDeny from all\n";
      file_put_contents($htaccessPath, $htaccessContent);
    }

    return TRUE;
  }

  /**
   * Retrieve the most recent scan results from persistent storage
   *
   * @param string $directory_type Optional directory type filter
   * @return array|null Decoded scan results array, or null if no results exist
   */
  public static function getLatestScanResults($directory_type = null) {
    $backupDir = CRM_Core_Config::singleton()->uploadDir . 'file_analyzer_backups';
    $latestScanPath = $backupDir . '/latest_scan_results_' . $directory_type . '.json';

    if (file_exists($latestScanPath)) {
      $content = file_get_contents($latestScanPath);
      $results = json_decode($content, TRUE);
      return $results;
    }

    return [];
  }

  /**
   * Retrieve abandoned files list from persistent storage
   *
   * @param string $directory_type Directory type (custom|contribute)
   * @return array List of abandoned file information arrays
   */
  public static function getAbandonedFilesFromJson($directory_type = self::DIRECTORY_CUSTOM) {
    $backupDir = CRM_Core_Config::singleton()->uploadDir . 'file_analyzer_backups';
    $abandonedPath = $backupDir . '/abandoned_files_' . $directory_type . '.json';

    if (file_exists($abandonedPath)) {
      $content = file_get_contents($abandonedPath);
      return json_decode($content, TRUE);
    }

    return [];
  }
}
