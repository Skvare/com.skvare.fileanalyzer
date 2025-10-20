<?php
use CRM_Fileanalyzer_ExtensionUtil as E;

/**
 * Enhanced CiviCRM File Analyzer Extension - File Preview Page
 *
 * This class handles file previews for both custom and contribute directories
 * with enhanced security, directory type detection, and comprehensive file support.
 *
 * Enhanced Features:
 * - Multi-directory support (custom uploads and contribute images)
 * - Automatic directory type detection
 * - Enhanced security validation
 * - Multiple preview modes (inline, download, thumbnail)
 * - File type detection and appropriate handling
 * - Database reference checking
 * - MIME type validation
 * - Image metadata extraction
 * - PDF handling
 * - Text file preview with encoding detection
 */
class CRM_Fileanalyzer_Page_Preview extends CRM_Core_Page {

  // Directory type constants
  const DIRECTORY_CUSTOM = 'custom';
  const DIRECTORY_CONTRIBUTE = 'contribute';

  // Preview mode constants
  const MODE_INLINE = 'inline';
  const MODE_DOWNLOAD = 'download';
  const MODE_THUMBNAIL = 'thumbnail';
  const MODE_INFO = 'info';

  /**
   * Main run method - Entry point for file preview page
   *
   * Enhanced to support multiple directories and preview modes
   */
  public function run() {
    // Get request parameters

    $filePath = CRM_Utils_Request::retrieve('file', 'String', $this, TRUE);
    $directoryType = CRM_Utils_Request::retrieve('directory_type', 'String', NULL, FALSE);
    $previewMode = CRM_Utils_Request::retrieve('mode', 'String', $this, FALSE, self::MODE_INLINE);

    // Validate and sanitize file path
    $filePath = $this->sanitizeFilePath($filePath);

    // Determine directory type if not provided
    if (!$directoryType) {
      $directoryType = $this->detectDirectoryType($filePath);
    }

    // Comprehensive security validation
    if (!$this->isFileAccessAllowed($filePath, $directoryType)) {
      CRM_Core_Error::statusBounce('Access denied to this file.');
    }

    if (!file_exists($filePath)) {
      CRM_Core_Error::statusBounce('File not found.');
    }

    // Get comprehensive file information
    $fileInfo = $this->getComprehensiveFileInfo($filePath, $directoryType);
    if ($fileInfo['is_pdf']) {
      //$previewMode = 'inline'; // Force inline for PDFs
    }
    // Handle different preview modes
    switch ($previewMode) {
      case self::MODE_DOWNLOAD:
        $this->handleDownload($filePath, $fileInfo);
        break;

      case self::MODE_THUMBNAIL:
        $this->handleThumbnail($filePath, $fileInfo);
        break;

      case self::MODE_INFO:
        $this->handleInfoRequest($fileInfo);
        break;

      case self::MODE_INLINE:
      default:
        $this->handleInlinePreview($filePath, $fileInfo, $directoryType);
        break;
    }
  }

  /**
   * Sanitize and validate file path
   *
   * @param string $filePath Raw file path from request
   * @return string Sanitized file path
   * @throws Exception If path is invalid
   */
  private function sanitizeFilePath($filePath) {
    // Decode URL encoding
    $filePath = urldecode($filePath);

    // Remove any directory traversal attempts
    $filePath = str_replace(['../', '..\\', '../', '..\\'], '', $filePath);

    // Normalize path separators
    $filePath = str_replace('\\', '/', $filePath);

    // Remove any null bytes
    $filePath = str_replace("\0", '', $filePath);

    return $filePath;
  }

  /**
   * Detect directory type based on file path
   *
   * @param string $filePath File path to analyze
   * @return string Directory type constant
   */
  private function detectDirectoryType($filePath) {
    $config = CRM_Core_Config::singleton();

    // Check if file is in custom directory
    if (strpos($filePath, $config->customFileUploadDir) === 0) {
      return self::DIRECTORY_CUSTOM;
    }

    // Check if file is in contribute directory
    $baseDir = dirname($config->customFileUploadDir);
    $contributePath = $baseDir . '/persist/contribute';
    if (strpos($filePath, $contributePath) === 0) {
      return self::DIRECTORY_CONTRIBUTE;
    }

    // Default to custom if uncertain
    return self::DIRECTORY_CUSTOM;
  }

  /**
   * Enhanced security check for file access
   *
   * @param string $filePath Full file path
   * @param string $directoryType Directory type
   * @return bool TRUE if access is allowed
   */
  private function isFileAccessAllowed($filePath, $directoryType) {
    $config = CRM_Core_Config::singleton();

    // Define allowed base directories
    $allowedPaths = [];

    if ($directoryType === self::DIRECTORY_CUSTOM) {
      $allowedPaths[] = $config->customFileUploadDir;
    }
    elseif ($directoryType === self::DIRECTORY_CONTRIBUTE) {
      $baseDir = dirname($config->customFileUploadDir);
      $allowedPaths[] = $baseDir . '/persist/contribute';
    }

    // Get real path to prevent symlink attacks
    $realPath = realpath($filePath);
    if (!$realPath) {
      return FALSE;
    }

    // Check if file is within allowed directories
    foreach ($allowedPaths as $allowedPath) {
      $realAllowedPath = realpath($allowedPath);
      if ($realAllowedPath && strpos($realPath, $realAllowedPath) === 0) {
        // Additional security checks
        return $this->performAdditionalSecurityChecks($realPath, $directoryType);
      }
    }

    return FALSE;
  }

  /**
   * Perform additional security checks
   *
   * @param string $realPath Real file path
   * @param string $directoryType Directory type
   * @return bool TRUE if additional checks pass
   */
  private function performAdditionalSecurityChecks($realPath, $directoryType) {
    // Check file extension against blacklist
    $extension = strtolower(pathinfo($realPath, PATHINFO_EXTENSION));
    $blacklistedExtensions = [
      'php', 'php3', 'php4', 'php5', 'phtml', 'phps',
      'asp', 'aspx', 'jsp', 'jspx', 'cfm', 'cfc',
      'pl', 'py', 'rb', 'sh', 'bat', 'cmd',
      'exe', 'com', 'scr', 'vbs', 'js'
    ];

    if (in_array($extension, $blacklistedExtensions)) {
      return FALSE;
    }

    // Check file size (prevent serving extremely large files)
    $maxFileSize = 100 * 1024 * 1024; // 100MB
    if (filesize($realPath) > $maxFileSize) {
      return FALSE;
    }

    // Check if file is readable
    if (!is_readable($realPath)) {
      return FALSE;
    }

    return TRUE;
  }

  /**
   * Get comprehensive file information with directory-specific enhancements
   *
   * @param string $filePath Full file path
   * @param string $directoryType Directory type
   * @return array Comprehensive file information
   */
  private function getComprehensiveFileInfo($filePath, $directoryType) {
    $stat = stat($filePath);
    $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
    $mimeType = $this->getMimeType($filePath);

    $fileInfo = [
      'name' => basename($filePath),
      'path' => $filePath,
      'directory_type' => $directoryType,
      'size' => $stat['size'],
      'size_formatted' => $this->formatFileSize($stat['size']),
      'created' => date('Y-m-d H:i:s', $stat['ctime']),
      'modified' => date('Y-m-d H:i:s', $stat['mtime']),
      'accessed' => date('Y-m-d H:i:s', $stat['atime']),
      'extension' => $extension,
      'mime_type' => $mimeType,
      'is_image' => $this->isImage($filePath, $mimeType),
      'is_pdf' => $this->isPDF($extension, $mimeType),
      'is_text' => $this->isText($mimeType),
      'is_video' => $this->isVideo($mimeType),
      'is_audio' => $this->isAudio($mimeType),
      'readable' => is_readable($filePath),
      'writable' => is_writable($filePath),
      'preview_supported' => $this->isPreviewSupported($extension, $mimeType),
      'download_url' => $this->generateDownloadUrl($filePath, $directoryType),
    ];

    // Add image-specific information
    if ($fileInfo['is_image']) {
      $imageInfo = $this->getImageInfo($filePath);
      $fileInfo = array_merge($fileInfo, $imageInfo);
    }

    // Add PDF-specific information
    if ($fileInfo['is_pdf']) {
      $pdfInfo = $this->getPDFInfo($filePath);
      $fileInfo = array_merge($fileInfo, $pdfInfo);
    }

    // Add text file information
    if ($fileInfo['is_text']) {
      $textInfo = $this->getTextInfo($filePath);
      $fileInfo = array_merge($fileInfo, $textInfo);
    }

    // Check database references
    $fileInfo['is_referenced'] = $this->isFileReferenced($filePath, $directoryType);
    $fileInfo['reference_info'] = $this->getFileReferences($filePath, $directoryType);

    // Add directory-specific information
    $fileInfo['directory_info'] = $this->getDirectorySpecificInfo($directoryType);

    // Add preview URL
    $fileInfo['preview_url'] = $this->generatePreviewUrl($filePath, $mimeType, $directoryType);

    return $fileInfo;
  }

  /**
   * Handle inline preview display
   *
   * @param string $filePath Full file path
   * @param array $fileInfo File information array
   * @param string $directoryType Directory type
   */
  private function handleInlinePreview($filePath, $fileInfo, $directoryType) {
    // Set template variables
    $this->assign('fileInfo', $fileInfo);
    $this->assign('filePath', $filePath);
    $this->assign('directoryType', $directoryType);

    // Generate preview content based on file type
    if ($fileInfo['is_image']) {
      $this->assign('previewContent', $this->generateImagePreview($fileInfo));
      $this->assign('previewType', 'image');
    }
    elseif ($fileInfo['is_pdf']) {
      $this->assign('previewContent', $this->generatePDFPreview($fileInfo));
      $this->assign('previewType', 'pdf');
    }
    elseif ($fileInfo['is_text']) {
      $this->assign('previewContent', $this->generateTextPreview($fileInfo));
      $this->assign('previewType', 'text');
    }
    elseif ($fileInfo['is_video']) {
      $this->assign('previewContent', $this->generateVideoPreview($fileInfo));
      $this->assign('previewType', 'video');
    }
    elseif ($fileInfo['is_audio']) {
      $this->assign('previewContent', $this->generateAudioPreview($fileInfo));
      $this->assign('previewType', 'audio');
    }
    else {
      $this->assign('previewContent', $this->generateGenericPreview($fileInfo));
      $this->assign('previewType', 'generic');
    }

    // Add breadcrumb navigation
    $this->assign('breadcrumb', $this->generateBreadcrumb($directoryType));

    parent::run();
  }

  /**
   * Handle file download
   *
   * @param string $filePath Full file path
   * @param array $fileInfo File information array
   */
  private function handleDownload($filePath, $fileInfo) {
    // Set appropriate headers for download
    header('Content-Type: ' . $fileInfo['mime_type']);
    header('Content-Disposition: attachment; filename="' . $fileInfo['name'] . '"');
    header('Content-Length: ' . $fileInfo['size']);
    header('Cache-Control: private, must-revalidate');
    header('Pragma: private');
    header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');

    // Output file content
    readfile($filePath);
    exit;
  }

  /**
   * Handle thumbnail generation
   *
   * @param string $filePath Full file path
   * @param array $fileInfo File information array
   */
  private function handleThumbnail($filePath, $fileInfo) {
    if (!$fileInfo['is_image']) {
      // Return default icon for non-images
      $this->outputDefaultThumbnail($fileInfo['extension']);
      return;
    }

    $thumbnailSize = CRM_Utils_Request::retrieve('size', 'Integer', $this, FALSE, 150);
    $thumbnailSize = min(max($thumbnailSize, 50), 500); // Limit size between 50-500px

    $thumbnail = $this->generateImageThumbnail($filePath, $thumbnailSize);

    if ($thumbnail) {
      header('Content-Type: image/jpeg');
      header('Cache-Control: public, max-age=3600');
      imagejpeg($thumbnail);
      imagedestroy($thumbnail);
    }
    else {
      $this->outputDefaultThumbnail($fileInfo['extension']);
    }

    exit;
  }

  /**
   * Handle info-only request (JSON response)
   *
   * @param array $fileInfo File information array
   */
  private function handleInfoRequest($fileInfo) {
    header('Content-Type: application/json');
    echo json_encode($fileInfo, JSON_PRETTY_PRINT);
    exit;
  }

  /**
   * Get MIME type with fallback detection
   *
   * @param string $filePath File path
   * @return string MIME type
   */
  private function getMimeType($filePath) {
    // Try multiple methods for MIME type detection
    if (function_exists('finfo_file')) {
      $finfo = finfo_open(FILEINFO_MIME_TYPE);
      $mimeType = finfo_file($finfo, $filePath);
      finfo_close($finfo);
      if ($mimeType) {
        return $mimeType;
      }
    }

    if (function_exists('mime_content_type')) {
      $mimeType = mime_content_type($filePath);
      if ($mimeType) {
        return $mimeType;
      }
    }

    // Fallback to extension-based detection
    return $this->getMimeTypeByExtension(pathinfo($filePath, PATHINFO_EXTENSION));
  }

  /**
   * Get MIME type by file extension
   *
   * @param string $extension File extension
   * @return string MIME type
   */
  private function getMimeTypeByExtension($extension) {
    $mimeTypes = [
      // Images
      'jpg' => 'image/jpeg',
      'jpeg' => 'image/jpeg',
      'png' => 'image/png',
      'gif' => 'image/gif',
      'svg' => 'image/svg+xml',
      'webp' => 'image/webp',
      'bmp' => 'image/bmp',
      'tiff' => 'image/tiff',
      'ico' => 'image/x-icon',

      // Documents
      'pdf' => 'application/pdf',
      'doc' => 'application/msword',
      'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
      'xls' => 'application/vnd.ms-excel',
      'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
      'ppt' => 'application/vnd.ms-powerpoint',
      'pptx' => 'application/vnd.openxmlformats-officedocument.presentationml.presentation',

      // Text
      'txt' => 'text/plain',
      'csv' => 'text/csv',
      'xml' => 'text/xml',
      'html' => 'text/html',
      'css' => 'text/css',
      'js' => 'text/javascript',
      'json' => 'application/json',
      'log' => 'text/plain',

      // Archives
      'zip' => 'application/zip',
      'rar' => 'application/x-rar-compressed',
      '7z' => 'application/x-7z-compressed',
      'tar' => 'application/x-tar',
      'gz' => 'application/gzip',

      // Video
      'mp4' => 'video/mp4',
      'avi' => 'video/x-msvideo',
      'mov' => 'video/quicktime',
      'wmv' => 'video/x-ms-wmv',
      'flv' => 'video/x-flv',
      'webm' => 'video/webm',

      // Audio
      'mp3' => 'audio/mpeg',
      'wav' => 'audio/wav',
      'flac' => 'audio/flac',
      'aac' => 'audio/aac',
      'ogg' => 'audio/ogg',
    ];

    $extension = strtolower($extension);
    return isset($mimeTypes[$extension]) ? $mimeTypes[$extension] : 'application/octet-stream';
  }

  /**
   * Check if file is an image
   *
   * @param string $filePath File path
   * @param string $mimeType MIME type
   * @return bool
   */
  private function isImage($filePath, $mimeType) {
    return strpos($mimeType, 'image/') === 0 && function_exists('getimagesize') && getimagesize($filePath) !== FALSE;
  }

  /**
   * Check if file is a PDF
   *
   * @param string $extension File extension
   * @param string $mimeType MIME type
   * @return bool
   */
  private function isPDF($extension, $mimeType) {
    return strtolower($extension) === 'pdf' || $mimeType === 'application/pdf';
  }

  /**
   * Check if file is text-based
   *
   * @param string $mimeType MIME type
   * @return bool
   */
  private function isText($mimeType) {
    return strpos($mimeType, 'text/') === 0 ||
      in_array($mimeType, ['application/json', 'application/xml', 'application/javascript']);
  }

  /**
   * Check if file is video
   *
   * @param string $mimeType MIME type
   * @return bool
   */
  private function isVideo($mimeType) {
    return strpos($mimeType, 'video/') === 0;
  }

  /**
   * Check if file is audio
   *
   * @param string $mimeType MIME type
   * @return bool
   */
  private function isAudio($mimeType) {
    return strpos($mimeType, 'audio/') === 0;
  }

  /**
   * Check if preview is supported for file type
   *
   * @param string $extension File extension
   * @param string $mimeType MIME type
   * @return bool
   */
  private function isPreviewSupported($extension, $mimeType) {
    // Supported preview types
    $supportedTypes = [
      'image/', 'text/', 'application/pdf', 'application/json',
      'application/xml', 'video/', 'audio/'
    ];

    foreach ($supportedTypes as $type) {
      if (strpos($mimeType, $type) === 0) {
        return TRUE;
      }
    }

    return FALSE;
  }

  /**
   * Get detailed image information
   *
   * @param string $filePath Image file path
   * @return array Image information
   */
  private function getImageInfo($filePath) {
    $imageInfo = getimagesize($filePath);
    if (!$imageInfo) {
      return [];
    }

    $info = [
      'width' => $imageInfo[0],
      'height' => $imageInfo[1],
      'dimensions' => $imageInfo[0] . ' × ' . $imageInfo[1] . ' pixels',
      'image_type' => $imageInfo[2],
      'image_type_name' => image_type_to_extension($imageInfo[2], FALSE),
      'aspect_ratio' => round($imageInfo[0] / $imageInfo[1], 2),
    ];

    // Add color information if available
    if (isset($imageInfo['channels'])) {
      $info['channels'] = $imageInfo['channels'];
      $info['color_mode'] = $imageInfo['channels'] === 3 ? 'RGB' : ($imageInfo['channels'] === 4 ? 'RGBA' : 'Grayscale');
    }

    // Add EXIF data for JPEG images
    if ($info['image_type'] === IMAGETYPE_JPEG && function_exists('exif_read_data')) {
      try {
        $exif = exif_read_data($filePath);
        if ($exif) {
          $info['exif'] = $this->processExifData($exif);
        }
      }
      catch (Exception $e) {
        // EXIF reading failed, continue without it
      }
    }

    return $info;
  }

  /**
   * Process EXIF data and extract useful information
   *
   * @param array $exif Raw EXIF data
   * @return array Processed EXIF data
   */
  private function processExifData($exif) {
    $processed = [];

    // Camera information
    if (isset($exif['Make'])) {
      $processed['camera_make'] = $exif['Make'];
    }
    if (isset($exif['Model'])) {
      $processed['camera_model'] = $exif['Model'];
    }

    // Shot information
    if (isset($exif['DateTime'])) {
      $processed['date_taken'] = $exif['DateTime'];
    }
    if (isset($exif['ExposureTime'])) {
      $processed['exposure_time'] = $exif['ExposureTime'];
    }
    if (isset($exif['FNumber'])) {
      $processed['f_number'] = 'f/' . $exif['FNumber'];
    }
    if (isset($exif['ISOSpeedRatings'])) {
      $processed['iso'] = $exif['ISOSpeedRatings'];
    }
    if (isset($exif['FocalLength'])) {
      $processed['focal_length'] = $exif['FocalLength'] . 'mm';
    }

    // GPS information
    if (isset($exif['GPSLatitude']) && isset($exif['GPSLongitude'])) {
      $processed['gps'] = [
        'latitude' => $exif['GPSLatitude'],
        'longitude' => $exif['GPSLongitude'],
      ];
    }

    return $processed;
  }

  /**
   * Get PDF information
   *
   * @param string $filePath PDF file path
   * @return array PDF information
   */
  private function getPDFInfo($filePath) {
    $info = [];

    // Try to get basic PDF info using shell command if available
    if (function_exists('shell_exec') && !empty(shell_exec('which pdfinfo'))) {
      try {
        $output = shell_exec('pdfinfo ' . escapeshellarg($filePath) . ' 2>/dev/null');
        if ($output) {
          $lines = explode("\n", $output);
          foreach ($lines as $line) {
            if (strpos($line, ':') !== FALSE) {
              [$key, $value] = explode(':', $line, 2);
              $key = trim(strtolower(str_replace(' ', '_', $key)));
              $value = trim($value);
              if ($value) {
                $info[$key] = $value;
              }
            }
          }
        }
      }
      catch (Exception $e) {
        // PDF info extraction failed
      }
    }

    return $info;
  }

  /**
   * Get text file information
   *
   * @param string $filePath Text file path
   * @return array Text file information
   */
  private function getTextInfo($filePath) {
    $info = [];

    try {
      // Get line count
      $lineCount = 0;
      $handle = fopen($filePath, 'r');
      if ($handle) {
        while (($line = fgets($handle)) !== FALSE) {
          $lineCount++;
        }
        fclose($handle);
        $info['line_count'] = $lineCount;
      }

      // Detect encoding
      $content = file_get_contents($filePath, FALSE, NULL, 0, 8192); // Read first 8KB
      if ($content !== FALSE) {
        $info['encoding'] = mb_detect_encoding($content, mb_detect_order(), TRUE) ?: 'Unknown';
        $info['character_count'] = strlen($content);
        $info['word_count'] = str_word_count($content);
      }
    }
    catch (Exception $e) {
      // Text analysis failed
    }

    return $info;
  }

  /**
   * Check if file is referenced in database (enhanced for both directories)
   *
   * @param string $filePath Full file path
   * @param string $directoryType Directory type
   * @return bool
   */
  private function isFileReferenced($filePath, $directoryType) {
    return CRM_Fileanalyzer_API_FileAnalysis::isFileInUse(basename($filePath), $directoryType);
  }

  /**
   * Get detailed file references from database
   *
   * @param string $filePath Full file path
   * @param string $directoryType Directory type
   * @return array Reference information
   */
  private function getFileReferences($filePath, $directoryType) {
    $filename = basename($filePath);
    $references = [];

    if ($directoryType === self::DIRECTORY_CUSTOM) {
      // Check civicrm_file table
      $fileQuery = "
        SELECT f.id, f.uri, f.mime_type, f.description, f.upload_date
        FROM civicrm_file f
        WHERE f.uri = %1 OR f.uri LIKE %2
      ";
      $fileParams = [
        1 => [$filename, 'String'],
        2 => ['%' . $filename, 'String'],
      ];

      $fileResult = CRM_Core_DAO::executeQuery($fileQuery, $fileParams);
      while ($fileResult->fetch()) {
        $references[] = [
          'type' => 'file_record',
          'id' => $fileResult->id,
          'uri' => $fileResult->uri,
          'mime_type' => $fileResult->mime_type,
          'description' => $fileResult->description,
          'upload_date' => $fileResult->upload_date,
        ];
      }

      // Check entity file relationships
      if (!empty($references)) {
        foreach ($references as &$ref) {
          $entityQuery = "
            SELECT ef.item_table, ef.item_id, ef.file_id
            FROM civicrm_entity_file ef
            WHERE ef.file_id = %1
          ";
          $entityParams = [1 => [$ref['id'], 'Integer']];

          $entityResult = CRM_Core_DAO::executeQuery($entityQuery, $entityParams);
          $ref['entities'] = [];
          while ($entityResult->fetch()) {
            $ref['entities'][] = [
              'table' => $entityResult->item_table,
              'id' => $entityResult->item_id,
            ];
          }
        }
      }
    }
    elseif ($directoryType === self::DIRECTORY_CONTRIBUTE) {
      // Check contribution pages
      $contributeQuery = "
        SELECT id, title, intro_text, thankyou_text
        FROM civicrm_contribution_page
        WHERE intro_text LIKE %1 OR thankyou_text LIKE %1
      ";
      $contributeParams = [
        1 => ['%' . $filename . '%', 'String'],
      ];

      $contributeResult = CRM_Core_DAO::executeQuery($contributeQuery, $contributeParams);
      while ($contributeResult->fetch()) {
        $references[] = [
          'type' => 'contribution_page',
          'id' => $contributeResult->id,
          'title' => $contributeResult->title,
          'found_in' => []
        ];

        if (strpos($contributeResult->intro_text, $filename) !== FALSE) {
          $references[count($references) - 1]['found_in'][] = 'intro_text';
        }
        if (strpos($contributeResult->thankyou_text, $filename) !== FALSE) {
          $references[count($references) - 1]['found_in'][] = 'thankyou_text';
        }
      }

      // Check message templates
      $templateQuery = "
        SELECT id, msg_title, msg_subject
        FROM civicrm_msg_template
        WHERE msg_html LIKE %1
      ";
      $templateParams = [
        1 => ['%' . $filename . '%', 'String'],
      ];

      $templateResult = CRM_Core_DAO::executeQuery($templateQuery, $templateParams);
      while ($templateResult->fetch()) {
        $references[] = [
          'type' => 'message_template',
          'id' => $templateResult->id,
          'title' => $templateResult->msg_title,
          'subject' => $templateResult->msg_subject,
        ];
      }
    }

    return $references;
  }

  /**
   * Get directory-specific information
   *
   * @param string $directoryType Directory type
   * @return array Directory information
   */
  private function getDirectorySpecificInfo($directoryType) {
    $config = CRM_Core_Config::singleton();

    if ($directoryType === self::DIRECTORY_CUSTOM) {
      return [
        'type' => 'Custom File Uploads',
        'description' => 'Files uploaded through CiviCRM custom file fields, contact attachments, and other custom uploads.',
        'base_path' => $config->customFileUploadDir,
        'web_accessible' => TRUE,
        'typical_use' => 'Contact attachments, custom field uploads, activity attachments, case documents',
        'common_file_types' => 'PDF documents, Word files, images, spreadsheets'
      ];
    }
    elseif ($directoryType === self::DIRECTORY_CONTRIBUTE) {
      $baseDir = dirname($config->customFileUploadDir);
      return [
        'type' => 'Contribute Images',
        'description' => 'Images used in contribution page headers, footers, and thank-you content.',
        'base_path' => $baseDir . '/persist/contribute/images',
        'web_accessible' => TRUE,
        'typical_use' => 'Contribution page headers, footers, premium images, organization logos',
        'common_file_types' => 'JPEG, PNG, GIF images, SVG graphics'
      ];
    }

    return [
      'type' => 'Unknown',
      'description' => 'File directory type could not be determined.',
      'base_path' => '',
      'web_accessible' => FALSE,
      'typical_use' => 'Unknown',
      'common_file_types' => 'Various'
    ];
  }

  /**
   * Generate download URL for file
   *
   * @param string $filePath File path
   * @param string $directoryType Directory type
   * @return string Download URL
   */
  private function generateDownloadUrl($filePath, $directoryType) {
    return CRM_Utils_System::url('civicrm/file-analyzer/preview', [
      'file' => urlencode($filePath),
      'directory_type' => $directoryType,
      'mode' => self::MODE_DOWNLOAD
    ], TRUE);
  }

  /**
   * Generate preview URL for file
   *
   * @param string $filePath File path
   * @param string $mimeType File mime type
   * @param string $directoryType Directory type
   * @return string Preview URL
   */
  private function generatePreviewUrl($filePath, $mimeType, $directoryType) {
    $config = CRM_Core_Config::singleton();
    // For web-accessible files, try to generate direct URL
    if ($directoryType === self::DIRECTORY_CUSTOM) {
      if (strpos($filePath, $config->customFileUploadDir) === 0) {
        $relativePath = substr($filePath, strlen($config->customFileUploadDir) + 1);
        $fileName = basename($path);
        $url = CRM_Utils_System::url('civicrm/file', "reset=1&filename={$relativePath}&mime-type={$mimeType}", TRUE);
        return $url;
      }
    }
    elseif ($directoryType === self::DIRECTORY_CONTRIBUTE) {
      $baseDir = dirname($config->customFileUploadDir);
      $contributeDir = $baseDir . '/persist/contribute';
      if (strpos($filePath, $contributeDir) === 0) {
        $relativePath = substr($filePath, strlen($contributeDir) + 1);
        $baseUrl = str_replace('/custom/', '/persist/contribute/images/',
          $config->imageUploadURL);
        return $baseUrl . $relativePath;
      }
    }

    // Fallback to preview page URL
    return CRM_Utils_System::url('civicrm/file-analyzer/preview', [
      'file' => urlencode($filePath),
      'directory_type' => $directoryType,
      'mode' => self::MODE_INLINE
    ]);
  }

  /**
   * Generate breadcrumb navigation
   *
   * @param string $directoryType Directory type
   * @return array Breadcrumb items
   */
  private function generateBreadcrumb($directoryType) {
    $breadcrumb = [
      [
        'title' => ts('File Analyzer'),
        'url' => CRM_Utils_System::url('civicrm/file-analyzer/dashboard')
      ]
    ];

    if ($directoryType === self::DIRECTORY_CUSTOM) {
      $breadcrumb[] = [
        'title' => ts('Custom Files Dashboard'),
        'url' => CRM_Utils_System::url('civicrm/file-analyzer/dashboard')
      ];
    }
    elseif ($directoryType === self::DIRECTORY_CONTRIBUTE) {
      $breadcrumb[] = [
        'title' => ts('Public Images Dashboard'),
        'url' => CRM_Utils_System::url('civicrm/file-analyzer/contribute-dashboard')
      ];
    }

    $breadcrumb[] = [
      'title' => ts('File Preview'),
      'url' => NULL // Current page
    ];

    return $breadcrumb;
  }

  /**
   * Generate image preview content
   *
   * @param array $fileInfo File information
   * @return string HTML content
   */
  private function generateImagePreview($fileInfo) {
    $html = '<div class="image-preview-container">';
    $html .= '<div class="image-preview-main">';
    $html .= '<img src="' . htmlspecialchars($fileInfo['preview_url']) . '" ';
    $html .= 'alt="' . htmlspecialchars($fileInfo['name']) . '" ';
    $html .= 'style="max-width: 100%; max-height: 70vh; border-radius: 4px; box-shadow: 0 2px 8px rgba(0,0,0,0.1);" />';
    $html .= '</div>';

    if (isset($fileInfo['dimensions'])) {
      $html .= '<div class="image-info" style="margin-top: 1rem; text-align: center; color: #6c757d;">';
      $html .= '<p><strong>Dimensions:</strong> ' . $fileInfo['dimensions'] . '</p>';

      if (isset($fileInfo['exif'])) {
        $html .= '<div class="exif-data">';
        $html .= '<h4>Camera Information</h4>';
        if (isset($fileInfo['exif']['camera_make'])) {
          $html .= '<p><strong>Camera:</strong> ' . $fileInfo['exif']['camera_make'];
          if (isset($fileInfo['exif']['camera_model'])) {
            $html .= ' ' . $fileInfo['exif']['camera_model'];
          }
          $html .= '</p>';
        }
        if (isset($fileInfo['exif']['date_taken'])) {
          $html .= '<p><strong>Date Taken:</strong> ' . $fileInfo['exif']['date_taken'] . '</p>';
        }
        if (isset($fileInfo['exif']['f_number']) || isset($fileInfo['exif']['exposure_time']) || isset($fileInfo['exif']['iso'])) {
          $html .= '<p><strong>Settings:</strong> ';
          $settings = [];
          if (isset($fileInfo['exif']['f_number'])) {
            $settings[] = $fileInfo['exif']['f_number'];
          }
          if (isset($fileInfo['exif']['exposure_time'])) {
            $settings[] = $fileInfo['exif']['exposure_time'] . 's';
          }
          if (isset($fileInfo['exif']['iso'])) {
            $settings[] = 'ISO ' . $fileInfo['exif']['iso'];
          }
          $html .= implode(', ', $settings) . '</p>';
        }
        $html .= '</div>';
      }
      $html .= '</div>';
    }

    $html .= '</div>';
    return $html;
  }

  /**
   * Generate PDF preview content
   *
   * @param array $fileInfo File information
   * @return string HTML content
   */
  private function generatePDFPreview($fileInfo) {
    $html = '<div class="pdf-preview-container">';
    $html .= '<div class="pdf-preview-toolbar">';
    $html .= '<p><i class="crm-i fa-file-pdf-o"></i> PDF Document</p>';
    $html .= '<a href="' . htmlspecialchars($fileInfo['download_url']) . '" class="button" target="_blank">';
    $html .= '<i class="crm-i fa-download"></i> Download PDF</a>';
    $html .= '</div>';

    $html .= '<div class="pdf-preview-frame">';
    $html .= '<iframe src="' . htmlspecialchars($fileInfo['preview_url']) . '" ';
    $html .= 'style="width: 100%; height: 600px; border: 1px solid #ddd; border-radius: 4px;"></iframe>';
    $html .= '</div>';

    if (!empty($fileInfo['pages'])) {
      $html .= '<div class="pdf-info">';
      $html .= '<p><strong>Pages:</strong> ' . $fileInfo['pages'] . '</p>';
      if (!empty($fileInfo['title'])) {
        $html .= '<p><strong>Title:</strong> ' . htmlspecialchars($fileInfo['title']) . '</p>';
      }
      if (!empty($fileInfo['author'])) {
        $html .= '<p><strong>Author:</strong> ' . htmlspecialchars($fileInfo['author']) . '</p>';
      }
      $html .= '</div>';
    }

    $html .= '</div>';
    return $html;
  }

  /**
   * Generate text preview content
   *
   * @param array $fileInfo File information
   * @return string HTML content
   */
  private function generateTextPreview($fileInfo) {
    $maxPreviewLength = 5000; // Limit preview to 5KB
    $content = file_get_contents($fileInfo['path'], FALSE, NULL, 0, $maxPreviewLength);

    $html = '<div class="text-preview-container">';

    if (isset($fileInfo['line_count'])) {
      $html .= '<div class="text-info" style="margin-bottom: 1rem; padding: 0.5rem; background: #f8f9fa; border-radius: 4px;">';
      $html .= '<p><strong>Lines:</strong> ' . number_format($fileInfo['line_count']) . ' | ';
      if (isset($fileInfo['character_count'])) {
        $html .= '<strong>Characters:</strong> ' . number_format($fileInfo['character_count']) . ' | ';
      }
      if (isset($fileInfo['word_count'])) {
        $html .= '<strong>Words:</strong> ' . number_format($fileInfo['word_count']) . ' | ';
      }
      if (isset($fileInfo['encoding'])) {
        $html .= '<strong>Encoding:</strong> ' . $fileInfo['encoding'];
      }
      $html .= '</p></div>';
    }

    $html .= '<div class="text-preview-content">';
    $html .= '<pre style="background: #f8f9fa; padding: 1rem; border-radius: 4px; overflow: auto; max-height: 500px; font-family: \'Courier New\', monospace; font-size: 0.9rem; white-space: pre-wrap;">';
    $html .= htmlspecialchars($content);

    if (strlen($content) >= $maxPreviewLength) {
      $html .= "\n\n... (content truncated)";
    }

    $html .= '</pre></div>';
    $html .= '</div>';
    return $html;
  }

  /**
   * Generate video preview content
   *
   * @param array $fileInfo File information
   * @return string HTML content
   */
  private function generateVideoPreview($fileInfo) {
    $html = '<div class="video-preview-container">';
    $html .= '<div class="video-preview-player">';
    $html .= '<video controls style="width: 100%; max-width: 800px; max-height: 600px;">';
    $html .= '<source src="' . htmlspecialchars($fileInfo['preview_url']) . '" type="' . htmlspecialchars($fileInfo['mime_type']) . '">';
    $html .= '<p>Your browser does not support the video element.</p>';
    $html .= '</video>';
    $html .= '</div>';
    $html .= '</div>';
    return $html;
  }

  /**
   * Generate audio preview content
   *
   * @param array $fileInfo File information
   * @return string HTML content
   */
  private function generateAudioPreview($fileInfo) {
    $html = '<div class="audio-preview-container">';
    $html .= '<div class="audio-preview-player" style="text-align: center; padding: 2rem;">';
    $html .= '<i class="crm-i fa-music" style="font-size: 3rem; color: #6c757d; margin-bottom: 1rem;"></i>';
    $html .= '<audio controls style="width: 100%; max-width: 400px;">';
    $html .= '<source src="' . htmlspecialchars($fileInfo['preview_url']) . '" type="' . htmlspecialchars($fileInfo['mime_type']) . '">';
    $html .= '<p>Your browser does not support the audio element.</p>';
    $html .= '</audio>';
    $html .= '</div>';
    $html .= '</div>';
    return $html;
  }

  /**
   * Generate generic preview content for unsupported file types
   *
   * @param array $fileInfo File information
   * @return string HTML content
   */
  private function generateGenericPreview($fileInfo) {
    $iconClass = $this->getFileIcon($fileInfo['extension']);

    $html = '<div class="generic-preview-container" style="text-align: center; padding: 3rem;">';
    $html .= '<div class="file-icon" style="margin-bottom: 2rem;">';
    $html .= '<i class="' . $iconClass . '" style="font-size: 4rem; color: #6c757d;"></i>';
    $html .= '</div>';
    $html .= '<h3>' . strtoupper($fileInfo['extension']) . ' File</h3>';
    $html .= '<p style="color: #6c757d; margin: 1rem 0;">Preview not available for this file type.</p>';
    $html .= '<div class="preview-actions">';
    $html .= '<a href="' . htmlspecialchars($fileInfo['download_url']) . '" class="button" style="margin: 0.5rem;">';
    $html .= '<i class="crm-i fa-download"></i> Download File</a>';
    $html .= '</div>';
    $html .= '</div>';
    return $html;
  }

  /**
   * Get appropriate icon class for file extension
   *
   * @param string $extension File extension
   * @return string CSS icon class
   */
  private function getFileIcon($extension) {
    $icons = [
      'pdf' => 'crm-i fa-file-pdf-o',
      'doc' => 'crm-i fa-file-word-o',
      'docx' => 'crm-i fa-file-word-o',
      'xls' => 'crm-i fa-file-excel-o',
      'xlsx' => 'crm-i fa-file-excel-o',
      'ppt' => 'crm-i fa-file-powerpoint-o',
      'pptx' => 'crm-i fa-file-powerpoint-o',
      'zip' => 'crm-i fa-file-archive-o',
      'rar' => 'crm-i fa-file-archive-o',
      '7z' => 'crm-i fa-file-archive-o',
      'mp3' => 'crm-i fa-file-audio-o',
      'wav' => 'crm-i fa-file-audio-o',
      'mp4' => 'crm-i fa-file-video-o',
      'avi' => 'crm-i fa-file-video-o',
      'txt' => 'crm-i fa-file-text-o',
      'csv' => 'crm-i fa-file-text-o',
      'xml' => 'crm-i fa-file-code-o',
      'html' => 'crm-i fa-file-code-o',
      'css' => 'crm-i fa-file-code-o',
      'js' => 'crm-i fa-file-code-o',
    ];

    $extension = strtolower($extension);
    return isset($icons[$extension]) ? $icons[$extension] : 'crm-i fa-file-o';
  }

  /**
   * Generate image thumbnail
   *
   * @param string $filePath Image file path
   * @param int $size Thumbnail size
   * @return resource|false Image resource or false on failure
   */
  private function generateImageThumbnail($filePath, $size) {
    $imageInfo = getimagesize($filePath);
    if (!$imageInfo) {
      return FALSE;
    }

    // Create source image
    $source = NULL;
    switch ($imageInfo[2]) {
      case IMAGETYPE_JPEG:
        $source = imagecreatefromjpeg($filePath);
        break;
      case IMAGETYPE_PNG:
        $source = imagecreatefrompng($filePath);
        break;
      case IMAGETYPE_GIF:
        $source = imagecreatefromgif($filePath);
        break;
      default:
        return FALSE;
    }

    if (!$source) {
      return FALSE;
    }

    $originalWidth = imagesx($source);
    $originalHeight = imagesy($source);

    // Calculate thumbnail dimensions maintaining aspect ratio
    if ($originalWidth > $originalHeight) {
      $thumbWidth = $size;
      $thumbHeight = intval(($originalHeight * $size) / $originalWidth);
    }
    else {
      $thumbHeight = $size;
      $thumbWidth = intval(($originalWidth * $size) / $originalHeight);
    }

    // Create thumbnail
    $thumbnail = imagecreatetruecolor($thumbWidth, $thumbHeight);

    // Preserve transparency for PNG and GIF
    if ($imageInfo[2] === IMAGETYPE_PNG || $imageInfo[2] === IMAGETYPE_GIF) {
      imagealphablending($thumbnail, FALSE);
      imagesavealpha($thumbnail, TRUE);
      $transparent = imagecolorallocatealpha($thumbnail, 255, 255, 255, 127);
      imagefill($thumbnail, 0, 0, $transparent);
    }

    // Resize
    imagecopyresampled($thumbnail, $source, 0, 0, 0, 0, $thumbWidth, $thumbHeight, $originalWidth, $originalHeight);
    imagedestroy($source);

    return $thumbnail;
  }

  /**
   * Output default thumbnail for non-image files
   *
   * @param string $extension File extension
   */
  private function outputDefaultThumbnail($extension) {
    // Create a simple colored square with file extension text
    $size = 150;
    $thumbnail = imagecreatetruecolor($size, $size);

    // Color scheme based on file type
    $colors = [
      'pdf' => [255, 82, 82],    // Red
      'doc' => [65, 131, 215],   // Blue
      'xls' => [34, 139, 34],    // Green
      'txt' => [128, 128, 128],  // Gray
      'zip' => [255, 165, 0],    // Orange
    ];

    $extension = strtolower($extension);
    $color = isset($colors[$extension]) ? $colors[$extension] : [108, 117, 125]; // Default gray

    $bgColor = imagecolorallocate($thumbnail, $color[0], $color[1], $color[2]);
    $textColor = imagecolorallocate($thumbnail, 255, 255, 255);

    imagefill($thumbnail, 0, 0, $bgColor);

    // Add extension text
    $text = strtoupper($extension);
    $fontSize = 5;
    $textWidth = imagefontwidth($fontSize) * strlen($text);
    $textHeight = imagefontheight($fontSize);
    $x = ($size - $textWidth) / 2;
    $y = ($size - $textHeight) / 2;

    imagestring($thumbnail, $fontSize, $x, $y, $text, $textColor);

    header('Content-Type: image/png');
    header('Cache-Control: public, max-age=3600');
    imagepng($thumbnail);
    imagedestroy($thumbnail);
  }

  /**
   * Format file size in human readable format
   *
   * @param int $bytes File size in bytes
   * @return string Formatted file size
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
}
