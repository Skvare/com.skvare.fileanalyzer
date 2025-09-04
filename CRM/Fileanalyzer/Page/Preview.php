<?php
use CRM_Fileanalyzer_ExtensionUtil as E;

class CRM_Fileanalyzer_Page_Preview extends CRM_Core_Page {

  public function run() {
    $filePath = CRM_Utils_Request::retrieve('file', 'String', $this, TRUE);

    // Security check - ensure file is within allowed directories
    if (!$this->isFileAllowed($filePath)) {
      CRM_Core_Error::statusBounce('Access denied to this file.');
    }

    if (!file_exists($filePath)) {
      CRM_Core_Error::statusBounce('File not found.');
    }

    // Get file information
    $fileInfo = [
      'name' => basename($filePath),
      'size' => filesize($filePath),
      'size_formatted' => $this->formatFileSize(filesize($filePath)),
      'modified' => date('Y-m-d H:i:s', filemtime($filePath)),
      'extension' => strtolower(pathinfo($filePath, PATHINFO_EXTENSION)),
      'mime_type' => mime_content_type($filePath),
      'is_image' => $this->isImage($filePath)
    ];

    if ($fileInfo['is_image']) {
      $imageInfo = getimagesize($filePath);
      if ($imageInfo) {
        $fileInfo['dimensions'] = $imageInfo[0] . ' x ' . $imageInfo[1];
      }
    }

    // Check if file is referenced
    $fileInfo['is_referenced'] = $this->isFileReferenced($filePath);

    $this->assign('fileInfo', $fileInfo);
    $this->assign('filePath', $filePath);

    // For images, create preview URL
    if ($fileInfo['is_image']) {
      $previewUrl = $this->getPreviewUrl($filePath);
      $this->assign('previewUrl', $previewUrl);
    }

    parent::run();
  }

  /**
   * Check if file is in allowed directories
   */
  private function isFileAllowed($filePath) {
    $config = CRM_Core_Config::singleton();
    $allowedPaths = [
      $config->customFileUploadDir,
      dirname($config->customFileUploadDir) . '/persist/contribute'
    ];

    $realPath = realpath($filePath);
    if (!$realPath) {
      return FALSE;
    }

    foreach ($allowedPaths as $allowedPath) {
      if (strpos($realPath, realpath($allowedPath)) === 0) {
        return TRUE;
      }
    }

    return FALSE;
  }

  /**
   * Check if file is an image
   */
  private function isImage($filePath) {
    $imageExtensions = ['jpg', 'jpeg', 'png', 'gif', 'svg', 'webp', 'bmp'];
    $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
    return in_array($extension, $imageExtensions);
  }

  /**
   * Check if file is referenced in database
   */
  private function isFileReferenced($filePath) {
    $fileName = basename($filePath);

    // Check civicrm_file table
    $sql = "SELECT id FROM civicrm_file WHERE uri = %1 LIMIT 1";
    $params = [1 => [$fileName, 'String']];
    $result = CRM_Core_DAO::singleValueQuery($sql, $params);

    if ($result) {
      return TRUE;
    }

    // Check message templates for contribute directory files
    if (strpos($filePath, '/persist/contribute') !== FALSE) {
      $sql = "SELECT id FROM civicrm_msg_template
              WHERE msg_html LIKE %1 OR msg_text LIKE %1 LIMIT 1";
      $params = [1 => ['%' . $fileName . '%', 'String']];
      $result = CRM_Core_DAO::singleValueQuery($sql, $params);

      return !empty($result);
    }

    return FALSE;
  }

  /**
   * Get preview URL for file
   */
  private function getPreviewUrl($filePath) {
    $config = CRM_Core_Config::singleton();

    // For custom directory files
    if (strpos($filePath, $config->customFileUploadDir) === 0) {
      $relativePath = substr($filePath, strlen($config->customFileUploadDir) + 1);
      return $config->imageUploadURL . $relativePath;
    }

    // For contribute directory files
    $contributeDir = dirname($config->customFileUploadDir) . '/persist/contribute';
    if (strpos($filePath, $contributeDir) === 0) {
      $relativePath = substr($filePath, strlen($contributeDir) + 1);
      $baseUrl = str_replace('/custom/', '/persist/contribute/', $config->imageUploadURL);
      return $baseUrl . $relativePath;
    }

    return NULL;
  }

  /**
   * Format file size
   */
  private function formatFileSize($bytes) {
    if ($bytes >= 1073741824) {
      $bytes = number_format($bytes / 1073741824, 2) . ' GB';
    }
    elseif ($bytes >= 1048576) {
      $bytes = number_format($bytes / 1048576, 2) . ' MB';
    }
    elseif ($bytes >= 1024) {
      $bytes = number_format($bytes / 1024, 2) . ' KB';
    }
    else {
      $bytes = $bytes . ' bytes';
    }
    return $bytes;
  }
}
