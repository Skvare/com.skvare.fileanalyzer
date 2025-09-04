<?php

/**
 * Enhanced AJAX Handler for file operations with directory type support
 */
class CRM_Fileanalyzer_Page_AJAX extends CRM_Core_Page {

  public function run() {
    $action = CRM_Utils_Request::retrieve('operation', 'String');

    switch ($action) {
      case 'deleteFile':
        $this->deleteFile();
        break;
      case 'getFileInfo':
        $this->getFileInfo();
        break;
      default:
        CRM_Utils_JSON::output(['error' => 'Invalid action']);
    }
  }

  /**
   * Delete a specific file with directory type support
   */
  private function deleteFile() {
    $filename = CRM_Utils_Request::retrieve('filename', 'String');
    $directoryType = CRM_Utils_Request::retrieve('directory_type', 'String') ?: CRM_Fileanalyzer_API_FileAnalysis::DIRECTORY_CUSTOM;

    if (!$filename) {
      CRM_Utils_JSON::output(['error' => 'No filename provided']);
      return;
    }

    // Get the appropriate directory path based on type
    try {
      $basePath = $this->getDirectoryPath($directoryType);
    }
    catch (Exception $e) {
      CRM_Utils_JSON::output(['error' => 'Invalid directory type']);
      return;
    }

    $filePath = $basePath . '/' . $filename;

    // Security check - ensure file is within the specified directory
    $realBasePath = realpath($basePath);
    $realFilePath = realpath($filePath);

    if (!$realFilePath || strpos($realFilePath, $realBasePath) !== 0) {
      CRM_Utils_JSON::output(['error' => 'Invalid file path']);
      return;
    }

    // Double-check that file is abandoned using the appropriate directory type
    if (CRM_Fileanalyzer_API_FileAnalysis::isFileInUse(basename($filename), $directoryType)) {
      CRM_Utils_JSON::output(['error' => 'File is in use and cannot be deleted']);
      return;
    }

    if (file_exists($filePath) && unlink($filePath)) {
      CRM_Utils_JSON::output(['success' => 'File deleted successfully']);
    }
    else {
      CRM_Utils_JSON::output(['error' => 'Failed to delete file']);
    }
  }

  /**
   * Get detailed file information with directory type support
   */
  private function getFileInfo() {
    $filename = CRM_Utils_Request::retrieve('filename', 'String');
    $directoryType = CRM_Utils_Request::retrieve('directory_type', 'String') ?: CRM_Fileanalyzer_API_FileAnalysis::DIRECTORY_CUSTOM;

    CRM_Core_Error::debug_log_message("Getting info for file: $filename in directory: $directoryType");

    if (!$filename) {
      CRM_Utils_JSON::output(['error' => 'No filename provided']);
      return;
    }

    // Get the appropriate directory path based on type
    try {
      $basePath = $this->getDirectoryPath($directoryType);
    }
    catch (Exception $e) {
      CRM_Utils_JSON::output(['error' => 'Invalid directory type']);
      return;
    }

    $filePath = $basePath . '/' . $filename;

    if (file_exists($filePath)) {
      $stat = stat($filePath);
      $info = [
        'filename' => basename($filename),
        'size' => $stat['size'],
        'created' => date('Y-m-d H:i:s', $stat['ctime']),
        'modified' => date('Y-m-d H:i:s', $stat['mtime']),
        'extension' => strtolower(pathinfo($filename, PATHINFO_EXTENSION)),
        'readable' => is_readable($filePath),
        'writable' => is_writable($filePath),
        'directory_type' => $directoryType,
        'full_path' => $filePath,
      ];

      // Add directory-specific information
      if ($directoryType === CRM_Fileanalyzer_API_FileAnalysis::DIRECTORY_CONTRIBUTE) {
        $info['usage_note'] = 'This image may be used in contribute page header or footer content.';
      }
      else {
        $info['usage_note'] = 'This file may be used as custom file upload or attachment.';
      }

      CRM_Utils_JSON::output($info);
    }
    else {
      CRM_Utils_JSON::output(['error' => 'File not found']);
    }
  }

  /**
   * Get the appropriate directory path based on directory type
   *
   * @param string $directoryType Directory type constant
   * @return string Full path to the directory
   * @throws Exception If directory type is invalid
   */
  private function getDirectoryPath($directoryType) {
    $config = CRM_Core_Config::singleton();

    switch ($directoryType) {
      case CRM_Fileanalyzer_API_FileAnalysis::DIRECTORY_CUSTOM:
        return $config->customFileUploadDir;

      case CRM_Fileanalyzer_API_FileAnalysis::DIRECTORY_CONTRIBUTE:
        $baseDir = dirname($config->customFileUploadDir);
        return $baseDir . '/persist/contribute/images';

      default:
        throw new Exception("Unknown directory type: {$directoryType}");
    }
  }
}
