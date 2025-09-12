<?php
use CRM_Fileanalyzer_ExtensionUtil as E;

/**
 * FileAnalyzer.Scan API specification with enhanced directory support
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

  $spec['directory_type'] = [
    'name' => 'directory_type',
    'title' => 'Directory Type',
    'description' => 'Type of directory to scan: custom, contribute, or all',
    'type' => CRM_Utils_Type::T_STRING,
    'api.default' => 'all',
    'options' => [
      'all' => 'All Directories',
      'custom' => 'Custom Files Directory',
      'contribute' => 'Contribute Images Directory',
    ],
  ];
}

/**
 * FileAnalyzer.Scan API with enhanced directory support
 *
 * @param array $params
 *
 * @return array API result descriptor
 *
 * @see civicrm_api3_create_success
 *
 * @throws API_Exception
 */
function civicrm_api3_file_analyzer_Scan($params) {
  try {
    // Get directory type parameter, default to 'all'
    $directoryType = CRM_Utils_Array::value('directory_type', $params, 'all');

    // Validate directory type
    $validTypes = ['all', 'custom', 'contribute'];
    if (!in_array($directoryType, $validTypes)) {
      return civicrm_api3_create_error('Invalid directory type. Must be one of: ' . implode(', ', $validTypes));
    }

    // Execute the scan with the specified directory type
    $result = CRM_Fileanalyzer_API_FileAnalysis::scheduledScan($directoryType);
    $dao = NULL;
    return civicrm_api3_create_success(
      $result['messages'],
      $params,
      'FileAnalyzer',
      'scan',
      $dao,
      ['directory_type' => $directoryType]
    );
  }
  catch (Exception $e) {
    return civicrm_api3_create_error('File scan failed: ' . $e->getMessage());
  }
}
