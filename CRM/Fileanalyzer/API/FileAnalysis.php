<?php

use CRM_Fileanalyzer_ExtensionUtil as E;

class CRM_FileAnalyzer_API_FileAnalysis {

  /**
   * Scheduled job to scan for abandoned files and store results in JSON
   */
  public static function scheduledScan() {
    $settings = self::getSettings();
    $customPath = CRM_Core_Config::singleton()->customFileUploadDir;
    $backupDir = $customPath . 'file_analyzer_backups';

    // Ensure backup directory exists
    self::createDirectories();

    // Perform comprehensive file scan
    $scanResults = self::performFileScan();

    // Store abandoned files separately
    $abandonedFilesPath = $backupDir . '/abandoned_files.json';
    file_put_contents($abandonedFilesPath, json_encode($scanResults['abandoned_files'], JSON_PRETTY_PRINT));
    // Auto-delete if enabled
    $deletedCount = 0;
    if ($settings['auto_delete'] && $settings['auto_delete_days']) {
      $cutoffDate = date('Y-m-d H:i:s', strtotime("-{$settings['auto_delete_days']} days"));
      $deletedCount = self::autoDeleteAbandonedFiles($cutoffDate, $settings['backup_before_delete'], $scanResults['abandoned_files']);
      CRM_Core_Error::debug_log_message("File Analyzer: Auto-deleted {$deletedCount} abandoned files");
    }
    $abandonedFilesCount = count($scanResults['abandoned_files']);

    unset($scanResults['abandoned_files']);
    // Store scan results in JSON file
    $scanResultsPath = $backupDir . '/scan_results_' . date('Y-m-d_H-i-s') . '.json';
    file_put_contents($scanResultsPath, json_encode($scanResults, JSON_PRETTY_PRINT));

    // Store latest scan results (overwrites previous)
    $latestScanPath = $backupDir . '/latest_scan_results.json';
    file_put_contents($latestScanPath, json_encode($scanResults, JSON_PRETTY_PRINT));

    return [
      'is_error' => 0,
      'messages' => [
        "Scan completed. Found " . $abandonedFilesCount . " abandoned files",
        "Total files scanned: " . $scanResults['directoryStats']['totalFiles'],
        "Auto-deleted: {$deletedCount} files"
      ],
      //'data' => $scanResults
    ];
  }

  /**
   * Perform comprehensive file scan and analysis
   */
  private static function performFileScan() {
    $customPath = CRM_Core_Config::singleton()->customFileUploadDir;
    $files = self::scanDirectoryRecursive($customPath);
    $settings = self::getSettings();
    $scanResults = [
      'scan_date' => date('Y-m-d H:i:s'),
      'abandoned_files' => [],
      'fileAnalysis' => ['monthly' => [], 'fileTypes' => []],
      'directoryStats' => ['totalSize' => 0, 'totalFiles' => 0],
    ];

    foreach ($files as $file) {
      $fullPath = $customPath . '/' . $file;
      if (!str_contains($file, $customPath) || !is_file($file)) {
        continue;
      }
      $stat = stat($file);
      $extension = strtolower(pathinfo($file, PATHINFO_EXTENSION));
      $month = date('Y-m', $stat['mtime']);

      // Skip excluded extensions
      if (in_array($extension, $settings['excluded_extensions'])) {
        continue;
      }

      $fileInfo = [
        'filename' => $file,
        'filenameOnly' => basename($file),
        'size' => $stat['size'],
        'modified' => date('Y-m-d H:i:s', $stat['mtime']),
        'extension' => $extension,
        'in_use' => self::isFileInUse(basename($file)),
        'path' => $file,
      ];

      $scanResults['directoryStats']['totalFiles']++;
      $scanResults['directoryStats']['totalSize'] += $stat['size'];

      // Categorize files
      if ($fileInfo['in_use']) {
        $scanResults['active_files']++;
      }
      else {
        $scanResults['abandoned_files'][] = $fileInfo;
      }

      // File type statistics
      if (!isset($scanResults['fileAnalysis']['fileTypes'][$extension])) {
        $scanResults['fileAnalysis']['fileTypes'][$extension] = ['count' => 0, 'size' => 0, 'abandoned_count' => 0];
      }
      $scanResults['fileAnalysis']['fileTypes'][$extension]['count']++;
      $scanResults['fileAnalysis']['fileTypes'][$extension]['size'] += $stat['size'];
      if (!$fileInfo['in_use']) {
        $scanResults['fileAnalysis']['fileTypes'][$extension]['abandoned_count']++;
      }

      // Monthly statistics
      if (!isset($scanResults['fileAnalysis']['monthly'][$month])) {
        $scanResults['fileAnalysis']['monthly'][$month] = ['count' => 0, 'size' => 0, 'abandoned_count' => 0];
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
   * Get extension settings
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
   * Auto-delete abandoned files older than specified date
   */
  private static function autoDeleteAbandonedFiles($cutoffDate, $backup = TRUE, $abandonedFiles = []) {
    $deletedCount = 0;
    foreach ($abandonedFiles as $fileInfo) {
      if ($fileInfo['modified'] < $cutoffDate) {
        $filePath = $fileInfo['filename'];

        if ($backup) {
          self::backupFile($filePath);
        }
        if (unlink($filePath)) {
          $deletedCount++;
        }
      }
    }

    return $deletedCount;
  }

  /**
   * Create backup of file before deletion
   */
  private static function backupFile($filePath) {
    $backupDir = CRM_Core_Config::singleton()->customFileUploadDir . 'file_analyzer_backups';

    if (!is_dir($backupDir)) {
      mkdir($backupDir, 0755, TRUE);
    }

    $backupPath = $backupDir . '/deleted_files/' . date('Y-m-d_H-i-s_') . basename($filePath);
    // Create deleted files subdirectory
    $deletedDir = dirname($backupPath);
    if (!is_dir($deletedDir)) {
      mkdir($deletedDir, 0755, TRUE);
    }
    copy($filePath, $backupPath);
  }

  /**
   * Check if file is referenced in database
   */
  public static function isFileInUse($filename) {
    // Check civicrm_file table
    $query = "
      SELECT COUNT(*) as count
      FROM civicrm_file
      WHERE uri = %1 OR uri LIKE %2
    ";
    $params = [
      1 => [$filename, 'String'],
      2 => ['%' . $filename, 'String'],
    ];

    $result = CRM_Core_DAO::executeQuery($query, $params);
    $result->fetch();

    if ($result->count > 0) {
      return TRUE;
    }

    // Check custom field file references
    $customFieldQuery = "
      SELECT cg.table_name, cf.column_name
      FROM civicrm_custom_field cf
      INNER JOIN civicrm_custom_group cg ON cf.custom_group_id = cg.id
      WHERE cf.data_type = 'File' AND cg.table_name IS NOT NULL
    ";

    $customFields = CRM_Core_DAO::executeQuery($customFieldQuery);

    while ($customFields->fetch()) {
      if ($customFields->table_name && $customFields->column_name) {
        // Check if table exists
        $tableExistsQuery = "SHOW TABLES LIKE %1";
        $tableCheck = CRM_Core_DAO::executeQuery($tableExistsQuery, [
          1 => [$customFields->table_name, 'String']
        ]);

        if ($tableCheck->N > 0) {
          $fileQuery = "
            SELECT COUNT(*) as count
            FROM `{$customFields->table_name}`
            WHERE `{$customFields->column_name}` = %1 OR `{$customFields->column_name}` LIKE %2
          ";

          $fileResult = CRM_Core_DAO::executeQuery($fileQuery, $params);
          $fileResult->fetch();

          if ($fileResult->count > 0) {
            return TRUE;
          }
        }
      }
    }

    // Check activity attachments
    $activityQuery = "
      SELECT COUNT(*) as count
      FROM civicrm_entity_file ef
      INNER JOIN civicrm_file f ON ef.file_id = f.id
      WHERE f.uri = %1 OR f.uri LIKE %2
    ";

    $activityResult = CRM_Core_DAO::executeQuery($activityQuery, $params);
    $activityResult->fetch();

    return $activityResult->count > 0;
  }

  /**
   * Recursively scan directory
   */
  private static function scanDirectoryRecursive($dir) {
    $files = [];
    if (!is_dir($dir)) {
      return $files;
    }

    try {
      /*
      $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS),
        RecursiveIteratorIterator::LEAVES_ONLY
      );
      */
      $skipDirs = ['vendor', 'node_modules', 'tests', 'file_analyzer_backups'];
      $directoryIterator = new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS);
      $filterIterator = new RecursiveCallbackFilterIterator($directoryIterator, function ($current, $key, $iterator) use ($skipDirs) {
        if ($iterator->hasChildren()) {
          // Skip directories in the skip list
          return !in_array($current->getFilename(), $skipDirs);
        }
        return TRUE; // include all files
      });

      $iterator = new RecursiveIteratorIterator($filterIterator, RecursiveIteratorIterator::LEAVES_ONLY);

      foreach ($iterator as $file) {
        if ($file->isFile()) {
          $relativePath = str_replace($dir . '/', '', $file->getPathname());
          $files[] = $relativePath;
        }
      }
    }
    catch (Exception $e) {
      CRM_Core_Error::debug_log_message("File Analyzer: Error scanning directory {$dir}: " . $e->getMessage());
    }

    return $files;
  }

  /**
   * Create necessary directories
   */
  private static function createDirectories() {
    $backupDir = CRM_Core_Config::singleton()->customFileUploadDir . 'file_analyzer_backups';

    if (!is_dir($backupDir)) {
      if (!mkdir($backupDir, 0755, TRUE)) {
        CRM_Core_Error::debug_log_message('FileAnalyzer: Failed to create backup directory');
        return FALSE;
      }
    }

    // Create subdirectories
    $subdirs = ['deleted_files', 'reports'];
    foreach ($subdirs as $subdir) {
      $path = $backupDir . '/' . $subdir;
      if (!is_dir($path)) {
        mkdir($path, 0755, TRUE);
      }
    }

    // Create .htaccess to protect backup directory
    $htaccessPath = $backupDir . '/.htaccess';
    if (!file_exists($htaccessPath)) {
      $htaccessContent = "Order deny,allow\nDeny from all\n";
      file_put_contents($htaccessPath, $htaccessContent);
    }

    return TRUE;
  }

  /**
   * Get latest scan results from JSON file
   */
  public static function getLatestScanResults() {
    $backupDir = CRM_Core_Config::singleton()->customFileUploadDir . 'file_analyzer_backups';
    $latestScanPath = $backupDir . '/latest_scan_results.json';

    if (file_exists($latestScanPath)) {
      $content = file_get_contents($latestScanPath);
      return json_decode($content, TRUE);
    }

    return NULL;
  }

  /**
   * Get abandoned files from JSON file
   */
  public static function getAbandonedFilesFromJson() {
    $backupDir = CRM_Core_Config::singleton()->customFileUploadDir . 'file_analyzer_backups';
    $abandonedPath = $backupDir . '/abandoned_files.json';

    if (file_exists($abandonedPath)) {
      $content = file_get_contents($abandonedPath);
      return json_decode($content, TRUE);
    }

    return [];
  }
}
