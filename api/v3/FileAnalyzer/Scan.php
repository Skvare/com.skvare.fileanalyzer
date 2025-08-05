<?php
use CRM_Fileanalyzer_ExtensionUtil as E;

/**
 * FileAnalyzer.Scan API specification (optional)
 * This is used for documentation and validation.
 *
 * @param array $spec description of fields supported by this API call
 *
 * @see https://docs.civicrm.org/dev/en/latest/framework/api-architecture/
 */
function _civicrm_api3_file_analyzer_Scan_spec(&$spec) {
  $spec['force_scan'] = [
    'name' => 'force_scan',
    'title' => 'Force Full Scan',
    'description' => 'Force a complete scan regardless of last scan time',
    'type' => CRM_Utils_Type::T_BOOLEAN,
    'api.default' => 0,
  ];

  $spec['include_backup'] = [
    'name' => 'include_backup',
    'title' => 'Include Backup Files',
    'description' => 'Include backup files in the scan',
    'type' => CRM_Utils_Type::T_BOOLEAN,
    'api.default' => 0,
  ];
}

/**
 * FileAnalyzer.Scan API
 *
 * @param array $params
 *
 * @return array
 *   API result descriptor
 *
 * @see civicrm_api3_create_success
 *
 * @throws API_Exception
 */
function civicrm_api3_file_analyzer_Scan($params) {
  try {
    $result = CRM_FileAnalyzer_API_FileAnalysis::scheduledScan();

    return civicrm_api3_create_success(
      $result['messages'],
      $params,
      'FileAnalyzer',
      'scan'
    );
  }
  catch (Exception $e) {
    return civicrm_api3_create_error('File scan failed: ' . $e->getMessage());
  }
}
