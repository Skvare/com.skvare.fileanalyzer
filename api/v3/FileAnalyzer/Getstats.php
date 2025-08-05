<?php
use CRM_Fileanalyzer_ExtensionUtil as E;

/**
 * FileAnalyzer.Getstats API specification (optional)
 * This is used for documentation and validation.
 *
 * @param array $spec description of fields supported by this API call
 *
 * @see https://docs.civicrm.org/dev/en/latest/framework/api-architecture/
 */
function _civicrm_api3_file_analyzer_Getstats_spec(&$spec) {
  $spec['magicword']['api.required'] = 1;
}

/**
 * FileAnalyzer.Getstats API
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
function civicrm_api3_file_analyzer_Getstats($params) {
  try {
    $dashboard = new CRM_FileAnalyzer_Page_Dashboard();
    $fileData = $dashboard->getFileAnalysisData();
    $abandonedFiles = $dashboard->getAbandonedFiles();
    $directoryStats = $dashboard->getDirectoryStats();

    $stats = [
      'total_files' => $directoryStats['totalFiles'],
      'total_size' => $directoryStats['totalSize'],
      'abandoned_count' => count($abandonedFiles),
      'abandoned_size' => array_sum(array_column($abandonedFiles, 'size')),
      'oldest_file' => $directoryStats['oldestFile'],
      'newest_file' => $directoryStats['newestFile'],
      'file_types' => $fileData['fileTypes'],
      'monthly_data' => $fileData['monthly'],
    ];

    return civicrm_api3_create_success($stats, $params, 'FileAnalyzer', 'getstats');
  }
  catch (Exception $e) {
    return civicrm_api3_create_error('Failed to get stats: ' . $e->getMessage());
  }
}
