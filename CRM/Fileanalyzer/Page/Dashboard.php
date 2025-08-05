<?php
use CRM_Fileanalyzer_ExtensionUtil as E;


/**
 * CiviCRM File Analyzer Extension
 * Analyzes files in custom directory, identifies abandoned files, and provides cleanup tools
 */
// Extension Info File (info.xml should be created separately)

class CRM_FileAnalyzer_Page_Dashboard extends CRM_Core_Page {

  public function run() {
    // Set page title
    CRM_Utils_System::setTitle(ts('File Analyzer Dashboard'));

    // Get file analysis data
    $fileData = $this->getFileAnalysisData();
    $abandonedFiles = $this->getAbandonedFiles();
    $directoryStats = $this->getDirectoryStats();

    // Assign variables to template
    $this->assign('fileData', json_encode($fileData));
    $this->assign('abandonedFiles', $abandonedFiles);
    $this->assign('directoryStats', $directoryStats);
    $this->assign('totalAbandonedSize', $this->calculateTotalSize($abandonedFiles));

    // Add CSS and JS resources
    CRM_Core_Resources::singleton()
      ->addStyleFile('com.skvare.fileanalyzer', 'css/dashboard.css')
      ->addScriptFile('com.skvare.fileanalyzer', 'js/dashboard.js')
      ->addScriptUrl('https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.9.1/chart.min.js');



    parent::run();
  }

  /**
   * Get file analysis data for charts
   */
  public function getFileAnalysisData() {
    $customPath = CRM_Core_Config::singleton()->customFileUploadDir;
    $files = $this->scanDirectory($customPath);
    $monthlyData = [];
    $fileTypeData = [];

    foreach ($files as $file) {
      if (str_contains($file, $customPath) && is_file($file)) {
        $stat = stat($file);
        $month = date('Y-m', $stat['mtime']);
        $extension = strtolower(pathinfo($file, PATHINFO_EXTENSION));
        $size = $stat['size'];

        // Monthly data
        if (!isset($monthlyData[$month])) {
          $monthlyData[$month] = ['count' => 0, 'size' => 0];
        }
        $monthlyData[$month]['count']++;
        $monthlyData[$month]['size'] += $size;

        // File type data
        if (!isset($fileTypeData[$extension])) {
          $fileTypeData[$extension] = ['count' => 0, 'size' => 0];
        }
        $fileTypeData[$extension]['count']++;
        $fileTypeData[$extension]['size'] += $size;
      }
    }
    // Sort monthly data
    ksort($monthlyData);

    return [
      'monthly' => $monthlyData,
      'fileTypes' => $fileTypeData,
    ];
  }

  /**
   * Identify abandoned files (not linked to any entity)
   */
  public function getAbandonedFiles() {
    $customPath = CRM_Core_Config::singleton()->customFileUploadDir;
    $files = $this->scanDirectory($customPath);
    $abandonedFiles = [];

    foreach ($files as $file) {
      if (str_contains($file, $customPath) && is_file($file)) {
        // Check if file is referenced in civicrm_file table
        $fileInUse = $this->isFileInUse($file);

        if (!$fileInUse) {
          $stat = stat($file);
          $abandonedFiles[] = [
            'filename' => $file,
            'filenameOnly' => basename($file),
            'size' => $stat['size'],
            'modified' => date('Y-m-d H:i:s', $stat['mtime']),
            'extension' => strtolower(pathinfo($file, PATHINFO_EXTENSION)),
            'path' => $file,
          ];
        }
      }
    }

    return $abandonedFiles;
  }

  /**
   * Check if a file is referenced in CiviCRM database
   */
  public function isFileInUse($filename) {
    $filename = basename($filename);
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

    // Check for custom field file references
    $customFieldQuery = "
      SELECT COUNT(*) as count
      FROM civicrm_custom_field cf
      INNER JOIN civicrm_custom_group cg ON cf.custom_group_id = cg.id
      WHERE cf.data_type = 'File'
    ";

    $customFields = CRM_Core_DAO::executeQuery($customFieldQuery);

    while ($customFields->fetch()) {
      // Check each custom field table for file references
      $tableName = $customFields->table_name;
      $columnName = $customFields->column_name;

      if ($tableName && $columnName) {
        $fileQuery = "
          SELECT COUNT(*) as count
          FROM `{$tableName}`
          WHERE `{$columnName}` = %1 OR `{$columnName}` LIKE %2
        ";

        $fileResult = CRM_Core_DAO::executeQuery($fileQuery, $params);
        $fileResult->fetch();

        if ($fileResult->count > 0) {
          return TRUE;
        }
      }
    }

    return FALSE;
  }

  /**
   * Get directory statistics
   */
  public function getDirectoryStats() {
    $customPath = CRM_Core_Config::singleton()->customFileUploadDir;
    $files = $this->scanDirectory($customPath);

    $totalSize = 0;
    $totalFiles = 0;
    $oldestFile = NULL;
    $newestFile = NULL;

    foreach ($files as $file) {
      if (str_contains($file, $customPath) && is_file($file)) {
        $stat = stat($file);
        $totalSize += $stat['size'];
        $totalFiles++;

        if (!$oldestFile || $stat['mtime'] < $oldestFile['time']) {
          $oldestFile = ['name' => $file, 'time' => $stat['mtime']];
        }

        if (!$newestFile || $stat['mtime'] > $newestFile['time']) {
          $newestFile = ['name' => $file, 'time' => $stat['mtime']];
        }
      }
    }

    return [
      'totalSize' => $totalSize,
      'totalFiles' => $totalFiles,
      'oldestFile' => $oldestFile,
      'newestFile' => $newestFile,
    ];
  }

  /**
   * Recursively scan directory
   */
  private function scanDirectory($dir) {
    $files = [];
    if (is_dir($dir)) {
      $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS),
        RecursiveIteratorIterator::LEAVES_ONLY
      );

      foreach ($iterator as $file) {
        if ($file->isFile()) {
          $relativePath = str_replace($dir . '/', '', $file->getPathname());
          $files[] = $relativePath;
        }
      }
    }
    return $files;
  }

  /**
   * Calculate total size of files
   */
  private function calculateTotalSize($files) {
    $total = 0;
    foreach ($files as $file) {
      $total += $file['size'];
    }
    return $total;
  }
}

