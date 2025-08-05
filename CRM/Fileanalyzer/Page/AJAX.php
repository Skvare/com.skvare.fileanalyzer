<?php

/**
 * AJAX Handler for file operations
 */
class CRM_FileAnalyzer_Page_AJAX extends CRM_Core_Page {

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
   * Delete a specific file
   */
  private function deleteFile() {
    $filename = CRM_Utils_Request::retrieve('filename', 'String');

    if (!$filename) {
      CRM_Utils_JSON::output(['error' => 'No filename provided']);
      return;
    }

    $customPath = CRM_Core_Config::singleton()->customFileUploadDir;
    $filePath = $customPath . '/' . $filename;

    // Security check - ensure file is within custom directory
    $realCustomPath = realpath($customPath);
    $realFilePath = realpath($filePath);

    if (!$realFilePath || strpos($realFilePath, $realCustomPath) !== 0) {
      CRM_Utils_JSON::output(['error' => 'Invalid file path']);
      return;
    }

    // Double-check that file is abandoned
    $fileAnalyzer = new CRM_FileAnalyzer_Page_Dashboard();
    if ($fileAnalyzer->isFileInUse(basename($filename))) {
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
   * Get detailed file information
   */
  private function getFileInfo() {
    $filename = CRM_Utils_Request::retrieve('filename', 'String');
    CRM_Core_Error::debug_log_message("Getting info for file: $filename");
    if (!$filename) {
      CRM_Utils_JSON::output(['error' => 'No filename provided']);
      return;
    }

    $customPath = CRM_Core_Config::singleton()->customFileUploadDir;
    $filePath = $customPath . '/' . $filename;

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
      ];

      CRM_Utils_JSON::output($info);
    }
    else {
      CRM_Utils_JSON::output(['error' => 'File not found']);
    }
  }
}
