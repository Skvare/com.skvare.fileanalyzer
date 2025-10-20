<?php

use CRM_Fileanalyzer_ExtensionUtil as E;

class CRM_Fileanalyzer_Page_Preview extends CRM_Core_Page {

  public function run() {
    // Get the file analyzer record ID from the URL
    $fileId = CRM_Utils_Request::retrieve('id', 'Positive', $this, TRUE);

    try {
      // Retrieve the file details using the BAO method
      $fileDetails = CRM_Fileanalyzer_API_FileAnalysis::getFileWithReferences($fileId);
      $mimeType = $fileDetails['mime_type'];
      $fileDetails['is_image'] = self::isImage($mimeType);
      $fileDetails['is_pdf'] = self::isPDF($mimeType);
      $fileDetails['is_text'] = self::isText($mimeType);
      $fileDetails['is_video'] = self::isVideo($mimeType);
      $fileDetails['is_audio'] = self::isAudio($mimeType);

      if (!$fileDetails) {
        throw new CRM_Core_Exception(ts('File record not found.'));
      }

      // Fetch entity details based on the item_table and item_id
      $entityDetails = $this->getEntityDetails($fileDetails['item_table'], $fileDetails['item_id']);
      // Fetch contact details if contact_id is present
      $contactDetails = $fileDetails['contact_id']
        ? $this->getContactDetails($fileDetails['contact_id'])
        : NULL;
      // echo '<pre>'; print_r($entityDetails);
      // Assign variables to the template
      $this->assign('fileInfo', $fileDetails);
      $this->assign('entityDetails', $entityDetails);
      $this->assign('contactDetails', $contactDetails);

      // Set page title
      CRM_Utils_System::setTitle(ts('File Details'));
      parent::run();
    }
    catch (CRM_Core_Exception $e) {
      CRM_Core_Error::statusBounce($e->getMessage(), CRM_Utils_System::url('civicrm/file-analyzer/dashboard'));
    }
  }

  private static function isImage($mimeType) {
    return strpos($mimeType, 'image/') === 0;
  }

  private static function isPDF($mimeType) {
    return $mimeType === 'application/pdf';
  }

  private static function isText($mimeType) {
    return strpos($mimeType, 'text/') === 0 ||
      in_array($mimeType, ['application/json', 'application/xml', 'application/javascript']);
  }

  /**
   * Check if file is video
   *
   * @param string $mimeType MIME type
   * @return bool
   */
  private static function isVideo($mimeType) {
    return strpos($mimeType, 'video/') === 0;
  }

  /**
   * Check if file is audio
   *
   * @param string $mimeType MIME type
   * @return bool
   */
  private static function isAudio($mimeType) {
    return strpos($mimeType, 'audio/') === 0;
  }

  /**
   * Get details of the entity associated with this file
   *
   * @param string $entityTable Entity table name
   * @param int $entityId Entity ID
   * @return array|null Entity details
   */
  private function getEntityDetails($entityTable, $entityId) {
    if (!$entityTable || !$entityId) {
      return NULL;
    }

    try {
      switch ($entityTable) {
        case 'civicrm_contact':
          return civicrm_api3('Contact', 'getsingle', ['id' => $entityId]);

        case 'civicrm_activity':
        case 'civicrm_activity':
          // Fetch activity with detailed information
          $activity = civicrm_api3('Activity', 'getsingle', [
            'id' => $entityId,
            'return' => [
              'id', 'subject', 'activity_type_id', 'activity_date_time',
              'status_id', 'priority_id', 'details', 'campaign_id',
              'source_contact_id', 'target_contact_id', 'assignee_contact_id'
            ]
          ]);

          // Enrich activity with contact details
          $activity['contacts'] = $this->getActivityContacts($entityId);

          return $activity;
        case 'civicrm_contribution':
          return civicrm_api3('Contribution', 'getsingle', ['id' => $entityId]);

        case 'civicrm_event':
          return civicrm_api3('Event', 'getsingle', ['id' => $entityId]);

        case 'civicrm_contribution_page':
          return civicrm_api3('ContributionPage', 'getsingle', ['id' => $entityId]);

        case 'civicrm_msg_template':
          return civicrm_api3('MessageTemplate', 'getsingle', ['id' => $entityId]);

        default:
          return NULL;
      }
    }
    catch (CiviCRM_API3_Exception $e) {
      // Log error, but don't throw exception
      CRM_Core_Error::debug_log_message('Error fetching entity details: ' . $e->getMessage());
      return NULL;
    }
  }

  /**
   * Get contact details by ID
   *
   * @param int $contactId Contact ID
   * @return array|null Contact details
   */
  private function getContactDetails($contactId) {
    try {
      return civicrm_api3('Contact', 'getsingle', [
        'id' => $contactId,
        'return' => [
          'display_name', 'contact_type', 'email', 'phone',
          'image_URL', 'external_identifier'
        ]
      ]);
    }
    catch (CiviCRM_API3_Exception $e) {
      // Log error, but don't throw exception
      CRM_Core_Error::debug_log_message('Error fetching contact details: ' . $e->getMessage());
      return NULL;
    }
  }

  /**
   * Get detailed contact information for an activity
   *
   * @param int $activityId Activity ID
   * @return array Associative array of contact types and their details
   */
  private function getActivityContacts($activityId) {
    try {
      // Fetch activity contacts
      $activityContacts = civicrm_api3('ActivityContact', 'get', [
        'activity_id' => $activityId,
        'return' => ['contact_id', 'record_type_id'],
        'options' => ['limit' => 0],
      ]);

      $contactTypes = [
        1 => 'source_contact',
        2 => 'target_contact',
        3 => 'assignee_contact'
      ];

      $contactDetails = [];

      foreach ($activityContacts['values'] as $contact) {
        $recordTypeId = $contact['record_type_id'];
        $contactId = $contact['contact_id'];

        // Get full contact details
        try {
          $contactDetail = civicrm_api3('Contact', 'getsingle', [
            'id' => $contactId,
            'return' => [
              'display_name', 'contact_type', 'email', 'phone',
              'image_URL', 'external_identifier'
            ]
          ]);

          // Map contact type
          $typeName = $contactTypes[$recordTypeId] ?? 'other_contact';
          $contactDetails[$typeName][] = $contactDetail;
        }
        catch (CiviCRM_API3_Exception $e) {
          // Log error but continue processing other contacts
          CRM_Core_Error::debug_log_message("Error fetching contact details for ID {$contactId}: " . $e->getMessage());
        }
      }

      return $contactDetails;
    }
    catch (CiviCRM_API3_Exception $e) {
      CRM_Core_Error::debug_log_message('Error fetching activity contacts: ' . $e->getMessage());
      return [];
    }
  }
}