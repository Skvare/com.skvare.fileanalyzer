<?php

use CRM_Fileanalyzer_ExtensionUtil as E;

/**
 * BAO class for FileanalyzerReference entity
 */
class CRM_Fileanalyzer_BAO_FileanalyzerReference extends CRM_Fileanalyzer_DAO_FileanalyzerReference {

  /**
   * Get available reference types
   *
   * @return array
   *   Array of reference types with value => label pairs
   */
  public static function getReferenceTypes() {
    return [
      'file_record' => ts('Attachment Record'),
      'contact_image' => ts('Contact Image'),
      'contribution_page' => ts('Contribution Page'),
      'message_template' => ts('Message Template'),
      'custom_field' => ts('Custom Field'),
      'activity_attachment' => ts('Activity Attachment'),
      'case_attachment' => ts('Case Attachment'),
      'mailing_attachment' => ts('Mailing Attachment'),
      'grant_attachment' => ts('Grant Attachment'),
    ];
  }

  /**
   * Create a new FileAnalyzerReference record
   *
   * @param array $params
   *   Array of parameters for the reference record
   *
   * @return CRM_Fileanalyzer_BAO_FileAnalyzerReference
   * @throws CRM_Core_Exception
   */
  public static function create($params) {
    $hook = empty($params['id']) ? 'create' : 'edit';
    CRM_Utils_Hook::pre($hook, 'FileAnalyzerReference', CRM_Utils_Array::value('id', $params), $params);

    // Set created_date if not provided and this is a new record
    if (empty($params['id']) && empty($params['created_date'])) {
      $params['created_date'] = date('YmdHis');
    }

    $instance = new self();
    $instance->copyValues($params);
    $instance->save();

    CRM_Utils_Hook::post($hook, 'FileAnalyzerReference', $instance->id, $instance);

    return $instance;
  }

  /**
   * Get references for a specific file analyzer record
   *
   * @param int $fileAnalyzerId
   *   File analyzer record ID
   *
   * @return array
   *   Array of reference records
   */
  public static function getByFileAnalyzerId($fileAnalyzerId) {
    $reference = new self();
    $reference->file_analyzer_id = $fileAnalyzerId;
    $reference->is_active = 1;
    $reference->find();

    $results = [];
    while ($reference->fetch()) {
      $results[] = clone $reference;
    }

    return $results;
  }

  /**
   * Get references by entity
   *
   * @param string $entityTable
   * @param int $entityId
   *
   * @return array
   *   Array of reference records
   */
  public static function getByEntity($entityTable, $entityId) {
    $reference = new self();
    $reference->entity_table = $entityTable;
    $reference->entity_id = $entityId;
    $reference->is_active = 1;
    $reference->find();

    $results = [];
    while ($reference->fetch()) {
      $results[] = clone $reference;
    }

    return $results;
  }

  /**
   * Check if a file has any active references
   *
   * @param int $fileAnalyzerId
   *   File analyzer record ID
   *
   * @return bool
   */
  public static function hasActiveReferences($fileAnalyzerId) {
    $reference = new self();
    $reference->file_analyzer_id = $fileAnalyzerId;
    $reference->is_active = 1;

    return (bool)$reference->count();
  }

  /**
   * Get count of references by type
   *
   * @param int $fileAnalyzerId
   *   File analyzer record ID
   *
   * @return array
   *   Array with reference_type => count pairs
   */
  public static function getCountByType($fileAnalyzerId) {
    $query = "
      SELECT reference_type, COUNT(*) as count
      FROM civicrm_file_analyzer_reference
      WHERE file_analyzer_id = %1 AND is_active = 1
      GROUP BY reference_type
    ";

    $dao = CRM_Core_DAO::executeQuery($query, [
      1 => [$fileAnalyzerId, 'Integer'],
    ]);

    $results = [];
    while ($dao->fetch()) {
      $results[$dao->reference_type] = (int)$dao->count;
    }

    return $results;
  }

  /**
   * Mark reference as inactive
   *
   * @param int $id
   *   Reference record ID
   *
   * @return bool
   */
  public static function markAsInactive($id) {
    $reference = new self();
    $reference->id = $id;

    if ($reference->find(TRUE)) {
      $reference->is_active = 0;
      $reference->save();
      return TRUE;
    }

    return FALSE;
  }

  /**
   * Verify and update reference validity
   *
   * @param int $id
   *   Reference record ID
   * @param bool $isValid
   *   Whether the reference is still valid
   *
   * @return bool
   */
  public static function verifyReference($id, $isValid = TRUE) {
    $reference = new self();
    $reference->id = $id;

    if ($reference->find(TRUE)) {
      $reference->last_verified_date = date('YmdHis');
      $reference->is_active = $isValid ? 1 : 0;
      $reference->save();
      return TRUE;
    }

    return FALSE;
  }

  /**
   * Delete reference record
   *
   * @param int $id
   *   Record ID to delete
   *
   * @return bool
   */
  public static function del($id) {
    $reference = new self();
    $reference->id = $id;

    if ($reference->find(TRUE)) {
      CRM_Utils_Hook::pre('delete', 'FileanalyzerReference', $id, CRM_Core_DAO::$_nullArray);
      $result = $reference->delete();
      CRM_Utils_Hook::post('delete', 'FileanalyzerReference', $id, $reference);
      return (bool)$result;
    }

    return FALSE;
  }

  /**
   * Delete all references for a file analyzer record
   *
   * @param int $fileAnalyzerId
   *   File analyzer record ID
   *
   * @return bool
   */
  public static function deleteByFileAnalyzerId($fileAnalyzerId) {
    $query = "DELETE FROM civicrm_file_analyzer_reference WHERE file_analyzer_id = %1";
    CRM_Core_DAO::executeQuery($query, [
      1 => [$fileAnalyzerId, 'Integer'],
    ]);

    return TRUE;
  }

  public static function tableMapping() {
    $mapping = [
      // managed files tables.
      'civicrm_contact' => 'Contact',
      'civicrm_activity' => 'Activity',
      'civicrm_contribution' => 'Contribution',
      'civicrm_membership' => 'Membership',
      'civicrm_participant' => 'Participant',
      'civicrm_event' => 'Event',
      'civicrm_case' => 'Case',
      'civicrm_grant' => 'Grant',
      'civicrm_pledge' => 'Pledge',
      'civicrm_relationship' => 'Relationship',
      'civicrm_campaign' => 'Campaign',
      'civicrm_case' => 'Case',
      'civicrm_note' => 'Note',
      'civicrm_pledge' => 'Pledge',
      // public file table.
      'civicrm_contribution_page' => 'Contribution Page',
      'civicrm_msg_template' => 'Message Template',
    ];
    return $mapping;
  }

}
