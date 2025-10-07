<?php

use CRM_Fileanalyzer_ExtensionUtil as E;

/**
 * BAO class for FileanalyzerScan entity
 */
class CRM_Fileanalyzer_BAO_FileanalyzerScan extends CRM_Fileanalyzer_DAO_FileanalyzerScan {

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
      'running' => ts('Running'),
      'completed' => ts('Completed'),
      'failed' => ts('Failed'),
      'cancelled' => ts('Cancelled'),
      'partial' => ts('Partial'),
    ];
  }

  /**
   * Create a new FileAnalyzerScan record
   *
   * @param array $params
   *   Array of parameters for the scan record
   *
   * @return CRM_Fileanalyzer_BAO_FileAnalyzerScan
   * @throws CRM_Core_Exception
   */
  public static function create($params) {
    $hook = empty($params['id']) ? 'create' : 'edit';
    CRM_Utils_Hook::pre($hook, 'FileAnalyzerScan', CRM_Utils_Array::value('id', $params), $params);

    // Set scan_date if not provided and this is a new record
    if (empty($params['id']) && empty($params['scan_date'])) {
      $params['scan_date'] = date('YmdHis');
    }

    $instance = new self();
    $instance->copyValues($params);
    $instance->save();

    CRM_Utils_Hook::post($hook, 'FileAnalyzerScan', $instance->id, $instance);

    return $instance;
  }

  /**
   * Start a new scan
   *
   * @param string $directoryType
   *   Directory type to scan
   *
   * @return CRM_Fileanalyzer_BAO_FileAnalyzerScan
   */
  public static function startScan($directoryType) {
    $params = [
      'directory_type' => $directoryType,
      'scan_date' => date('YmdHis'),
      'scan_status' => 'running',
      'total_files_scanned' => 0,
      'active_files' => 0,
      'abandoned_files' => 0,
      'total_size' => 0,
      'abandoned_size' => 0,
    ];

    return self::create($params);
  }

  /**
   * Complete a scan
   *
   * @param int $scanId
   *   Scan record ID
   * @param array $statistics
   *   Scan statistics
   * @param int $duration
   *   Scan duration in seconds
   *
   * @return bool
   */
  public static function completeScan($scanId, $statistics, $duration = NULL) {
    $scan = new self();
    $scan->id = $scanId;

    if ($scan->find(TRUE)) {
      $scan->scan_status = 'completed';
      $scan->total_files_scanned = $statistics['total_files'] ?? 0;
      $scan->active_files = $statistics['active_files'] ?? 0;
      $scan->abandoned_files = $statistics['abandoned_files'] ?? 0;
      $scan->total_size = $statistics['total_size'] ?? 0;
      $scan->abandoned_size = $statistics['abandoned_size'] ?? 0;

      if ($duration !== NULL) {
        $scan->scan_duration = $duration;
      }

      if (!empty($statistics)) {
        $scan->statistics = json_encode($statistics);
      }

      $scan->save();
      return TRUE;
    }

    return FALSE;
  }

  /**
   * Fail a scan
   *
   * @param int $scanId
   *   Scan record ID
   * @param string $errorMessage
   *   Error message
   *
   * @return bool
   */
  public static function failScan($scanId, $errorMessage) {
    $scan = new self();
    $scan->id = $scanId;

    if ($scan->find(TRUE)) {
      $scan->scan_status = 'failed';
      $scan->error_message = $errorMessage;
      $scan->save();
      return TRUE;
    }

    return FALSE;
  }

  /**
   * Get latest scan for a directory type
   *
   * @param string $directoryType
   *   Directory type
   *
   * @return CRM_Fileanalyzer_BAO_FileAnalyzerScan|NULL
   */
  public static function getLatestScan($directoryType = NULL) {
    $scan = new self();

    if ($directoryType) {
      $scan->directory_type = $directoryType;
    }

    $scan->orderBy('scan_date DESC');
    $scan->limit(1);

    if ($scan->find(TRUE)) {
      return $scan;
    }

    return NULL;
  }

  /**
   * Get scan history
   *
   * @param array $params
   *   Optional filters (directory_type, limit, etc.)
   *
   * @return array
   *   Array of scan records
   */
  public static function getScanHistory($params = []) {
    $scan = new self();

    if (!empty($params['directory_type'])) {
      $scan->directory_type = $params['directory_type'];
    }

    if (!empty($params['scan_status'])) {
      $scan->scan_status = $params['scan_status'];
    }

    $scan->orderBy('scan_date DESC');

    if (!empty($params['limit'])) {
      $scan->limit($params['limit']);
    }

    $scan->find();

    $results = [];
    while ($scan->fetch()) {
      $results[] = clone $scan;
    }

    return $results;
  }

  /**
   * Get scan statistics summary
   *
   * @param string $directoryType
   *   Optional directory type filter
   * @param int $days
   *   Number of days to look back (default 30)
   *
   * @return array
   *   Summary statistics
   */
  public static function getScanSummary($directoryType = NULL, $days = 30) {
    $dateThreshold = date('Y-m-d H:i:s', strtotime("-{$days} days"));

    $query = "
      SELECT 
        COUNT(*) as total_scans,
        SUM(CASE WHEN scan_status = 'completed' THEN 1 ELSE 0 END) as completed_scans,
        SUM(CASE WHEN scan_status = 'failed' THEN 1 ELSE 0 END) as failed_scans,
        AVG(scan_duration) as avg_duration,
        AVG(total_files_scanned) as avg_files_scanned,
        MAX(scan_date) as last_scan_date
      FROM civicrm_file_analyzer_scan
      WHERE scan_date >= %1
    ";

    $params = [1 => [$dateThreshold, 'String']];

    if ($directoryType) {
      $query .= " AND directory_type = %2";
      $params[2] = [$directoryType, 'String'];
    }

    $dao = CRM_Core_DAO::executeQuery($query, $params);

    if ($dao->fetch()) {
      return [
        'total_scans' => (int)$dao->total_scans,
        'completed_scans' => (int)$dao->completed_scans,
        'failed_scans' => (int)$dao->failed_scans,
        'avg_duration' => round($dao->avg_duration, 2),
        'avg_files_scanned' => round($dao->avg_files_scanned, 2),
        'last_scan_date' => $dao->last_scan_date,
      ];
    }

    return [];
  }

  /**
   * Check if a scan is currently running
   *
   * @param string $directoryType
   *   Optional directory type filter
   *
   * @return bool
   */
  public static function isScanRunning($directoryType = NULL) {
    $scan = new self();
    $scan->scan_status = 'running';

    if ($directoryType) {
      $scan->directory_type = $directoryType;
    }

    return (bool)$scan->count();
  }

  /**
   * Get running scan
   *
   * @param string $directoryType
   *   Optional directory type filter
   *
   * @return CRM_Fileanalyzer_BAO_FileAnalyzerScan|NULL
   */
  public static function getRunningScan($directoryType = NULL) {
    $scan = new self();
    $scan->scan_status = 'running';

    if ($directoryType) {
      $scan->directory_type = $directoryType;
    }

    if ($scan->find(TRUE)) {
      return $scan;
    }

    return NULL;
  }

  /**
   * Update scan progress
   *
   * @param int $scanId
   *   Scan record ID
   * @param array $progress
   *   Progress data
   *
   * @return bool
   */
  public static function updateProgress($scanId, $progress) {
    $scan = new self();
    $scan->id = $scanId;

    if ($scan->find(TRUE)) {
      if (isset($progress['total_files_scanned'])) {
        $scan->total_files_scanned = $progress['total_files_scanned'];
      }
      if (isset($progress['active_files'])) {
        $scan->active_files = $progress['active_files'];
      }
      if (isset($progress['abandoned_files'])) {
        $scan->abandoned_files = $progress['abandoned_files'];
      }
      if (isset($progress['total_size'])) {
        $scan->total_size = $progress['total_size'];
      }
      if (isset($progress['abandoned_size'])) {
        $scan->abandoned_size = $progress['abandoned_size'];
      }

      $scan->save();
      return TRUE;
    }

    return FALSE;
  }

  /**
   * Delete scan record
   *
   * @param int $id
   *   Record ID to delete
   *
   * @return bool
   */
  public static function del($id) {
    $scan = new self();
    $scan->id = $id;

    if ($scan->find(TRUE)) {
      CRM_Utils_Hook::pre('delete', 'FileanalyzerScan', $id, CRM_Core_DAO::$_nullArray);
      $result = $scan->delete();
      CRM_Utils_Hook::post('delete', 'FileanalyzerScan', $id, $scan);
      return (bool)$result;
    }

    return FALSE;
  }

}