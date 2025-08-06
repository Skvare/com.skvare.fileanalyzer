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
    $scanResults = CRM_Fileanalyzer_API_FileAnalysis::getLatestScanResults();
    $abandonedFiles = CRM_Fileanalyzer_API_FileAnalysis::getAbandonedFilesFromJson();
    $stats = [
      'total_files' => $scanResults['directoryStats']['totalFiles'],
      'total_size' => $scanResults['directoryStats']['totalSize'],
      'abandoned_count' => count($abandonedFiles),
      'abandoned_size' => array_sum(array_column($abandonedFiles, 'size')),
      'file_types' => $scanResults['fileAnalysis']['fileTypes'],
      'monthly_data' => $scanResults['fileAnalysis']['monthly'],
    ];

    return civicrm_api3_create_success($stats, $params, 'FileAnalyzer', 'getstats');
  }
  catch (Exception $e) {
    return civicrm_api3_create_error('Failed to get stats: ' . $e->getMessage());
  }
}
