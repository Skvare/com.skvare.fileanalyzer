<?php

use CRM_Fileanalyzer_ExtensionUtil as E;


/**
 * BAO class for Fileanalyzer entity
 */
class CRM_Fileanalyzer_BAO_Fileanalyzer extends CRM_Fileanalyzer_DAO_Fileanalyzer {

  /**
   * Get available directory types
   *
   * @return array
   *   Array of directory types with value => label pairs
   */
  public static function getDirectoryTypes() {
    return [
      'custom' => ts('Custom Files'),
      'contribute' => ts('Public Files'),
    ];
  }

  /**
   * Get scan status options
   *
   * @return array
   *   Array of scan statuses with value => label pairs
   */
  public static function getScanStatuses() {
    return [
      'pending' => ts('Pending'),
      'scanned' => ts('Scanned'),
      'deleted' => ts('Deleted'),
      'error' => ts('Error'),
      'skipped' => ts('Skipped'),
    ];
  }

  /**
   * Create a new FileAnalyzer record
   *
   * @param array $params
   *   Array of parameters for the file analyzer record
   *
   * @return CRM_Fileanalyzer_BAO_Fileanalyzer
   * @throws CRM_Core_Exception
   */
  public static function create($params) {
    $hook = empty($params['id']) ? 'create' : 'edit';
    CRM_Utils_Hook::pre($hook, 'FileAnalyzer', CRM_Utils_Array::value('id', $params), $params);

    $instance = new self();
    $instance->copyValues($params);
    $instance->save();

    CRM_Utils_Hook::post($hook, 'FileAnalyzer', $instance->id, $instance);

    return $instance;
  }

  /**
   * Get file analyzer record by file path
   *
   * @param string $filePath
   * @param string $directoryType
   *
   * @return CRM_Fileanalyzer_BAO_Fileanalyzer|NULL
   */
  public static function getByFilePath($filePath, $directoryType) {
    $fileAnalyzer = new self();
    $fileAnalyzer->file_path = $filePath;
    $fileAnalyzer->directory_type = $directoryType;

    if ($fileAnalyzer->find(TRUE)) {
      return $fileAnalyzer;
    }

    return NULL;
  }

  /**
   * Get abandoned files
   *
   * @param array $params
   *   Optional filters (directory_type, limit, etc.)
   *
   * @return array
   *   Array of abandoned file records
   */
  public static function getAbandonedFiles($params = []) {
    $fileAnalyzer = new self();
    $fileAnalyzer->is_abandoned = 1;
    $fileAnalyzer->is_active = 1;

    if (!empty($params['directory_type'])) {
      $fileAnalyzer->directory_type = $params['directory_type'];
    }

    if (!empty($params['limit'])) {
      $fileAnalyzer->limit($params['limit']);
    }

    $fileAnalyzer->find();

    $results = [];
    while ($fileAnalyzer->fetch()) {
      $results[] = clone $fileAnalyzer;
    }

    return $results;
  }

  /**
   * Calculate total size of abandoned files
   *
   * @param string $directoryType
   *   Optional directory type filter
   *
   * @return int
   *   Total size in bytes
   */
  public static function getAbandonedFilesSize($directoryType = NULL) {
    $query = "
      SELECT SUM(file_size) as total_size
      FROM civicrm_file_analyzer
      WHERE is_abandoned = 1 AND is_active = 1
    ";

    $params = [];
    if ($directoryType) {
      $query .= " AND directory_type = %1";
      $params[1] = [$directoryType, 'String'];
    }

    $result = CRM_Core_DAO::singleValueQuery($query, $params);
    return (int)$result;
  }

  /**
   * Mark file as abandoned
   *
   * @param int $id
   *   File analyzer record ID
   *
   * @return bool
   */
  public static function markAsAbandoned($id) {
    $fileAnalyzer = new self();
    $fileAnalyzer->id = $id;

    if ($fileAnalyzer->find(TRUE)) {
      $fileAnalyzer->is_abandoned = 1;
      $fileAnalyzer->save();
      return TRUE;
    }

    return FALSE;
  }

  /**
   * Mark file as active (not abandoned)
   *
   * @param int $id
   *   File analyzer record ID
   *
   * @return bool
   */
  public static function markAsActive($id) {
    $fileAnalyzer = new self();
    $fileAnalyzer->id = $id;

    if ($fileAnalyzer->find(TRUE)) {
      $fileAnalyzer->is_abandoned = 0;
      $fileAnalyzer->save();
      return TRUE;
    }

    return FALSE;
  }

  /**
   * Get statistics for file analyzer
   *
   * @param string $directoryType
   *   Optional directory type filter
   *
   * @return array
   *   Statistics array
   */
  public static function getStatistics($directoryType = NULL) {
    $query = "
      SELECT 
        COUNT(*) as total_files,
        SUM(CASE WHEN is_abandoned = 1 THEN 1 ELSE 0 END) as abandoned_files,
        SUM(CASE WHEN is_abandoned = 0 THEN 1 ELSE 0 END) as active_files,
        SUM(file_size) as total_size,
        SUM(CASE WHEN is_abandoned = 1 THEN file_size ELSE 0 END) as abandoned_size,
        SUM(CASE WHEN is_abandoned = 0 THEN file_size ELSE 0 END) as active_size
      FROM civicrm_file_analyzer
      WHERE is_active = 1
    ";

    $params = [];
    if ($directoryType) {
      $query .= " AND directory_type = %1";
      $params[1] = [$directoryType, 'String'];
    }

    $dao = CRM_Core_DAO::executeQuery($query, $params);

    if ($dao->fetch()) {
      return [
        'total_files' => (int)$dao->total_files,
        'abandoned_files' => (int)$dao->abandoned_files,
        'active_files' => (int)$dao->active_files,
        'total_size' => (int)$dao->total_size,
        'abandoned_size' => (int)$dao->abandoned_size,
        'active_size' => (int)$dao->active_size,
      ];
    }

    return [];
  }

  /**
   * Delete FileAnalyzer record
   *
   * @param int $id
   *   Record ID to delete
   *
   * @return bool
   */
  public static function del($id) {
    $fileAnalyzer = new self();
    $fileAnalyzer->id = $id;

    if ($fileAnalyzer->find(TRUE)) {
      CRM_Utils_Hook::pre('delete', 'FileAnalyzer', $id, CRM_Core_DAO::$_nullArray);
      $result = $fileAnalyzer->delete();
      CRM_Utils_Hook::post('delete', 'FileAnalyzer', $id, $fileAnalyzer);
      return (bool)$result;
    }

    return FALSE;
  }

  public static function deleteFileRecord($fileID) {
    CRM_Core_Error::debug_var('fileID', $fileID);
  }

}
