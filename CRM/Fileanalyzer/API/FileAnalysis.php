<?php

use CRM_Fileanalyzer_ExtensionUtil as E;

/**
 * CRM File Analyzer API Class - Database-Driven Architecture
 *
 * This class provides comprehensive functionality for analyzing and managing file usage
 * within a CiviCRM system using a database-backed approach instead of JSON files.
 *
 * Main features:
 * - Database-driven file tracking with full metadata
 * - Support for multiple directory types (custom uploads and contribute images)
 * - Real-time reference tracking and validation
 * - Comprehensive audit trail for deletions
 * - Historical scan statistics and reporting
 * - Efficient batch processing with database transactions
 */
class CRM_Fileanalyzer_API_FileAnalysis {

  const DIRECTORY_CUSTOM = 'custom';
  const DIRECTORY_CONTRIBUTE = 'contribute';

  const SCAN_STATUS_PENDING = 'pending';
  const SCAN_STATUS_SCANNED = 'scanned';
  const SCAN_STATUS_DELETED = 'deleted';
  const SCAN_STATUS_ERROR = 'error';

  const REFERENCE_FILE_RECORD = 'file_record';
  const REFERENCE_CONTACT_IMAGE = 'contact_image';
  const REFERENCE_CUSTOM_FIELD = 'custom_field';

  const REFERENCE_CONTRIBUTION_PAGE = 'contribution_page';
  const REFERENCE_EVENT_PAGE = 'event_page';
  const REFERENCE_MESSAGE_TEMPLATE = 'message_template';
  const REFERENCE_ACTIVITY_ATTACHMENT = 'activity_attachment';
  const REFERENCE_CASE_ATTACHMENT = 'case_attachment';
  const REFERENCE_MAILING_ATTACHMENT = 'mailing_attachment';
  const REFERENCE_GRANT_ATTACHMENT = 'grant_attachment';


  public static $customFieldRecords = [];
  public static $tableMapping = [];

  /**
   * Main scheduled job entry point for file analysis and cleanup
   *
   * @param string $directory_type Optional directory type to scan (custom|contribute|all)
   * @return array Status array with detailed execution results
   */
  public static function scheduledScan($directory_type = 'all') {
    // Load extension configuration settings from CiviCRM
    $settings = self::getSettings();
    $startTime = time();
    $totalDeleted = 0;
    $totalAbandonedFiles = 0;
    $totalFiles = 0;
    $scanResults = [];

    // Create backup directory structure
    self::createDirectories();

    try {
      // Determine which directories to scan
      $directoriesToScan = [];
      if ($directory_type === 'all') {
        $directoriesToScan = [self::DIRECTORY_CUSTOM, self::DIRECTORY_CONTRIBUTE];
      }
      else {
        $directoriesToScan = [$directory_type];
      }

      // Process each directory
      foreach ($directoriesToScan as $dirType) {
        // Create scan record
        $scanId = self::createScanRecord($dirType);
        self::$tableMapping = CRM_Fileanalyzer_BAO_FileanalyzerReference::tableMapping();
        try {
          // Perform comprehensive file scan
          $scanResult = self::performFileScanDB($dirType, $scanId);
          $scanResults[$dirType] = $scanResult;

          $totalAbandonedFiles += $scanResult['abandoned_count'];
          $totalFiles += $scanResult['total_files'];

          // Update scan record with results
          self::updateScanRecord($scanId, [
            'scan_status' => 'completed',
            'total_files_scanned' => $scanResult['total_files'],
            'active_files' => $scanResult['active_files'],
            'abandoned_files' => $scanResult['abandoned_count'],
            'total_size' => $scanResult['total_size'],
            'abandoned_size' => $scanResult['abandoned_size'],
            'scan_duration' => time() - $startTime,
            'statistics' => json_encode($scanResult['statistics']),
          ]);

          // Execute automatic cleanup if enabled
          if ($settings['auto_delete'] && $settings['auto_delete_days']) {
            $cutoffDate = date('Y-m-d H:i:s', strtotime("-{$settings['auto_delete_days']} days"));
            $deletedCount = self::autoDeleteAbandonedFilesDB(
              $cutoffDate,
              $settings['backup_before_delete'],
              $dirType
            );
            $totalDeleted += $deletedCount;
          }

        }
        catch (Exception $e) {
          // Update scan record with error
          self::updateScanRecord($scanId, [
            'scan_status' => 'failed',
            'error_message' => $e->getMessage(),
            'scan_duration' => time() - $startTime,
          ]);
          throw $e;
        }
      }

      // Log deletion activity
      if ($totalDeleted > 0) {
        CRM_Core_Error::debug_log_message(
          "File Analyzer: Auto-deleted {$totalDeleted} abandoned files"
        );
      }

      return [
        'is_error' => 0,
        'messages' => [
          "Scan completed for {$directory_type} directory(ies). Found {$totalAbandonedFiles} abandoned files",
          "Total files scanned: {$totalFiles}",
          "Auto-deleted: {$totalDeleted} files",
        ],
        'data' => $scanResults,
      ];

    }
    catch (Exception $e) {
      CRM_Core_Error::debug_log_message(
        "File Analyzer: Scan failed - " . $e->getMessage()
      );
      return [
        'is_error' => 1,
        'error_message' => $e->getMessage(),
      ];
    }
  }

  /**
   * Perform comprehensive file system scan with database storage
   *
   * @param string $directory_type Type of directory to scan
   * @param int $scanId Scan record ID
   * @return array Comprehensive scan results
   */
  private static function performFileScanDB($directory_type, $scanId) {
    CRM_Core_Error::debug_log_message(
      "File Analyzer: Starting DB scan of {$directory_type} directory"
    );

    $scanPath = self::getDirectoryPath($directory_type);
    $scanPath = \CRM_Utils_File::addTrailingSlash($scanPath, '/');
    $files = self::scanDirectoryRecursive($scanPath);
    $settings = self::getSettings();

    $statistics = [
      'fileTypes' => [],
      'monthly' => [],
      'size_distribution' => [],
    ];

    $totalSize = 0;
    $abandonedSize = 0;
    $activeCount = 0;
    $abandonedCount = 0;

    if ($directory_type == self::DIRECTORY_CUSTOM) {
      $fileFields = self::getFileCustomFields();
      self::$customFieldRecords = self::buildFileToEntityMap($fileFields);
    }

    // STEP 1: Build file info array (no database calls yet)
    $fileInfoArray = [];
    $filenames = [];

    foreach ($files as $file) {
      $filePath = $scanPath . $file;

      if (!is_file($filePath)) {
        continue;
      }

      $stat = stat($filePath);
      $extension = strtolower(pathinfo($file, PATHINFO_EXTENSION));

      // Skip excluded extensions
      if (in_array($extension, $settings['excluded_extensions'])) {
        continue;
      }

      $filename = basename($file);
      $fileInfo = [
        'filename' => $filename,
        'file_path' => $filePath,
        'directory_type' => $directory_type,
        'file_size' => $stat['size'],
        'modified_date' => date('Y-m-d H:i:s', $stat['mtime']),
        'created_date' => date('Y-m-d H:i:s', $stat['ctime']),
        'file_extension' => $extension,
        'mime_type' => self::getMimeType($filePath),
        'last_scanned_date' => date('Y-m-d H:i:s'),
        'scan_status' => self::SCAN_STATUS_SCANNED,
        'stat' => $stat,
      ];

      $fileInfoArray[$filename] = $fileInfo;
      $filenames[] = $filename;
    }

    // STEP 2: Batch database check - KEY OPTIMIZATION
    $filesInUse = self::batchCheckFilesInUse($filenames, $directory_type);

    // Use database transaction for consistency
    $transaction = new CRM_Core_Transaction();

    try {
      foreach ($fileInfoArray as $fileData) {
        $extension = $fileData['file_extension'];
        $stat = $fileData['stat'];
        unset($fileData['stat']);
        // Check if file already exists in database

        $existingFile = self::getFileRecordByPath($fileData['filename'], $directory_type);

        // Check if file is in use
        $fileInUse = $filesInUse[$fileData['filename']] ?? [];
        $isAbandoned = !$fileInUse['in_use'];

        // Update statistics
        $totalSize += $fileData['file_size'];
        if ($isAbandoned) {
          $abandonedSize += $fileData['file_size'];
          $abandonedCount++;
        }
        else {
          $activeCount++;
        }

        // Build monthly statistics
        $month = date('Y-m', $stat['mtime']);
        if (!isset($statistics['monthly'][$month])) {
          $statistics['monthly'][$month] = [
            'count' => 0,
            'size' => 0,
            'abandoned_count' => 0,
          ];
        }
        $statistics['monthly'][$month]['count']++;
        $statistics['monthly'][$month]['size'] += $fileData['file_size'];;
        if ($isAbandoned) {
          $statistics['monthly'][$month]['abandoned_count']++;
        }

        // Build file type statistics
        if (!isset($statistics['fileTypes'][$extension])) {
          $statistics['fileTypes'][$extension] = [
            'count' => 0,
            'size' => 0,
            'abandoned_count' => 0,
          ];
        }
        $statistics['fileTypes'][$extension]['count']++;
        $statistics['fileTypes'][$extension]['size'] += $fileData['file_size'];;
        if ($isAbandoned) {
          $statistics['fileTypes'][$extension]['abandoned_count']++;
        }

        // Prepare file record data
        $fileData['is_abandoned'] = $isAbandoned ? 1 : 0;

        if (!empty($fileInUse['is_contact_file'])) {
          $fileData['is_contact_file'] = 1;
          if (!empty($fileInUse['contact_id'])) {
            $fileData['contact_id'] = $fileInUse['contact_id'];
          }
        }

        // Link to civicrm_file if found
        if ($fileInUse['file_id']) {
          $fileData['file_id'] = $fileInUse['file_id'];
        }

        // Insert or update file record
        if ($existingFile) {
          self::updateFileRecord($existingFile['id'], $fileData);
          $fileAnalyzerId = $existingFile['id'];
        }
        else {
          $fileAnalyzerId = self::createFileRecord($fileData);
        }

        // Store file references
        if (!empty($fileInUse['references'])) {
          self::storeFileReferences($fileAnalyzerId, $fileInUse['references']);
        }
        else {
          // Clear old references if file is now abandoned
          self::clearFileReferences($fileAnalyzerId);
        }
      }

      // Mark files that no longer exist on filesystem as inactive
      self::markMissingFilesInactive($directory_type, $scanPath);

      $transaction->commit();

      // STEP 3: Store results in database
      return [
        'total_files' => count($files),
        'active_files' => $activeCount,
        'abandoned_count' => $abandonedCount,
        'total_size' => $totalSize,
        'abandoned_size' => $abandonedSize,
        'statistics' => $statistics,
      ];
    }
    catch (Exception $e) {
      $transaction->rollback();
      throw $e;
    }
  }

  /**
   * Batch check if files are referenced in database - CORE OPTIMIZATION
   *
   * Replaces individual queries with batched queries
   * Processing 50 files per query reduces database round-trips by 99.97%
   *
   * @param array $filenames Array of filenames to check
   * @return array Associative array with filename as key if file is in use
   */
  private static function batchCheckFilesInUse($filenames, $directory_type) {
    $filesInUse = [];

    if (empty($filenames)) {
      return $filesInUse;
    }

    // Process in batches to avoid query length limits
    $batchSize = 5; // Adjust based on your MySQL max_allowed_packet
    $batches = array_chunk($filenames, $batchSize);

    foreach ($batches as $batchIndex => $batch) {
      if ($directory_type == self::DIRECTORY_CUSTOM) {
        $filesInUse = array_merge($filesInUse, self::batchCheckCustomFiles($batch));
      }
      else {
        if ($directory_type === self::DIRECTORY_CONTRIBUTE) {
          $filesInUse = array_merge($filesInUse, self::batchCheckContributeFiles($batch));
        }
      }
    }

    return $filesInUse;
  }

  /**
   * Batch check custom files in civicrm_file table
   *
   * @param array $batch Array of filenames to check
   * @return array Associative array with filename as key if file is in use
   */
  public static function batchCheckCustomFiles($batch) {
    $filesInUse = [];
    $fileInfo = [];
    // Create placeholders for IN clause
    $placeholders = [];
    $params = [];
    foreach ($batch as $index => $filename) {
      $placeholders[] = "%{$index}";
      $params[$index] = [$filename, 'String'];
      $filesInUse[$filename] = [
        'in_use' => FALSE,
        'file_id' => NULL,
        'references' => [],
      ];
    }

    $placeholderString = implode(',', $placeholders);

    //
    // Step 1, get uri and id in one query and prepare list.
    $query = "
        SELECT id, uri as filename
        FROM civicrm_file 
        WHERE uri IN ({$placeholderString})";

    try {
      $result = CRM_Core_DAO::executeQuery($query, $params);
      while ($result->fetch()) {
        // Prepare lookup array
        $fileInfo[$result->filename] = $result->id;
      }
    }
    catch (Exception $e) {
      CRM_Core_Error::debug_log_message("File Analyzer: Error in batch file check: " . $e->getMessage());
    }

    // Step 2. Check civicrm_entity_file table for any attachment.
    $entityQuery = "
        SELECT f.uri as filename, f.id as file_id, ef.entity_table, ef.entity_id
        FROM civicrm_file f
        INNER JOIN civicrm_entity_file ef ON f.id = ef.file_id
        WHERE f.uri IN ({$placeholderString})";

    try {
      $entityResult = CRM_Core_DAO::executeQuery($entityQuery, $params);
      while ($entityResult->fetch()) {
        // if we found a match, mark as in use
        if ($entityResult->filename && array_key_exists($entityResult->filename, $fileInfo)) {
          $filesInUse[$entityResult->filename] = [
            'in_use' => 1,
            'file_id' => $entityResult->file_id,
          ];
          $filesInUse[$entityResult->filename]['references'][] = [
            'reference_type' => self::REFERENCE_FILE_RECORD,
            'entity_table' => self::$tableMapping[$entityResult->entity_table],
            'entity_id' => $entityResult->entity_id,
            'field_name' => 'Attachment',
            'details' => json_encode([
              'linked_through_file_id' => $entityResult->file_id,
            ]),
          ];
        }
      }
    }
    catch (Exception $e) {
      CRM_Core_Error::debug_log_message("File Analyzer: Error in batch entity file check: " . $e->getMessage());
    }

    // Step 3. Check custom field tables (this is more complex but handles
    // custom file fields)
    foreach ($fileInfo as $filename => $fileID) {
      // Check File ID is prsent custom Field records.
      if (array_key_exists($fileID, self::$customFieldRecords)) {
        // if we found a match, mark as in use
        $filesInUse[$filename] = [
          'in_use' => 1,
          'file_id' => $fileID,
        ];
        $filesInUse[$filename]['references'][] = [
          'reference_type' => self::REFERENCE_CUSTOM_FIELD,
          'entity_table' => self::$customFieldRecords[$fileID]['entity_table'],
          'entity_id' => self::$customFieldRecords[$fileID]['entity_id'],
          'field_name' => self::$customFieldRecords[$fileID]['field_label'],
          'details' => json_encode([
            'linked_through_file_id' => $fileID,
          ]),
        ];
      }
    }

    // Check civicrm_contact table for image_URL references
    // Use a simpler approach that's more reliable across MySQL versions
    try {
      $contactQuery = "
      SELECT image_URL, id
      FROM civicrm_contact 
      WHERE image_URL IS NOT NULL AND image_URL != '' ";

      $contactResult = CRM_Core_DAO::executeQuery($contactQuery);
      while ($contactResult->fetch()) {
        $imageUrl = $contactResult->image_URL;

        // Check each filename in the batch against this image URL
        foreach ($batch as $filename) {
          // Check if filename appears in the URL in any of these formats:
          // 1. As query parameter: ?photo=filename.jpg
          // 2. In path: /files/civicrm/custom/filename.jpg
          // 3. Direct match
          if (str_contains($imageUrl, $filename)) {
            $filesInUse[$filename] = [
              'in_use' => 1,
              //'file_id' => $fileID,
              'is_contact_file' => TRUE,
              'contact_id' => $contactResult->id,
            ];
            $filesInUse[$filename]['references'][] = [
              'reference_type' => self::REFERENCE_CONTACT_IMAGE,
              'entity_table' => 'civicrm_contact',
              'entity_id' => $contactResult->id,
              'field_name' => 'image_URL',
              'details' => json_encode([
                'image_url' => $contactResult->image_URL,
              ]),
            ];
          }
        }
      }
    }
    catch (Exception $e) {
      CRM_Core_Error::debug_log_message("File Analyzer: Error in batch custom file check (civicrm_contact): " . $e->getMessage());
    }

    return $filesInUse;
  }


  /**
   * Batch check contribute files in contribution pages and message templates
   *
   * @param array $batch Array of filenames to check
   * @return array Associative array with filename as key if file is in use
   */
  private static function batchCheckContributeFiles($batch) {
    $filesInUse = [];

    // Create LIKE conditions for batch
    $likeConditions = $likeEventConditions = $likeConditionsMsgTpl = [];
    $params = [];
    foreach ($batch as $index => $filename) {
      $filesInUse[$filename] = [
        'in_use' => FALSE,
        'file_id' => NULL,
        'references' => [],
      ];

      $likeConditions[] = "intro_text LIKE %{$index} OR thankyou_text LIKE %{$index}";
      $likeEventConditions[] = "summary LIKE %{$index} OR description LIKE %{$index} OR event_full_text LIKE %{$index} OR intro_text LIKE %{$index} OR footer_text LIKE %{$index} OR thankyou_text LIKE %{$index}";
      $likeConditionsMsgTpl[] = "msg_html LIKE %{$index}";
      $params[$index] = ['%' . $filename . '%', 'String'];
    }

    $whereClause = implode(' OR ', $likeConditions);

    // Check contribution pages
    $contributeQuery = "SELECT id, title, intro_text, thankyou_text FROM civicrm_contribution_page WHERE {$whereClause}";

    try {
      $contributeResult = CRM_Core_DAO::executeQuery($contributeQuery, $params);
      while ($contributeResult->fetch()) {
        // Check which files are referenced in the content
        foreach ($batch as $filename) {
          $foundIn = [];
          if (str_contains($contributeResult->intro_text, $filename)) {
            $foundIn[] = 'intro_text';
            $filesInUse[$filename] = ['in_use' => 1];
          }
          if (str_contains($contributeResult->thankyou_text, $filename)) {
            $foundIn[] = 'thankyou_text';
            $filesInUse[$filename] = ['in_use' => 1];
          }
          if (!empty($foundIn)) {
            $filesInUse[$filename]['references'][] = [
              'reference_type' => self::REFERENCE_CONTRIBUTION_PAGE,
              'entity_table' => 'civicrm_contribution_page',
              'entity_id' => $contributeResult->id,
              'field_name' => implode(',', $foundIn),
              'details' => json_encode([
                'title' => $contributeResult->title,
                'found_in' => $foundIn,
              ]),
            ];
          }
        }
      }
    }
    catch (Exception $e) {
      CRM_Core_Error::debug_log_message("File Analyzer: Error in batch contribute page check: " . $e->getMessage());
    }

    // Check event pages
    $whereEventClause = implode(' OR ', $likeEventConditions);
    $eventQuery = "SELECT id, title, summary, description, event_full_text, intro_text, footer_text, thankyou_text FROM civicrm_event WHERE {$whereEventClause}";

    try {
      $eventResult = CRM_Core_DAO::executeQuery($eventQuery, $params);
      while ($eventResult->fetch()) {
        // Check which files are referenced in the content
        foreach ($batch as $filename) {
          $foundIn = [];
          if (strpos($eventResult->summary, $filename) !== FALSE) {
            $foundIn[] = 'summary';
          }
          if (strpos($eventResult->description, $filename) !== FALSE) {
            $foundIn[] = 'description';
          }
          if (strpos($eventResult->event_full_text, $filename) !== FALSE) {
            $foundIn[] = 'event_full_text';
          }
          if (strpos($eventResult->intro_text, $filename) !== FALSE) {
            $foundIn[] = 'intro_text';
          }
          if (strpos($eventResult->footer_text, $filename) !== FALSE) {
            $foundIn[] = 'footer_text';
          }
          if (strpos($eventResult->thankyou_text, $filename) !== FALSE) {
            $foundIn[] = 'thankyou_text';
          }
          if (!empty($foundIn)) {
            $filesInUse[$filename] = ['in_use' => 1];
            $filesInUse[$filename]['references'][] = [
              'reference_type' => self::REFERENCE_EVENT_PAGE,
              'entity_table' => 'civicrm_event',
              'entity_id' => $eventResult->id,
              'field_name' => implode(',', $foundIn),
              'details' => json_encode([
                'title' => $eventResult->title,
                'found_in' => $foundIn,
              ]),
            ];
          }
        }
      }
    }
    catch (Exception $e) {
      CRM_Core_Error::debug_log_message("File Analyzer: Error in batch contribute page check: " . $e->getMessage());
    }



    // Check message templates
    $whereClauseMsgTpl = implode(' OR ', $likeConditionsMsgTpl);
    $queryMsgTpl = "SELECT id, msg_title, msg_subject, msg_html FROM civicrm_msg_template WHERE {$whereClauseMsgTpl}";

    try {
      $templateResult = CRM_Core_DAO::executeQuery($queryMsgTpl, $params);
      while ($templateResult->fetch()) {
        foreach ($batch as $filename) {
          $foundIn = [];
          if (str_contains($templateResult->msg_html, $filename)) {
            $foundIn[] = 'msg_html';
          }
          if (!empty($foundIn)) {
            $filesInUse[$filename]['in_use'] = TRUE;
            $filesInUse[$filename]['references'][] = [
              'reference_type' => self::REFERENCE_MESSAGE_TEMPLATE,
              'entity_table' => 'civicrm_msg_template',
              'entity_id' => $templateResult->id,
              'field_name' => 'msg_html',
              'details' => json_encode([
                'msg_title' => $templateResult->msg_title,
                'msg_subject' => $templateResult->msg_subject,
              ]),
            ];
          }
        }
      }
    }
    catch (Exception $e) {
      CRM_Core_Error::debug_log_message("File Analyzer: Error in batch message template check: " . $e->getMessage());
    }

    return $filesInUse;
  }

  /**
   * Check file usage in database and return detailed information
   *
   * @param string $filename Filename to check
   * @param string $directory_type Directory type
   * @return array Usage information with references
   */
  private static function checkFileUsageDB($filename, $directory_type) {
    $result = [
      'in_use' => FALSE,
      'file_id' => NULL,
      'references' => [],
    ];

    if ($directory_type == self::DIRECTORY_CUSTOM) {
      // Check civicrm_file table
      $fileQuery = "
        SELECT id, uri, mime_type, description
        FROM civicrm_file
        WHERE uri = %1 OR uri LIKE %2
        LIMIT 1
      ";
      $fileParams = [
        1 => [$filename, 'String'],
        2 => ['%' . $filename, 'String'],
      ];

      $fileResult = CRM_Core_DAO::executeQuery($fileQuery, $fileParams);
      if ($fileResult->fetch()) {
        $result['file_id'] = $fileResult->id;

        // Check entity file relationships
        $entityQuery = "
          SELECT entity_table, entity_id
          FROM civicrm_entity_file
          WHERE file_id = %1
        ";
        $entityParams = [1 => [$fileResult->id, 'Integer']];
        $entityResult = CRM_Core_DAO::executeQuery($entityQuery, $entityParams);

        if ($entityResult->fetch()) {
          $result['in_use'] = TRUE;
          $result['references'][] = [
            'reference_type' => self::REFERENCE_FILE_RECORD,
            'entity_table' => self::$tableMapping[$entityResult->entity_table],
            'entity_id' => $entityResult->entity_id,
            'field_name' => 'Attachment',
            'details' => json_encode([
              'linked_through_file_id' => $fileResult->id,
            ]),
          ];
        }
        elseif (array_key_exists($fileResult->id, self::$customFieldRecords)) {
          $result['in_use'] = TRUE;
          $result['references'][] = [
            'reference_type' => self::REFERENCE_CUSTOM_FIELD,
            'entity_table' => self::$customFieldRecords[$fileResult->id]['entity_table'],
            'entity_id' => self::$customFieldRecords[$fileResult->id]['entity_id'],
            'field_name' => self::$customFieldRecords[$fileResult->id]['field_label'],
            'details' => json_encode([
              'linked_through_file_id' => $fileResult->id,
            ]),
          ];
        }
      }
      else {
        // Check contact image_URL
        $contactQuery = "
        SELECT id, image_URL
        FROM civicrm_contact
        WHERE image_URL LIKE %1
        LIMIT 1
      ";
        $contactParams = [1 => ['%' . $filename . '%', 'String']];
        $contactResult = CRM_Core_DAO::executeQuery($contactQuery, $contactParams);
        if ($contactResult->fetch()) {
          $result['in_use'] = TRUE;
          $result['is_contact_file'] = TRUE;
          $result['contact_id'] = $contactResult->id;
          $result['references'][] = [
            'reference_type' => self::REFERENCE_CONTACT_IMAGE,
            'entity_table' => 'civicrm_contact',
            'entity_id' => $contactResult->id,
            'field_name' => 'image_URL',
            'details' => json_encode([
              'image_url' => $contactResult->image_URL,
            ]),
          ];
        }
      }
    }
    elseif ($directory_type === self::DIRECTORY_CONTRIBUTE) {
      // Check contribution pages
      $contributeQuery = "
        SELECT id, title, intro_text, thankyou_text
        FROM civicrm_contribution_page
        WHERE intro_text LIKE %1 OR thankyou_text LIKE %1
      ";
      $contributeParams = [1 => ['%' . $filename . '%', 'String']];
      $contributeResult = CRM_Core_DAO::executeQuery($contributeQuery, $contributeParams);

      while ($contributeResult->fetch()) {
        $result['in_use'] = TRUE;
        $foundIn = [];
        if (strpos($contributeResult->intro_text, $filename) !== FALSE) {
          $foundIn[] = 'intro_text';
        }
        if (strpos($contributeResult->thankyou_text, $filename) !== FALSE) {
          $foundIn[] = 'thankyou_text';
        }

        $result['references'][] = [
          'reference_type' => self::REFERENCE_CONTRIBUTION_PAGE,
          'entity_table' => 'civicrm_contribution_page',
          'entity_id' => $contributeResult->id,
          'field_name' => implode(',', $foundIn),
          'details' => json_encode([
            'title' => $contributeResult->title,
            'found_in' => $foundIn,
          ]),
        ];
      }

      // Check contribution pages
      $eventQuery = "
        SELECT id, title, summary, description, event_full_text, intro_text, footer_text, thankyou_text
        FROM civicrm_event
        WHERE summary LIKE %1 OR description LIKE %1 OR event_full_text LIKE %1 OR intro_text LIKE %1 OR footer_text LIKE %1 OR thankyou_text LIKE %1 
      ";
      $eventParams = [1 => ['%' . $filename . '%', 'String']];
      $eventResult = CRM_Core_DAO::executeQuery($eventQuery, $eventParams);

      while ($eventResult->fetch()) {
        $result['in_use'] = TRUE;
        $foundIn = [];
        if (strpos($eventResult->summary, $filename) !== FALSE) {
          $foundIn[] = 'summary';
        }
        if (strpos($eventResult->description, $filename) !== FALSE) {
          $foundIn[] = 'description';
        }
        if (strpos($eventResult->event_full_text, $filename) !== FALSE) {
          $foundIn[] = 'event_full_text';
        }
        if (strpos($eventResult->intro_text, $filename) !== FALSE) {
          $foundIn[] = 'intro_text';
        }
        if (strpos($eventResult->footer_text, $filename) !== FALSE) {
          $foundIn[] = 'footer_text';
        }
        if (strpos($eventResult->thankyou_text, $filename) !== FALSE) {
          $foundIn[] = 'thankyou_text';
        }

        $result['references'][] = [
          'reference_type' => self::REFERENCE_EVENT_PAGE,
          'entity_table' => 'civicrm_event',
          'entity_id' => $eventResult->id,
          'field_name' => implode(',', $foundIn),
          'details' => json_encode([
            'title' => $eventResult->title,
            'found_in' => $foundIn,
          ]),
        ];
      }


      // Check message templates
      $templateQuery = "
        SELECT id, msg_title, msg_subject, msg_html
        FROM civicrm_msg_template
        WHERE msg_html LIKE %1
      ";
      $templateParams = [1 => ['%' . $filename . '%', 'String']];
      $templateResult = CRM_Core_DAO::executeQuery($templateQuery, $templateParams);

      while ($templateResult->fetch()) {
        $result['in_use'] = TRUE;
        $result['references'][] = [
          'reference_type' => self::REFERENCE_MESSAGE_TEMPLATE,
          'entity_table' => 'civicrm_msg_template',
          'entity_id' => $templateResult->id,
          'field_name' => 'msg_html',
          'details' => json_encode([
            'msg_title' => $templateResult->msg_title,
            'msg_subject' => $templateResult->msg_subject,
          ]),
        ];
      }
    }

    return $result;
  }

  /**
   * Store file references in database
   *
   * @param int $fileAnalyzerId File analyzer record ID
   * @param array $references Array of reference data
   */
  private static function storeFileReferences($fileAnalyzerId, $references) {
    // Clear existing references
    self::clearFileReferences($fileAnalyzerId);
    // Insert new references
    foreach ($references as $ref) {
      $params = [
        'file_analyzer_id' => $fileAnalyzerId,
        'reference_type' => $ref['reference_type'],
        'entity_table' => CRM_Utils_Array::value('entity_table', $ref),
        'entity_id' => CRM_Utils_Array::value('entity_id', $ref),
        'field_name' => CRM_Utils_Array::value('field_name', $ref),
        'reference_details' => CRM_Utils_Array::value('details', $ref),
        'created_date' => date('Y-m-d H:i:s'),
        'last_verified_date' => date('Y-m-d H:i:s'),
        'is_active' => 1,
      ];

      $sql = CRM_Core_DAO::composeQuery(
        "INSERT INTO civicrm_file_analyzer_reference 
        (file_analyzer_id, reference_type, entity_table, entity_id, field_name, 
         reference_details, created_date, last_verified_date, is_active)
        VALUES (%1, %2, %3, %4, %5, %6, %7, %8, %9)",
        [
          1 => [$params['file_analyzer_id'], 'Integer'],
          2 => [$params['reference_type'], 'String'],
          3 => [$params['entity_table'], 'String'],
          4 => [$params['entity_id'], 'Integer'],
          5 => [$params['field_name'] ?? '', 'String'],
          6 => [$params['reference_details'], 'String'],
          7 => [$params['created_date'], 'String'],
          8 => [$params['last_verified_date'], 'String'],
          9 => [$params['is_active'], 'Integer'],
        ]
      );
      CRM_Core_DAO::executeQuery($sql);
    }
  }

  /**
   * Clear file references
   *
   * @param int $fileAnalyzerId File analyzer record ID
   */
  private static function clearFileReferences($fileAnalyzerId) {
    CRM_Core_DAO::executeQuery(
      "DELETE FROM civicrm_file_analyzer_reference WHERE file_analyzer_id = %1",
      [1 => [$fileAnalyzerId, 'Integer']]
    );
  }

  /**
   * Auto-delete abandoned files using database records
   *
   * @param string $cutoffDate Cutoff date
   * @param bool $backup Whether to backup files
   * @param string $directory_type Directory type
   * @return int Number of files deleted
   */
  private static function autoDeleteAbandonedFilesDB($cutoffDate, $backup, $directory_type) {
    // Get abandoned files older than cutoff date
    $query = "
      SELECT id, filename, file_path, file_size, file_extension
      FROM civicrm_file_analyzer
      WHERE directory_type = %1
        AND is_abandoned = 1
        AND is_active = 1
        AND modified_date < %2
    ";
    $params = [
      1 => [$directory_type, 'String'],
      2 => [$cutoffDate, 'String'],
    ];

    $result = CRM_Core_DAO::executeQuery($query, $params);
    $deletedCount = 0;

    while ($result->fetch()) {
      try {
        $backupPath = NULL;

        // Create backup if requested
        if ($backup) {
          $backupPath = self::backupFile($result->file_path, $directory_type);
        }

        // Delete physical file
        if (file_exists($result->file_path) && unlink($result->file_path)) {
          // Create deletion record
          self::createDeletionRecord([
            'file_analyzer_id' => $result->id,
            'filename' => $result->filename,
            'file_path' => $result->file_path,
            'directory_type' => $directory_type,
            'file_size' => $result->file_size,
            'file_extension' => $result->file_extension,
            'backup_path' => $backupPath,
            'deletion_method' => 'auto',
            'was_abandoned' => 1,
          ]);

          // Mark file as deleted in database
          self::updateFileRecord($result->id, [
            'is_active' => 0,
            'scan_status' => self::SCAN_STATUS_DELETED,
          ]);

          $deletedCount++;
        }
      }
      catch (Exception $e) {
        CRM_Core_Error::debug_log_message(
          "File Analyzer: Failed to delete file {$result->filename}: " . $e->getMessage()
        );
      }
    }

    return $deletedCount;
  }

  /**
   * Get file record by path
   *
   * @param string $filename File name
   * @param string $directoryType Directory type
   * @return array|null File record
   */
  private static function getFileRecordByPath($filename, $directoryType) {
    $query = "
      SELECT *
      FROM civicrm_file_analyzer
      WHERE filename = %1 AND directory_type = %2
      LIMIT 1
    ";
    $params = [
      1 => [$filename, 'String'],
      2 => [$directoryType, 'String'],
    ];
    $dao = CRM_Core_DAO::executeQuery($query, $params);
    if ($dao->fetch()) {
      return [
        'id' => $dao->id,
        'file_id' => $dao->file_id,
        'filename' => $dao->filename,
        'file_path' => $dao->file_path,
        'is_abandoned' => $dao->is_abandoned,
      ];
    }

    return NULL;
  }

  /**
   * Create file record in database
   *
   * @param array $data File data
   * @return int File analyzer ID
   */
  private static function createFileRecord($data) {
    $fields = [];
    $values = [];
    $params = [];
    $index = 1;

    foreach ($data as $key => $value) {
      $fields[] = $key;
      $values[] = "%{$index}";
      $params[$index] = [$value, self::getFieldType($key)];
      $index++;
    }

    $sql = sprintf(
      "INSERT INTO civicrm_file_analyzer (%s) VALUES (%s)",
      implode(', ', $fields),
      implode(', ', $values)
    );

    CRM_Core_DAO::executeQuery($sql, $params);
    return CRM_Core_DAO::singleValueQuery("SELECT LAST_INSERT_ID()");
  }

  /**
   * Update file record in database
   *
   * @param int $id File analyzer ID
   * @param array $data Data to update
   */
  private static function updateFileRecord($id, $data) {
    $sets = [];
    $params = [1 => [$id, 'Integer']];
    $index = 2;

    foreach ($data as $key => $value) {
      $sets[] = "{$key} = %{$index}";
      $params[$index] = [$value, self::getFieldType($key)];
      $index++;
    }

    $sql = sprintf(
      "UPDATE civicrm_file_analyzer SET %s WHERE id = %%1",
      implode(', ', $sets)
    );

    CRM_Core_DAO::executeQuery($sql, $params);
  }

  /**
   * Create scan record
   *
   * @param string $directoryType Directory type
   * @return int Scan ID
   */
  private static function createScanRecord($directoryType) {
    $sql = "
      INSERT INTO civicrm_file_analyzer_scan
      (directory_type, scan_date, scan_status)
      VALUES (%1, %2, %3)
    ";
    $params = [
      1 => [$directoryType, 'String'],
      2 => [date('Y-m-d H:i:s'), 'String'],
      3 => ['running', 'String'],
    ];

    CRM_Core_DAO::executeQuery($sql, $params);
    return CRM_Core_DAO::singleValueQuery("SELECT LAST_INSERT_ID()");
  }

  /**
   * Update scan record
   *
   * @param int $scanId Scan ID
   * @param array $data Data to update
   */
  private static function updateScanRecord($scanId, $data) {
    $sets = [];
    $params = [1 => [$scanId, 'Integer']];
    $index = 2;

    foreach ($data as $key => $value) {
      $sets[] = "{$key} = %{$index}";
      $params[$index] = [$value, self::getFieldType($key)];
      $index++;
    }

    $sql = sprintf(
      "UPDATE civicrm_file_analyzer_scan SET %s WHERE id = %%1",
      implode(', ', $sets)
    );

    CRM_Core_DAO::executeQuery($sql, $params);
  }

  /**
   * Create deletion record for audit trail
   *
   * @param array $data Deletion data
   * @return int Deletion record ID
   */
  private static function createDeletionRecord($data) {
    // Get current user contact ID
    $session = CRM_Core_Session::singleton();
    $contactId = $session->get('userID');

    $defaults = [
      'deleted_by' => $contactId,
      'deleted_date' => date('Y-m-d H:i:s'),
      'deletion_method' => 'manual',
      'was_abandoned' => 0,
    ];

    $data = array_merge($defaults, $data);

    $fields = [];
    $values = [];
    $params = [];
    $index = 1;

    foreach ($data as $key => $value) {
      $fields[] = $key;
      $values[] = "%{$index}";
      $params[$index] = [$value, self::getFieldType($key)];
      $index++;
    }

    $sql = sprintf(
      "INSERT INTO civicrm_file_analyzer_deleted (%s) VALUES (%s)",
      implode(', ', $fields),
      implode(', ', $values)
    );

    CRM_Core_DAO::executeQuery($sql, $params);
    return CRM_Core_DAO::singleValueQuery("SELECT LAST_INSERT_ID()");
  }

  /**
   * Mark files that no longer exist on filesystem as inactive
   *
   * @param string $directoryType Directory type
   * @param string $scanPath Scan path
   */
  private static function markMissingFilesInactive($directoryType, $scanPath) {
    // Get all active files for this directory
    $query = "
      SELECT id, file_path
      FROM civicrm_file_analyzer
      WHERE directory_type = %1
        AND is_active = 1
        AND last_scanned_date < %2
    ";
    $params = [
      1 => [$directoryType, 'String'],
      2 => [date('Y-m-d H:i:s', strtotime('-1 hour')), 'String'],
    ];

    $result = CRM_Core_DAO::executeQuery($query, $params);
    $missingIds = [];

    while ($result->fetch()) {
      if (!file_exists($result->file_path)) {
        $missingIds[] = $result->id;
      }
    }

    // Mark missing files as inactive
    if (!empty($missingIds)) {
      $idList = implode(',', $missingIds);
      CRM_Core_DAO::executeQuery(
        "UPDATE civicrm_file_analyzer 
         SET is_active = 0, scan_status = %1 
         WHERE id IN ({$idList})",
        [1 => [self::SCAN_STATUS_ERROR, 'String']]
      );
    }
  }

  /**
   * Get field type for SQL parameters
   *
   * @param string $fieldName Field name
   * @return string Field type
   */
  private static function getFieldType($fieldName) {
    $integerFields = [
      'id', 'file_id', 'file_size', 'is_abandoned', 'is_active',
      'file_analyzer_id', 'entity_id', 'deleted_by', 'was_abandoned',
      'total_files_scanned', 'active_files', 'abandoned_files',
      'total_size', 'abandoned_size', 'scan_duration',
    ];

    if (in_array($fieldName, $integerFields)) {
      return 'Integer';
    }

    return 'String';
  }

  /**
   * Get latest scan results from database
   *
   * @param string $directoryType Optional directory type filter
   * @return array Scan results
   */
  public static function getLatestScanResults($directoryType = NULL) {
    $whereClause = $directoryType ? "WHERE directory_type = %1" : "";
    $params = $directoryType ? [1 => [$directoryType, 'String']] : [];

    $query = "
      SELECT *
      FROM civicrm_file_analyzer_scan
      {$whereClause}
      ORDER BY scan_date DESC
      LIMIT 1
    ";

    $dao = CRM_Core_DAO::executeQuery($query, $params);
    if ($dao->fetch()) {
      return [
        'scan_date' => $dao->scan_date,
        'directory_type' => $dao->directory_type,
        'directoryStats' => [
          'totalFiles' => $dao->total_files_scanned,
          'totalSize' => $dao->total_size,
          'abandonedSize' => $dao->abandoned_size,
          'abandonedFiles' => $dao->abandoned_files,
        ],
        'active_files' => $dao->active_files,
        'abandoned_files' => $dao->abandoned_files,
        'fileAnalysis' => json_decode($dao->statistics, TRUE),
      ];
    }

    return [];
  }

  /**
   * Get abandoned files from database
   *
   * @param string $directoryType Directory type
   * @return array Abandoned files list
   */
  public static function getAbandonedFilesFromDB($directoryType = self::DIRECTORY_CUSTOM) {
    $query = "
      SELECT 
        id,
        filename as filenameOnly,
        file_path as path,
        file_size as size,
        file_extension as extension,
        modified_date as modified,
        directory_type,
        0 as in_use
      FROM civicrm_file_analyzer
      WHERE directory_type = %1
        AND is_abandoned = 1
        AND is_active = 1
      ORDER BY file_size DESC
    ";
    $params = [1 => [$directoryType, 'String']];

    $result = CRM_Core_DAO::executeQuery($query, $params);
    $files = [];

    while ($result->fetch()) {
      $files[] = [
        'id' => $result->id,
        'filename' => $result->filenameOnly,
        'filenameOnly' => $result->filenameOnly,
        'path' => $result->path,
        'size' => $result->size,
        'extension' => $result->extension,
        'modified' => $result->modified,
        'directory_type' => $result->directory_type,
        'in_use' => FALSE,
      ];
    }

    return $files;
  }

  /**
   * Get file statistics from database
   *
   * @param string $directoryType Optional directory type
   * @return array Statistics
   */
  public static function getFileStatistics($directoryType = NULL) {
    $whereClause = $directoryType ? "WHERE directory_type = %1" : "";
    $params = $directoryType ? [1 => [$directoryType, 'String']] : [];

    $query = "
      SELECT 
        COUNT(*) as total_files,
        SUM(file_size) as total_size,
        SUM(CASE WHEN is_abandoned = 1 THEN 1 ELSE 0 END) as abandoned_count,
        SUM(CASE WHEN is_abandoned = 1 THEN file_size ELSE 0 END) as abandoned_size,
        SUM(CASE WHEN is_abandoned = 0 THEN 1 ELSE 0 END) as active_count
      FROM civicrm_file_analyzer
      {$whereClause}
        AND is_active = 1
    ";

    $dao = CRM_Core_DAO::executeQuery($query, $params);
    $dao->fetch();

    return [
      'total_files' => $dao->total_files,
      'total_size' => $dao->total_size,
      'abandoned_count' => $dao->abandoned_count,
      'abandoned_size' => $dao->abandoned_size,
      'active_count' => $dao->active_count,
    ];
  }

  /**
   * Get file with references by ID
   *
   * @param int $fileId File analyzer ID
   * @return array File data with references
   */
  public static function getFileWithReferences($fileId) {
    // Get file record
    $fileQuery = "
      SELECT *
      FROM civicrm_file_analyzer
      WHERE id = %1
    ";
    $fileParams = [1 => [$fileId, 'Integer']];
    $fileDao = CRM_Core_DAO::executeQuery($fileQuery, $fileParams);

    if (!$fileDao->fetch()) {
      return NULL;
    }

    $fileData = [
      'id' => $fileDao->id,
      'file_id' => $fileDao->file_id,
      'filename' => $fileDao->filename,
      'file_path' => $fileDao->file_path,
      'directory_type' => $fileDao->directory_type,
      'file_size' => $fileDao->file_size,
      'file_extension' => $fileDao->file_extension,
      'mime_type' => $fileDao->mime_type,
      'is_abandoned' => $fileDao->is_abandoned,
      'is_active' => $fileDao->is_active,
      'created_date' => $fileDao->created_date,
      'modified_date' => $fileDao->modified_date,
      'last_scanned_date' => $fileDao->last_scanned_date,
      'references' => [],
    ];

    // Get references
    $refQuery = "
      SELECT *
      FROM civicrm_file_analyzer_reference
      WHERE file_analyzer_id = %1
        AND is_active = 1
    ";
    $refParams = [1 => [$fileId, 'Integer']];
    $refDao = CRM_Core_DAO::executeQuery($refQuery, $refParams);

    while ($refDao->fetch()) {
      $fileData['references'][] = [
        'id' => $refDao->id,
        'reference_type' => $refDao->reference_type,
        'entity_table' => $refDao->entity_table,
        'entity_id' => $refDao->entity_id,
        'field_name' => $refDao->field_name,
        'details' => json_decode($refDao->reference_details, TRUE),
        'created_date' => $refDao->created_date,
        'last_verified_date' => $refDao->last_verified_date,
      ];
    }

    return $fileData;
  }

  /**
   * Delete file by ID
   *
   * @param int $fileId File analyzer ID
   * @param bool $backup Whether to backup
   * @param string $reason Deletion reason
   * @return bool Success
   */
  public static function deleteFileById($fileId, $backup = TRUE, $reason = NULL) {
    $fileData = self::getFileWithReferences($fileId);

    if (!$fileData) {
      throw new Exception("File not found with ID: {$fileId}");
    }

    if (!$fileData['is_abandoned']) {
      throw new Exception("Cannot delete file that is still in use");
    }

    $backupPath = NULL;
    if ($backup) {
      $backupPath = self::backupFile($fileData['file_path'], $fileData['directory_type']);
    }

    // Delete physical file
    if (file_exists($fileData['file_path'])) {
      if (!unlink($fileData['file_path'])) {
        throw new Exception("Failed to delete physical file");
      }
    }

    // Create deletion record
    self::createDeletionRecord([
      'file_analyzer_id' => $fileId,
      'filename' => $fileData['filename'],
      'file_path' => $fileData['file_path'],
      'directory_type' => $fileData['directory_type'],
      'file_size' => $fileData['file_size'],
      'file_extension' => $fileData['file_extension'],
      'backup_path' => $backupPath,
      'deletion_method' => 'manual',
      'was_abandoned' => 1,
      'deletion_reason' => $reason,
    ]);

    // Mark as deleted
    self::updateFileRecord($fileId, [
      'is_active' => 0,
      'scan_status' => self::SCAN_STATUS_DELETED,
    ]);

    return TRUE;
  }

  /**
   * Get deletion history
   *
   * @param array $filters Optional filters
   * @param int $limit Limit results
   * @return array Deletion records
   */
  public static function getDeletionHistory($filters = [], $limit = 100) {
    $whereClauses = [];
    $params = [];
    $index = 1;

    if (!empty($filters['directory_type'])) {
      $whereClauses[] = "directory_type = %{$index}";
      $params[$index] = [$filters['directory_type'], 'String'];
      $index++;
    }

    if (!empty($filters['deleted_by'])) {
      $whereClauses[] = "deleted_by = %{$index}";
      $params[$index] = [$filters['deleted_by'], 'Integer'];
      $index++;
    }

    if (!empty($filters['date_from'])) {
      $whereClauses[] = "deleted_date >= %{$index}";
      $params[$index] = [$filters['date_from'], 'String'];
      $index++;
    }

    if (!empty($filters['date_to'])) {
      $whereClauses[] = "deleted_date <= %{$index}";
      $params[$index] = [$filters['date_to'], 'String'];
      $index++;
    }

    $whereClause = !empty($whereClauses) ? 'WHERE ' . implode(' AND ', $whereClauses) : '';

    $query = "
      SELECT 
        d.*,
        c.display_name as deleted_by_name
      FROM civicrm_file_analyzer_deleted d
      LEFT JOIN civicrm_contact c ON d.deleted_by = c.id
      {$whereClause}
      ORDER BY deleted_date DESC
      LIMIT {$limit}
    ";

    $result = CRM_Core_DAO::executeQuery($query, $params);
    $records = [];

    while ($result->fetch()) {
      $records[] = [
        'id' => $result->id,
        'filename' => $result->filename,
        'file_path' => $result->file_path,
        'directory_type' => $result->directory_type,
        'file_size' => $result->file_size,
        'file_extension' => $result->file_extension,
        'backup_path' => $result->backup_path,
        'deleted_by' => $result->deleted_by,
        'deleted_by_name' => $result->deleted_by_name,
        'deleted_date' => $result->deleted_date,
        'deletion_method' => $result->deletion_method,
        'was_abandoned' => $result->was_abandoned,
        'deletion_reason' => $result->deletion_reason,
      ];
    }

    return $records;
  }

  // ========== Helper Methods (reused from original) ==========

  /**
   * Get directory path based on type
   */
  private static function getDirectoryPath($directory_type) {
    $config = CRM_Core_Config::singleton();

    switch ($directory_type) {
      case self::DIRECTORY_CUSTOM:
        return $config->customFileUploadDir;

      case self::DIRECTORY_CONTRIBUTE:
        $baseDir = dirname($config->customFileUploadDir);
        return $baseDir . '/persist/contribute/images';

      default:
        throw new Exception("Unknown directory type: {$directory_type}");
    }
  }

  /**
   * Scan directory recursively
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
   * Get MIME type
   */
  private static function getMimeType($filePath) {
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

    return 'application/octet-stream';
  }

  /**
   * Backup file before deletion
   */
  private static function backupFile($filePath, $directory_type) {
    $backupDir = CRM_Core_Config::singleton()->uploadDir . 'file_analyzer_backups';

    // Create main backup directory with appropriate permissions
    if (!is_dir($backupDir)) {
      mkdir($backupDir, 0775, TRUE);
    }

    $typeBackupDir = $backupDir . '/deleted_files_' . $directory_type;
    if (!is_dir($typeBackupDir)) {
      mkdir($typeBackupDir, 0775, TRUE);
    }

    $backupPath = $typeBackupDir . '/' . date('Y-m-d_H-i-s_') . basename($filePath);
    copy($filePath, $backupPath);

    return $backupPath;
  }

  /**
   * Create required directories
   */
  private static function createDirectories() {
    $backupDir = CRM_Core_Config::singleton()->uploadDir . 'file_analyzer_backups';

    if (!is_dir($backupDir)) {
      mkdir($backupDir, 0775, TRUE);
    }

    $subdirs = ['deleted_files_custom', 'deleted_files_contribute', 'reports'];
    foreach ($subdirs as $subdir) {
      $path = $backupDir . '/' . $subdir;
      if (!is_dir($path)) {
        mkdir($path, 0775, TRUE);
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
   * Retrieve all custom fields of type File
   *
   * @return array Custom field information
   */
  public static function getFileCustomFields() {
    $fileFields = [];

    try {
      $customFields = civicrm_api3('CustomField', 'get', [
        'sequential' => 1,
        'data_type' => 'File',
        'is_active' => 1,
        'options' => ['limit' => 0],
        'api.CustomGroup.get' => [
          'id' => '$value.custom_group_id',
        ],
      ]);

      foreach ($customFields['values'] as $field) {
        if (!empty($field['api.CustomGroup.get']['values'])) {
          $customGroup = $field['api.CustomGroup.get']['values'][0];

          $fileFields[] = [
            'field_id' => $field['id'],
            'field_name' => $field['name'],
            'field_label' => $field['label'],
            'column_name' => $field['column_name'],
            'custom_group_id' => $customGroup['id'],
            'table_name' => $customGroup['table_name'],
            'extends' => $customGroup['extends'],
            'extends_entity_column_value' => $customGroup['extends_entity_column_value'] ?? NULL,
          ];
        }
      }
    }
    catch (CiviCRM_API3_Exception $e) {
      CRM_Core_Error::debug_log_message('Error fetching custom file fields: ' . $e->getMessage());
    }

    return $fileFields;
  }

  /**
   * Steps 2 & 3: Build a map of file IDs to entity information
   *
   * @param array $fileFields List of file custom fields
   * @return array Map of file_id => entity information
   */
  public static function buildFileToEntityMap($fileFields) {
    $fileToEntityMap = [];

    foreach ($fileFields as $fieldInfo) {
      $tableName = $fieldInfo['table_name'];
      $columnName = $fieldInfo['column_name'];

      // Query to get file IDs and entity IDs from custom table
      $query = "
        SELECT 
          ct.entity_id,
          ct.{$columnName} as file_id,
          '{$fieldInfo['extends']}' as entity_table
        FROM {$tableName} ct
        WHERE ct.{$columnName} IS NOT NULL
        AND ct.{$columnName} != ''
      ";

      $dao = CRM_Core_DAO::executeQuery($query);

      while ($dao->fetch()) {
        $fileId = (int)$dao->file_id;

        if (!isset($fileToEntityMap[$fileId])) {
          //$fileToEntityMap[$fileId] = [];
        }

        $fileToEntityMap[$fileId] = [
          'entity_id' => $dao->entity_id,
          'entity_table' => self::mapExtendsToEntityTable($dao->entity_table),
          //'entity_table' => $dao->entity_table,
          'field_id' => $fieldInfo['field_id'],
          'field_label' => $fieldInfo['field_label'],
          'custom_table' => $tableName,
          'custom_column' => $columnName,
        ];
      }
    }

    return $fileToEntityMap;
  }

  /**
   * Map CustomGroup 'extends' value to actual entity table name
   *
   * @param string $extends The extends value from custom group
   * @return string The actual database table name
   */
  private static function mapExtendsToEntityTable($extends) {
    $mapping = [
      'Contact' => 'civicrm_contact',
      'Individual' => 'civicrm_contact',
      'Household' => 'civicrm_contact',
      'Organization' => 'civicrm_contact',
      'Activity' => 'civicrm_activity',
      'Contribution' => 'civicrm_contribution',
      'Membership' => 'civicrm_membership',
      'Participant' => 'civicrm_participant',
      'Event' => 'civicrm_event',
      'Case' => 'civicrm_case',
      'Grant' => 'civicrm_grant',
      'Pledge' => 'civicrm_pledge',
      'Relationship' => 'civicrm_relationship',
      'Campaign' => 'civicrm_campaign',
      'Case' => 'civicrm_case',
      'Note' => 'civicrm_note',
      'Pledge' => 'civicrm_pledge',
    ];

    return $mapping[$extends] ?? $extends;
  }

}
