<?php

/**
 * FileAnalyzer.DeleteFile API specification
 */
function _civicrm_api3_file_analyzer_deleteFile_spec(&$spec) {
  $spec['file_path']['description'] = 'Full path to the file to delete';
  $spec['file_path']['type'] = 'String';
  $spec['file_path']['api.required'] = 1;
}

/**
 * Delete a single file with backup
 */
function civicrm_api3_file_analyzer_delete_file($params) {
  $filePath = $params['file_path'];

  if (!file_exists($filePath)) {
    throw new API_Exception('File not found: ' . $filePath);
  }

  // Create backup before deletion
  $backupResult = _file_analyzer_backup_file($filePath);

  if ($backupResult && unlink($filePath)) {
    return civicrm_api3_create_success([
      'message' => 'File deleted successfully',
      'backup_location' => $backupResult
    ]);
  }
  else {
    throw new API_Exception('Failed to delete file: ' . $filePath);
  }
}

/**
 * FileAnalyzer.BulkDeleteAbandoned API specification
 */
function _civicrm_api3_file_analyzer_bulkDeleteAbandoned_spec(&$spec) {
  $spec['directory']['description'] = 'Directory to clean up (custom|contribute)';
  $spec['directory']['type'] = 'String';
  $spec['directory']['api.default'] = 'custom';
  $spec['max_files']['description'] = 'Maximum number of files to delete in one operation';
  $spec['max_files']['type'] = 'Integer';
  $spec['max_files']['api.default'] = 100;
}

/**
 * Bulk delete abandoned files for a directory
 */
function civicrm_api3_file_analyzer_bulk_delete_abandoned($params) {
  $directory = CRM_Utils_Array::value('directory', $params, 'custom');

  // Get abandoned files list
  $abandonedFiles = _file_analyzer_get_abandoned_files($directory);

  if (empty($abandonedFiles)) {
    return civicrm_api3_create_success([
      'message' => 'No abandoned files found',
      'deleted_count' => 0
    ]);
  }

  $deletedCount = 0;
  $errors = [];
  $backupDir = _file_analyzer_get_backup_directory($directory);

  foreach ($abandonedFiles as $fileInfo) {
    $filePath = $fileInfo['path'];

    try {
      // Backup file
      $backupResult = _file_analyzer_backup_file($filePath, $backupDir);

      if ($backupResult && file_exists($filePath)) {
        if (unlink($filePath)) {
          $deletedCount++;
        }
        else {
          $errors[] = 'Failed to delete: ' . basename($filePath);
        }
      }
    }
    catch (Exception $e) {
      $errors[] = 'Error with ' . basename($filePath) . ': ' . $e->getMessage();
    }
  }

  // Update scan results after bulk deletion
  if ($deletedCount > 0) {
    CRM_Fileanalyzer_API_FileAnalysis::scheduledScan();
  }

  $result = [
    'message' => "Deleted {$deletedCount} abandoned files",
    'deleted_count' => $deletedCount
  ];

  if (!empty($errors)) {
    $result['errors'] = $errors;
  }

  return civicrm_api3_create_success($result);
}

/**
 * FileAnalyzer.ExportReport API specification
 */
function _civicrm_api3_file_analyzer_exportReport_spec(&$spec) {
  $spec['directory']['description'] = 'Directory to export report for';
  $spec['directory']['type'] = 'String';
  $spec['directory']['api.default'] = 'custom';
  $spec['format']['description'] = 'Export format (csv|json)';
  $spec['format']['type'] = 'String';
  $spec['format']['api.default'] = 'csv';
}


/**
 * Export scan results as CSV
 */
function civicrm_api3_file_analyzer_export_report($params) {
  $directory = CRM_Utils_Array::value('directory', $params, 'custom');
  $format = CRM_Utils_Array::value('format', $params, 'csv');

  $scanResults = _file_analyzer_get_scan_results($directory);

  if (empty($scanResults)) {
    throw new API_Exception('No scan data available for directory: ' . $directory);
  }

  $exportData = _file_analyzer_prepare_export_data($scanResults, $directory);

  switch ($format) {
    case 'csv':
      $filename = _file_analyzer_export_csv($exportData, $directory);
      break;
    case 'json':
      $filename = _file_analyzer_export_json($exportData, $directory);
      break;
    default:
      throw new API_Exception('Unsupported export format: ' . $format);
  }

  return civicrm_api3_create_success([
    'export_file' => $filename,
    'download_url' => _file_analyzer_get_download_url($filename)
  ]);
}

/**
 * FileAnalyzer.CleanupHistory API specification
 */
function _civicrm_api3_file_analyzer_cleanupHistory_spec(&$spec) {
  $spec['retention_days']['description'] = 'Number of days to retain history';
  $spec['retention_days']['type'] = 'Integer';
  $spec['retention_days']['api.default'] = 30;
  $spec['directory']['description'] = 'Specific directory to clean (optional)';
  $spec['directory']['type'] = 'String';
}


/**
 * Clean up old scan history files
 */
function civicrm_api3_file_analyzer_cleanup_history($params) {
  $retentionDays = CRM_Utils_Array::value('retention_days', $params, 30);
  $directory = CRM_Utils_Array::value('directory', $params);

  $backupPath = _file_analyzer_get_backup_path();
  $cutoffTime = strtotime("-{$retentionDays} days");

  $pattern = $directory ? "{$directory}_scan_results_*.json" : "*_scan_results_*.json";
  $files = glob($backupPath . '/' . $pattern);

  $deletedCount = 0;
  foreach ($files as $file) {
    if (filemtime($file) < $cutoffTime) {
      if (unlink($file)) {
        $deletedCount++;
      }
    }
  }

  return civicrm_api3_create_success([
    'message' => "Cleaned up {$deletedCount} old scan history files",
    'deleted_count' => $deletedCount
  ]);
}

/**
 * FileAnalyzer.GetFileInfo API specification
 */
function _civicrm_api3_file_analyzer_getFileInfo_spec(&$spec) {
  $spec['file_path']['description'] = 'Full path to the file';
  $spec['file_path']['type'] = 'String';
  $spec['file_path']['api.required'] = 1;
}

/**
 * Get file preview information
 */
function civicrm_api3_file_analyzer_get_file_info($params) {
  $filePath = $params['file_path'];

  if (!file_exists($filePath)) {
    throw new API_Exception('File not found: ' . $filePath);
  }

  $fileInfo = [
    'name' => basename($filePath),
    'size' => filesize($filePath),
    'size_formatted' => _file_analyzer_format_file_size(filesize($filePath)),
    'modified' => filemtime($filePath),
    'modified_formatted' => date('Y-m-d H:i:s', filemtime($filePath)),
    'extension' => strtolower(pathinfo($filePath, PATHINFO_EXTENSION)),
    'mime_type' => mime_content_type($filePath),
    'is_image' => _file_analyzer_is_image($filePath)
  ];

  if ($fileInfo['is_image']) {
    $imageInfo = getimagesize($filePath);
    if ($imageInfo) {
      $fileInfo['dimensions'] = [
        'width' => $imageInfo[0],
        'height' => $imageInfo[1]
      ];
    }
  }

  return civicrm_api3_create_success($fileInfo);
}

// Helper Functions

/**
 * Get abandoned files for a directory
 */
function _file_analyzer_get_abandoned_files($directory) {
  $backupPath = _file_analyzer_get_backup_path();
  $abandonedFile = $backupPath . "/{$directory}_abandoned_files.json";

  if (file_exists($abandonedFile)) {
    $contents = file_get_contents($abandonedFile);
    return json_decode($contents, TRUE) ?: [];
  }

  return [];
}

/**
 * Get scan results for a directory
 */
function _file_analyzer_get_scan_results($directory) {
  $backupPath = _file_analyzer_get_backup_path();
  $resultsFile = $backupPath . "/{$directory}_scan_results.json";

  if (file_exists($resultsFile)) {
    $contents = file_get_contents($resultsFile);
    return json_decode($contents, TRUE);
  }

  return NULL;
}

/**
 * Backup a file before deletion
 */
function _file_analyzer_backup_file($filePath, $backupDir = NULL) {
  if (!$backupDir) {
    $backupDir = _file_analyzer_get_backup_directory('deleted_files');
  }

  if (!is_dir($backupDir)) {
    if (!mkdir($backupDir, 0755, TRUE)) {
      throw new Exception('Cannot create backup directory: ' . $backupDir);
    }
  }

  $filename = basename($filePath);
  $timestamp = date('Y-m-d_H-i-s');
  $backupFilename = "{$timestamp}_{$filename}";
  $backupPath = $backupDir . '/' . $backupFilename;

  if (copy($filePath, $backupPath)) {
    return $backupPath;
  }

  throw new Exception('Failed to backup file: ' . $filePath);
}

/**
 * Get backup directory path
 */
function _file_analyzer_get_backup_directory($subdirectory = '') {
  $config = CRM_Core_Config::singleton();
  $basePath = $config->customFileUploadDir . '/file_analyzer_backups';

  if ($subdirectory) {
    $basePath .= '/' . $subdirectory;
  }

  return $basePath;
}

/**
 * Get backup path (alias for backward compatibility)
 */
function _file_analyzer_get_backup_path() {
  return _file_analyzer_get_backup_directory();
}

/**
 * Check if file is an image
 */
function _file_analyzer_is_image($filePath) {
  $imageExtensions = ['jpg', 'jpeg', 'png', 'gif', 'svg', 'webp', 'bmp', 'tiff'];
  $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
  return in_array($extension, $imageExtensions);
}

/**
 * Format file size in human readable format
 */
function _file_analyzer_format_file_size($bytes) {
  if ($bytes >= 1073741824) {
    $bytes = number_format($bytes / 1073741824, 2) . ' GB';
  }
  elseif ($bytes >= 1048576) {
    $bytes = number_format($bytes / 1048576, 2) . ' MB';
  }
  elseif ($bytes >= 1024) {
    $bytes = number_format($bytes / 1024, 2) . ' KB';
  }
  elseif ($bytes > 1) {
    $bytes = $bytes . ' bytes';
  }
  elseif ($bytes == 1) {
    $bytes = $bytes . ' byte';
  }
  else {
    $bytes = '0 bytes';
  }
  return $bytes;
}

/**
 * Prepare data for export
 */
function _file_analyzer_prepare_export_data($scanResults, $directory) {
  $exportData = [
    'summary' => $scanResults['summary'],
    'scan_info' => $scanResults['scan_info'],
    'directory' => $directory
  ];

  // Detailed file list
  $fileList = [];

  // Add abandoned files
  if (!empty($scanResults['abandoned_files'])) {
    foreach ($scanResults['abandoned_files'] as $file) {
      $fileList[] = [
        'name' => basename($file['path']),
        'path' => $file['path'],
        'size' => $file['size'],
        'size_formatted' => _file_analyzer_format_file_size($file['size']),
        'modified' => date('Y-m-d H:i:s', $file['modified']),
        'extension' => $file['extension'],
        'status' => 'abandoned'
      ];
    }
  }

  $exportData['files'] = $fileList;
  $exportData['file_types'] = $scanResults['file_types'];
  $exportData['monthly_stats'] = $scanResults['monthly_stats'];

  return $exportData;
}

/**
 * Export data as CSV
 */
function _file_analyzer_export_csv($exportData, $directory) {
  $timestamp = date('Y-m-d_H-i-s');
  $filename = "file_analysis_{$directory}_{$timestamp}.csv";
  $filepath = _file_analyzer_get_backup_directory('exports') . '/' . $filename;

  // Ensure export directory exists
  $exportDir = dirname($filepath);
  if (!is_dir($exportDir)) {
    mkdir($exportDir, 0755, TRUE);
  }

  $handle = fopen($filepath, 'w');

  // Write summary
  fputcsv($handle, ['File Analysis Report - ' . ucfirst($directory) . ' Directory']);
  fputcsv($handle, ['Generated', date('Y-m-d H:i:s')]);
  fputcsv($handle, []);

  // Write summary statistics
  fputcsv($handle, ['Summary Statistics']);
  foreach ($exportData['summary'] as $key => $value) {
    fputcsv($handle, [ucwords(str_replace('_', ' ', $key)), $value]);
  }
  fputcsv($handle, []);

  // Write file details header
  fputcsv($handle, ['File Details']);
  fputcsv($handle, ['Name', 'Path', 'Size (bytes)', 'Size (formatted)', 'Modified', 'Extension', 'Status']);

  // Write file details
  foreach ($exportData['files'] as $file) {
    fputcsv($handle, [
      $file['name'],
      $file['path'],
      $file['size'],
      $file['size_formatted'],
      $file['modified'],
      $file['extension'],
      $file['status']
    ]);
  }

  fclose($handle);

  return $filename;
}

/**
 * Export data as JSON
 */
function _file_analyzer_export_json($exportData, $directory) {
  $timestamp = date('Y-m-d_H-i-s');
  $filename = "file_analysis_{$directory}_{$timestamp}.json";
  $filepath = _file_analyzer_get_backup_directory('exports') . '/' . $filename;

  // Ensure export directory exists
  $exportDir = dirname($filepath);
  if (!is_dir($exportDir)) {
    mkdir($exportDir, 0755, TRUE);
  }

  file_put_contents($filepath, json_encode($exportData, JSON_PRETTY_PRINT));

  return $filename;
}

/**
 * Get download URL for exported file
 */
function _file_analyzer_get_download_url($filename) {
  $config = CRM_Core_Config::singleton();
  $baseUrl = $config->userFrameworkResourceURL;

  // This would need to be adjusted based on your CiviCRM setup
  return $baseUrl . 'fileanalyzer/download/' . $filename;
}

/**
 * Scheduled job wrapper for multi-directory scanning
 */
function civicrm_api3_file_analyzer_scheduled_scan($params) {
  try {
    $results = CRM_Fileanalyzer_API_FileAnalysis::scheduledScan();

    return civicrm_api3_create_success([
      'message' => 'Scan completed successfully for all directories',
      'results' => $results
    ]);
  }
  catch (Exception $e) {
    throw new API_Exception('Scan failed: ' . $e->getMessage());
  }
}

/**
 * FileAnalyzer.SystemStatus API specification
 */
function _civicrm_api3_file_analyzer_systemStatus_spec(&$spec) {
  // No parameters needed
}

/**
 * Get system status and health check for file analyzer
 */
function civicrm_api3_file_analyzer_system_status($params) {
  $status = [];

  // Check directory permissions
  $config = CRM_Core_Config::singleton();
  $customDir = $config->customFileUploadDir;
  $contributeDir = dirname($customDir) . '/persist/contribute';
  $backupDir = $customDir . '/file_analyzer_backups';

  $directories = [
    'custom' => $customDir,
    'contribute' => $contributeDir,
    'backup' => $backupDir
  ];

  foreach ($directories as $type => $dir) {
    $status['directories'][$type] = [
      'path' => $dir,
      'exists' => is_dir($dir),
      'readable' => is_readable($dir),
      'writable' => is_writable($dir)
    ];
  }

  // Check for recent scans
  foreach (['custom', 'contribute'] as $dirType) {
    $scanFile = $backupDir . "/{$dirType}_scan_results.json";
    $status['scans'][$dirType] = [
      'file_exists' => file_exists($scanFile),
      'last_scan' => file_exists($scanFile) ? date('Y-m-d H:i:s', filemtime($scanFile)) : NULL
    ];
  }

  // Check PHP extensions and limits
  $status['php'] = [
    'json_extension' => extension_loaded('json'),
    'fileinfo_extension' => extension_loaded('fileinfo'),
    'memory_limit' => ini_get('memory_limit'),
    'max_execution_time' => ini_get('max_execution_time'),
    'upload_max_filesize' => ini_get('upload_max_filesize')
  ];

  return civicrm_api3_create_success($status);
}

/**
 * FileAnalyzer.ValidateFiles API specification
 */
function _civicrm_api3_file_analyzer_validateFiles_spec(&$spec) {
  $spec['directory']['description'] = 'Directory to validate';
  $spec['directory']['type'] = 'String';
  $spec['directory']['api.default'] = 'custom';
  $spec['check_images']['description'] = 'Perform image-specific validation';
  $spec['check_images']['type'] = 'Boolean';
  $spec['check_images']['api.default'] = TRUE;
}

/**
 * Validate file integrity and check for corruption
 */
function civicrm_api3_file_analyzer_validate_files($params) {
  $directory = CRM_Utils_Array::value('directory', $params, 'custom');
  $checkImages = CRM_Utils_Array::value('check_images', $params, TRUE);

  $scanResults = _file_analyzer_get_scan_results($directory);
  if (empty($scanResults)) {
    throw new API_Exception('No scan data available. Please run a scan first.');
  }

  $validationResults = [
    'total_checked' => 0,
    'corrupted_files' => [],
    'missing_files' => [],
    'valid_files' => 0
  ];

  // Check all files from scan results
  $allFiles = array_merge(
    $scanResults['abandoned_files'] ?? [],
    [] // Add referenced files if needed
  );

  foreach ($allFiles as $fileInfo) {
    $filePath = $fileInfo['path'];
    $validationResults['total_checked']++;

    if (!file_exists($filePath)) {
      $validationResults['missing_files'][] = [
        'path' => $filePath,
        'name' => basename($filePath)
      ];
      continue;
    }

    $isValid = TRUE;
    $errors = [];

    // Basic file integrity check
    if (filesize($filePath) != $fileInfo['size']) {
      $isValid = FALSE;
      $errors[] = 'Size mismatch';
    }

    // Image-specific validation
    if ($checkImages && _file_analyzer_is_image($filePath)) {
      $imageInfo = @getimagesize($filePath);
      if ($imageInfo === FALSE) {
        $isValid = FALSE;
        $errors[] = 'Corrupted image file';
      }
    }

    if (!$isValid) {
      $validationResults['corrupted_files'][] = [
        'path' => $filePath,
        'name' => basename($filePath),
        'errors' => $errors
      ];
    }
    else {
      $validationResults['valid_files']++;
    }
  }

  return civicrm_api3_create_success($validationResults);
}

/**
 * FileAnalyzer.DiskUsage API specification
 */
function _civicrm_api3_file_analyzer_diskUsage_spec(&$spec) {
  // No parameters needed
}

/**
 * Get disk usage statistics for directories
 */
function civicrm_api3_file_analyzer_disk_usage($params) {
  $directories = ['custom', 'contribute'];
  $usage = [];

  $config = CRM_Core_Config::singleton();
  $basePath = dirname($config->customFileUploadDir);

  $paths = [
    'custom' => $config->customFileUploadDir,
    'contribute' => $basePath . '/persist/contribute'
  ];

  foreach ($paths as $type => $path) {
    if (!is_dir($path)) {
      $usage[$type] = [
        'exists' => FALSE,
        'size' => 0,
        'size_formatted' => '0 bytes',
        'file_count' => 0
      ];
      continue;
    }

    $size = 0;
    $fileCount = 0;

    $iterator = new RecursiveIteratorIterator(
      new RecursiveDirectoryIterator($path, RecursiveDirectoryIterator::SKIP_DOTS)
    );

    foreach ($iterator as $file) {
      if ($file->isFile()) {
        $size += $file->getSize();
        $fileCount++;
      }
    }

    $usage[$type] = [
      'exists' => TRUE,
      'path' => $path,
      'size' => $size,
      'size_formatted' => _file_analyzer_format_file_size($size),
      'file_count' => $fileCount
    ];
  }

  // Total usage
  $totalSize = array_sum(array_column($usage, 'size'));
  $totalFiles = array_sum(array_column($usage, 'file_count'));

  $usage['total'] = [
    'size' => $totalSize,
    'size_formatted' => _file_analyzer_format_file_size($totalSize),
    'file_count' => $totalFiles
  ];

  return civicrm_api3_create_success($usage);
}
