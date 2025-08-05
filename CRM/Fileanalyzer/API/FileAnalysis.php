<?php

use CRM_Fileanalyzer_ExtensionUtil as E;

class CRM_FileAnalyzer_API_FileAnalysis {

  /**
   * Scheduled job to scan for abandoned files
   */
  public static function scheduledScan() {
    $settings = self::getSettings();

    if ($settings['auto_delete'] && $settings['auto_delete_days']) {
      $cutoffDate = date('Y-m-d H:i:s', strtotime("-{$settings['auto_delete_days']} days"));
      $deletedCount = self::autoDeleteAbandonedFiles($cutoffDate, $settings['backup_before_delete']);

      CRM_Core_Error::debug_log_message("File Analyzer: Auto-deleted {$deletedCount} abandoned files");

      return [
        'is_error' => 0,
        'messages' => ["Auto-deleted {$deletedCount} abandoned files older than {$settings['auto_delete_days']} days"],
      ];
    }

    return [
      'is_error' => 0,
      'messages' => ['File scan completed - auto-delete is disabled'],
    ];
  }

  /**
   * Get extension settings
   */
  private static function getSettings() {
    return [
      'scan_interval' => Civi::settings()->get('fileanalyzer_scan_interval'),
      'auto_delete' => Civi::settings()->get('fileanalyzer_auto_delete'),
      'auto_delete_days' => Civi::settings()->get('fileanalyzer_auto_delete_days'),
      'backup_before_delete' => Civi::settings()->get('fileanalyzer_backup_before_delete'),
      'excluded_extensions' => explode(',', Civi::settings()->get('fileanalyzer_excluded_extensions')),
    ];
  }

  /**
   * Auto-delete abandoned files older than specified date
   */
  private static function autoDeleteAbandonedFiles($cutoffDate, $backup = TRUE) {
    $customPath = CRM_Core_Config::singleton()->customFileUploadDir;
    $files = self::scanDirectoryRecursive($customPath);
    $deletedCount = 0;

    foreach ($files as $file) {
      $filePath = $customPath . '/' . $file;
      if (is_file($filePath)) {
        $stat = stat($filePath);
        $modifiedDate = date('Y-m-d H:i:s', $stat['mtime']);

        if ($modifiedDate < $cutoffDate && !self::isFileInUse($file)) {
          if ($backup) {
            self::backupFile($filePath);
          }

          if (unlink($filePath)) {
            $deletedCount++;
          }
        }
      }
    }

    return $deletedCount;
  }

  /**
   * Create backup of file before deletion
   */
  private static function backupFile($filePath) {
    $backupDir = CRM_Core_Config::singleton()->customFileUploadDir . '/file_analyzer_backups';

    if (!is_dir($backupDir)) {
      mkdir($backupDir, 0755, TRUE);
    }

    $backupPath = $backupDir . '/' . date('Y-m-d_H-i-s_') . basename($filePath);
    copy($filePath, $backupPath);
  }

  /**
   * Check if file is referenced in database
   */
  private static function isFileInUse($filename) {
    // Implementation same as in main class
    // ... (code from earlier implementation)
    return FALSE; // Placeholder
  }

  /**
   * Recursively scan directory
   */
  private static function scanDirectoryRecursive($dir) {
    // Implementation same as in main class
    // ... (code from earlier implementation)
    return []; // Placeholder
  }

  /**
   * Create necessary directories
   */
  private function createDirectories() {
    $backupDir = CRM_Core_Config::singleton()->customFileUploadDir . '/file_analyzer_backups';

    if (!is_dir($backupDir)) {
      if (!mkdir($backupDir, 0755, TRUE)) {
        CRM_Core_Error::debug_log_message('FileAnalyzer: Failed to create backup directory');
      }
    }

    // Create .htaccess to protect backup directory
    $htaccessPath = $backupDir . '/.htaccess';
    if (!file_exists($htaccessPath)) {
      $htaccessContent = "Order deny,allow\nDeny from all\n";
      file_put_contents($htaccessPath, $htaccessContent);
    }
  }
}
