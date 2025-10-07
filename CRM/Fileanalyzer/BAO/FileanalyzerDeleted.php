<?php

use CRM_Fileanalyzer_ExtensionUtil as E;

/**
 * BAO class for FileAnalyzerDeleted entity
 */
class CRM_Fileanalyzer_BAO_FileanalyzerDeleted extends CRM_Fileanalyzer_DAO_FileanalyzerDeleted {

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
   * Get deletion method options
   *
   * @return array
   *   Array of deletion methods with value => label pairs
   */
  public static function getDeletionMethods() {
    return [
      'manual' => ts('Manual'),
      'auto' => ts('Automatic'),
      'bulk' => ts('Bulk'),
      'scheduled' => ts('Scheduled'),
      'cleanup' => ts('Cleanup'),
    ];
  }

  /**
   * Create a new FileAnalyzerDeleted record
   *
   * @param array $params
   *   Array of parameters for the deleted file record
   *
   * @return CRM_Fileanalyzer_BAO_FileanalyzerDeleted
   * @throws CRM_Core_Exception
   */
  public static function create($params) {
    $hook = empty($params['id']) ? 'create' : 'edit';
    CRM_Utils_Hook::pre($hook, 'FileanalyzerDeleted', CRM_Utils_Array::value('id', $params), $params);

    // Set deleted_date if not provided and this is a new record
    if (empty($params['id']) && empty($params['deleted_date'])) {
      $params['deleted_date'] = date('YmdHis');
    }

    // Set deleted_by to current user if not provided
    if (empty($params['id']) && empty($params['deleted_by'])) {
      $session = CRM_Core_Session::singleton();
      $params['deleted_by'] = $session->get('userID');
    }

    $instance = new self();
    $instance->copyValues($params);
    $instance->save();

    CRM_Utils_Hook::post($hook, 'FileanalyzerDeleted', $instance->id,
      $instance);

    return $instance;
  }

  /**
   * Record a deleted file
   *
   * @param array $fileData
   *   File data from FileAnalyzer record
   * @param array $deletionInfo
   *   Additional deletion information
   *
   * @return CRM_Fileanalyzer_BAO_FileAnalyzerDeleted
   */
  public static function recordDeletion($fileData, $deletionInfo = []) {
    $params = [
      'file_analyzer_id' => $fileData['id'] ?? NULL,
      'filename' => $fileData['filename'],
      'file_path' => $fileData['file_path'],
      'directory_type' => $fileData['directory_type'],
      'file_size' => $fileData['file_size'] ?? 0,
      'file_extension' => $fileData['file_extension'] ?? NULL,
      'was_abandoned' => $fileData['is_abandoned'] ?? 0,
      'deletion_method' => $deletionInfo['deletion_method'] ?? 'manual',
      'deletion_reason' => $deletionInfo['deletion_reason'] ?? NULL,
      'backup_path' => $deletionInfo['backup_path'] ?? NULL,
      'deleted_by' => $deletionInfo['deleted_by'] ?? NULL,
    ];

    return self::create($params);
  }

  /**
   * Get deleted files
   *
   * @param array $params
   *   Optional filters (directory_type, deleted_by, date_range, etc.)
   *
   * @return array
   *   Array of deleted file records
   */
  public static function getDeletedFiles($params = []) {
    $deleted = new self();

    if (!empty($params['directory_type'])) {
      $deleted->directory_type = $params['directory_type'];
    }

    if (!empty($params['deleted_by'])) {
      $deleted->deleted_by = $params['deleted_by'];
    }

    if (!empty($params['deletion_method'])) {
      $deleted->deletion_method = $params['deletion_method'];
    }

    if (!empty($params['was_abandoned'])) {
      $deleted->was_abandoned = $params['was_abandoned'];
    }

    $deleted->orderBy('deleted_date DESC');

    if (!empty($params['limit'])) {
      $deleted->limit($params['limit']);
    }

    $deleted->find();

    $results = [];
    while ($deleted->fetch()) {
      $results[] = clone $deleted;
    }

    return $results;
  }

  /**
   * Get deletion statistics
   *
   * @param array $filters
   *   Optional filters (directory_type, date_range, etc.)
   *
   * @return array
   *   Statistics array
   */
  public static function getDeletionStatistics($filters = []) {
    $query = "
      SELECT 
        COUNT(*) as total_deleted,
        SUM(CASE WHEN was_abandoned = 1 THEN 1 ELSE 0 END) as abandoned_deleted,
        SUM(CASE WHEN was_abandoned = 0 THEN 1 ELSE 0 END) as active_deleted,
        SUM(file_size) as total_size_deleted,
        SUM(CASE WHEN was_abandoned = 1 THEN file_size ELSE 0 END) as abandoned_size_deleted
      FROM civicrm_file_analyzer_deleted
      WHERE 1=1
    ";

    $params = [];
    $paramIndex = 1;

    if (!empty($filters['directory_type'])) {
      $query .= " AND directory_type = %{$paramIndex}";
      $params[$paramIndex] = [$filters['directory_type'], 'String'];
      $paramIndex++;
    }

    if (!empty($filters['date_from'])) {
      $query .= " AND deleted_date >= %{$paramIndex}";
      $params[$paramIndex] = [$filters['date_from'], 'String'];
      $paramIndex++;
    }

    if (!empty($filters['date_to'])) {
      $query .= " AND deleted_date <= %{$paramIndex}";
      $params[$paramIndex] = [$filters['date_to'], 'String'];
      $paramIndex++;
    }

    $dao = CRM_Core_DAO::executeQuery($query, $params);

    if ($dao->fetch()) {
      return [
        'total_deleted' => (int)$dao->total_deleted,
        'abandoned_deleted' => (int)$dao->abandoned_deleted,
        'active_deleted' => (int)$dao->active_deleted,
        'total_size_deleted' => (int)$dao->total_size_deleted,
        'abandoned_size_deleted' => (int)$dao->abandoned_size_deleted,
      ];
    }

    return [];
  }

  /**
   * Get deletion statistics by user
   *
   * @param int $limit
   *   Limit number of users returned
   *
   * @return array
   *   Array of user statistics
   */
  public static function getStatisticsByUser($limit = 10) {
    $query = "
      SELECT 
        d.deleted_by,
        c.display_name,
        COUNT(*) as total_deletions,
        SUM(d.file_size) as total_size_deleted
      FROM civicrm_file_analyzer_deleted d
      LEFT JOIN civicrm_contact c ON d.deleted_by = c.id
      WHERE d.deleted_by IS NOT NULL
      GROUP BY d.deleted_by, c.display_name
      ORDER BY total_deletions DESC
      LIMIT %1
    ";

    $dao = CRM_Core_DAO::executeQuery($query, [
      1 => [$limit, 'Integer'],
    ]);

    $results = [];
    while ($dao->fetch()) {
      $results[] = [
        'contact_id' => $dao->deleted_by,
        'display_name' => $dao->display_name,
        'total_deletions' => (int)$dao->total_deletions,
        'total_size_deleted' => (int)$dao->total_size_deleted,
      ];
    }

    return $results;
  }

  /**
   * Get deletion statistics by method
   *
   * @param string $directoryType
   *   Optional directory type filter
   *
   * @return array
   *   Array of method statistics
   */
  public static function getStatisticsByMethod($directoryType = NULL) {
    $query = "
      SELECT 
        deletion_method,
        COUNT(*) as count,
        SUM(file_size) as total_size
      FROM civicrm_file_analyzer_deleted
      WHERE 1=1
    ";

    $params = [];
    if ($directoryType) {
      $query .= " AND directory_type = %1";
      $params[1] = [$directoryType, 'String'];
    }

    $query .= " GROUP BY deletion_method ORDER BY count DESC";

    $dao = CRM_Core_DAO::executeQuery($query, $params);

    $results = [];
    while ($dao->fetch()) {
      $results[$dao->deletion_method] = [
        'count' => (int)$dao->count,
        'total_size' => (int)$dao->total_size,
      ];
    }

    return $results;
  }

  /**
   * Get files deleted within a date range
   *
   * @param string $startDate
   *   Start date (Y-m-d format)
   * @param string $endDate
   *   End date (Y-m-d format)
   * @param string $directoryType
   *   Optional directory type filter
   *
   * @return array
   *   Array of deleted file records
   */
  public static function getFilesByDateRange($startDate, $endDate, $directoryType = NULL) {
    $deleted = new self();

    if ($directoryType) {
      $deleted->directory_type = $directoryType;
    }

    $deleted->whereAdd("deleted_date >= '{$startDate} 00:00:00'");
    $deleted->whereAdd("deleted_date <= '{$endDate} 23:59:59'");
    $deleted->orderBy('deleted_date DESC');
    $deleted->find();

    $results = [];
    while ($deleted->fetch()) {
      $results[] = clone $deleted;
    }

    return $results;
  }

  /**
   * Check if a file has a backup
   *
   * @param int $id
   *   Deleted file record ID
   *
   * @return bool
   */
  public static function hasBackup($id) {
    $deleted = new self();
    $deleted->id = $id;

    if ($deleted->find(TRUE)) {
      return !empty($deleted->backup_path) && file_exists($deleted->backup_path);
    }

    return FALSE;
  }

  /**
   * Get files with backups
   *
   * @param string $directoryType
   *   Optional directory type filter
   *
   * @return array
   *   Array of deleted file records with backups
   */
  public static function getFilesWithBackups($directoryType = NULL) {
    $deleted = new self();
    $deleted->whereAdd("backup_path IS NOT NULL");

    if ($directoryType) {
      $deleted->directory_type = $directoryType;
    }

    $deleted->orderBy('deleted_date DESC');
    $deleted->find();

    $results = [];
    while ($deleted->fetch()) {
      $results[] = clone $deleted;
    }

    return $results;
  }

  /**
   * Get recent deletions
   *
   * @param int $limit
   *   Number of records to return
   * @param int $days
   *   Number of days to look back
   *
   * @return array
   *   Array of recent deleted file records
   */
  public static function getRecentDeletions($limit = 20, $days = 7) {
    $dateThreshold = date('Y-m-d H:i:s', strtotime("-{$days} days"));

    $deleted = new self();
    $deleted->whereAdd("deleted_date >= '{$dateThreshold}'");
    $deleted->orderBy('deleted_date DESC');
    $deleted->limit($limit);
    $deleted->find();

    $results = [];
    while ($deleted->fetch()) {
      $results[] = clone $deleted;
    }

    return $results;
  }

  /**
   * Purge old deletion records
   *
   * @param int $daysToKeep
   *   Number of days of records to keep
   * @param bool $withBackupsOnly
   *   If TRUE, only purge records with backups
   *
   * @return int
   *   Number of records purged
   */
  public static function purgeOldRecords($daysToKeep = 90, $withBackupsOnly = FALSE) {
    $dateThreshold = date('Y-m-d H:i:s', strtotime("-{$daysToKeep} days"));

    $query = "DELETE FROM civicrm_file_analyzer_deleted WHERE deleted_date < %1";
    $params = [1 => [$dateThreshold, 'String']];

    if ($withBackupsOnly) {
      $query .= " AND backup_path IS NOT NULL";
    }

    $dao = CRM_Core_DAO::executeQuery($query, $params);
    return $dao->affectedRows();
  }

  /**
   * Delete record
   *
   * @param int $id
   *   Record ID to delete
   *
   * @return bool
   */
  public static function del($id) {
    $deleted = new self();
    $deleted->id = $id;

    if ($deleted->find(TRUE)) {
      CRM_Utils_Hook::pre('delete', 'FileanalyzerDeleted', $id, CRM_Core_DAO::$_nullArray);
      $result = $deleted->delete();
      CRM_Utils_Hook::post('delete', 'FileanalyzerDeleted', $id, $deleted);
      return (bool)$result;
    }

    return FALSE;
  }

}