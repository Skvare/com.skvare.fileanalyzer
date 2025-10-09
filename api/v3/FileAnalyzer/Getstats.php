<?php
use CRM_Fileanalyzer_ExtensionUtil as E;

/**
 * FileAnalyzer.Getstats API specification with directory type support
 */
function _civicrm_api3_file_analyzer_Getstats_spec(&$spec) {
  $spec['directory_type'] = [
    'name' => 'directory_type',
    'title' => 'Directory Type',
    'description' => 'Type of directory to get stats for: custom, contribute, or all',
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
 * FileAnalyzer.Getstats API with directory type support
 *
 * @param array $params
 *
 * @return array API result descriptor
 *
 * @see civicrm_api3_create_success
 *
 * @throws API_Exception
 */
function civicrm_api3_file_analyzer_Getstats($params) {
  try {
    // Get directory type parameter, default to 'all'
    $directoryType = CRM_Utils_Array::value('directory_type', $params, 'all');

    // Validate directory type
    $validTypes = ['all', 'custom', 'contribute'];
    if (!in_array($directoryType, $validTypes)) {
      return civicrm_api3_create_error('Invalid directory type. Must be one of: ' . implode(', ', $validTypes));
    }

    $stats = [];

    if ($directoryType === 'all') {
      // Get stats for all directories
      $scanResults['custom'] = CRM_Fileanalyzer_API_FileAnalysis::getLatestScanResults('custom');
      $scanResults['contribute'] = CRM_Fileanalyzer_API_FileAnalysis::getLatestScanResults('contribute');
      if ($scanResults) {
        foreach ($scanResults as $type => $results) {
          $stats[$type] = [
            'total_files' => $results['directoryStats']['totalFiles'],
            'total_size' => $results['directoryStats']['totalSize'],
            'abandoned_count' => $results['directoryStats']['abandonedFiles'],
            'abandoned_size' => $results['directoryStats']['abandonedSize'],
            'active_files' => $results['active_files'] ?? 0,
            'file_types' => $results['fileAnalysis']['fileTypes'] ?? [],
            'monthly_data' => $results['fileAnalysis']['monthly'] ?? [],
            'last_scan' => $results['scan_date'] ?? NULL,
          ];
        }

        // Calculate combined totals
        $stats['combined'] = [
          'total_files' => array_sum(array_column($stats, 'total_files')),
          'total_size' => array_sum(array_column($stats, 'total_size')),
          'abandoned_count' => array_sum(array_column($stats, 'abandoned_count')),
          'abandoned_size' => array_sum(array_column($stats, 'abandoned_size')),
          'active_files' => array_sum(array_column($stats, 'active_files')),
        ];
      }
    }
    else {
      // Get stats for specific directory
      $scanResults = CRM_Fileanalyzer_API_FileAnalysis::getLatestScanResults($directoryType);

      if ($scanResults) {
        $stats = [
          'total_files' => $scanResults['directoryStats']['totalFiles'],
          'total_size' => $scanResults['directoryStats']['totalSize'],
          'abandoned_count' => $scanResults['directoryStats']['abandonedFiles'],
          'abandoned_size' => $scanResults['directoryStats']['abandonedSize'],
          'active_files' => $scanResults['active_files'] ?? 0,
          'file_types' => $scanResults['fileAnalysis']['fileTypes'] ?? [],
          'monthly_data' => $scanResults['fileAnalysis']['monthly'] ?? [],
          'last_scan' => $scanResults['scan_date'] ?? NULL,
          'directory_type' => $directoryType,
        ];
      }
      else {
        $stats = [
          'total_files' => 0,
          'total_size' => 0,
          'abandoned_count' => 0,
          'abandoned_size' => 0,
          'active_files' => 0,
          'file_types' => [],
          'monthly_data' => [],
          'last_scan' => NULL,
          'directory_type' => $directoryType,
        ];
      }
    }

    return civicrm_api3_create_success($stats, $params, 'FileAnalyzer', 'getstats');
  }
  catch (Exception $e) {
    return civicrm_api3_create_error('Failed to get stats: ' . $e->getMessage());
  }
}
