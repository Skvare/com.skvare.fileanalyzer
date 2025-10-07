<?php
use CRM_Fileanalyzer_ExtensionUtil as E;

/**
 * Enhanced CiviCRM File Analyzer Extension Dashboard Page with Preview and Export
 *
 * This class handles the main dashboard display for the File Analyzer extension.
 * It reads pre-generated JSON files created by the scheduled scan job and presents
 * the data in a user-friendly dashboard with charts and statistics.
 *
 * Enhanced Features:
 * - File preview functionality using CRM_Fileanalyzer_Page_Preview
 * - Export functionality using CRM_Fileanalyzer_Page_Export
 * - AJAX operations for file management
 */
class CRM_Fileanalyzer_Page_Dashboard extends CRM_Core_Page {

  /**
   * Main run method - Entry point for dashboard page
   *
   * This method orchestrates the dashboard display by:
   * 1. Setting the page title
   * 2. Retrieving cached scan results from JSON files
   * 3. Processing and assigning data to the template
   * 4. Loading required CSS/JS resources
   * 5. Setting up preview and export URLs
   * 6. Calling parent run method to render the page
   *
   * @return void
   */
  public function run() {
    // Set the browser title and page heading
    CRM_Utils_System::setTitle(ts('File Analyzer Dashboard'));

    // Retrieve the latest scan results.
    $scanResults = CRM_Fileanalyzer_API_FileAnalysis::getLatestScanResults(CRM_Fileanalyzer_API_FileAnalysis::DIRECTORY_CUSTOM);

    // Handle case where no scan results are available
    if (!$scanResults) {
      $scanResults = [
        'scan_date' => NULL,
        'fileAnalysis' => [
          'monthly' => [],
          'fileTypes' => []
        ],
        'directoryStats' => [
          'totalFiles' => 0,
          'totalSize' => 0,
          'abandonedSize' => 0,
          'abandonedFiles' => 0,
        ],
        'active_files' => 0,
      ];

      CRM_Core_Session::setStatus(
        ts('No scan data available. Please run the scheduled job first.'),
        ts('No Data'),
        'info'
      );
    }

    // This date is used to inform users when the last scan was performed
    $this->assign('lastScanDate', $scanResults['scan_date']);

    // Assign file analysis data to template for chart rendering
    // JSON encode is needed for JavaScript chart libraries
    $this->assign('fileData', json_encode($scanResults['fileAnalysis']));
    $abandonedFiles = [];
    // Assign enhanced abandoned files array to template for table display
    // $this->assign('abandonedFiles', $abandonedFiles);

    // Assign directory statistics for summary widgets
    $this->assign('directoryStats', $scanResults['directoryStats']);

    // Calculate total size of abandoned files for summary display
    // Uses array_column to extract 'size' values, then sum them
    $this->assign('totalAbandonedSize', array_sum(array_column($abandonedFiles, 'size')));

    // Assign directory type for template logic
    $this->assign('directoryType', CRM_Fileanalyzer_API_FileAnalysis::DIRECTORY_CUSTOM);

    // Assign URLs for preview and export functionality
    $this->assign('previewUrl', CRM_Utils_System::url('civicrm/file-analyzer/preview'));
    $this->assign('exportUrl', CRM_Utils_System::url('civicrm/file-analyzer/export'));
    $this->assign('ajaxUrl', CRM_Utils_System::url('civicrm/ajax/file-analyzer'));

    // Check if files have export data available
    $this->assign('canExport', !empty($abandonedFiles));
    $this->assign('exportFormats', ['csv' => 'CSV', 'json' => 'JSON']);

    // Load required frontend resources for dashboard functionality
    CRM_Core_Resources::singleton()
      // Add custom dashboard CSS for styling
      ->addStyleFile('com.skvare.fileanalyzer', 'css/dashboard.css')
      // Add custom JavaScript for dashboard interactions
      ->addScriptFile('com.skvare.fileanalyzer', 'js/dashboard.js')
      // Add Chart.js library from CDN for data visualization
      ->addScriptUrl('https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.9.1/chart.min.js');

    // Call parent run method to complete page rendering
    parent::run();
  }

  /**
   * Enhance files with preview information and capabilities
   *
   * @param array $files Array of file information
   * @return array Enhanced files array with preview data
   */
  private function enhanceFilesWithPreviewInfo($files) {
    $enhancedFiles = [];

    foreach ($files as $file) {
      // Add preview capability flags
      $file['can_preview'] = $this->canPreviewFile($file);
      $file['preview_type'] = $this->getPreviewType($file);
      $file['preview_url'] = $this->getPreviewUrl($file);
      $file['is_image'] = $this->isImageFile($file);

      $enhancedFiles[] = $file;
    }

    return $enhancedFiles;
  }

  /**
   * Check if a file can be previewed
   *
   * @param array $file File information array
   * @return bool True if file can be previewed
   */
  private function canPreviewFile($file) {
    $previewableExtensions = [
      'jpg', 'jpeg', 'png', 'gif', 'svg', 'webp', 'bmp', // Images
      'pdf', // PDFs
      'txt', 'log', 'csv', 'xml', 'json' // Text files
    ];

    return in_array(strtolower($file['extension']), $previewableExtensions);
  }

  /**
   * Get the preview type for a file
   *
   * @param array $file File information array
   * @return string Preview type (image, pdf, text, none)
   */
  private function getPreviewType($file) {
    $extension = strtolower($file['extension']);

    if (in_array($extension, ['jpg', 'jpeg', 'png', 'gif', 'svg', 'webp', 'bmp'])) {
      return 'image';
    }

    if ($extension === 'pdf') {
      return 'pdf';
    }

    if (in_array($extension, ['txt', 'log', 'csv', 'xml', 'json'])) {
      return 'text';
    }

    return 'none';
  }

  /**
   * Generate preview URL for a file
   *
   * @param array $file File information array
   * @return string Preview URL
   */
  private function getPreviewUrl($file) {
    return CRM_Utils_System::url('civicrm/file-analyzer/preview', [
      'file' => urlencode($file['path'])
    ]);
  }

  /**
   * Check if file is an image
   *
   * @param array $file File information array
   * @return bool True if file is an image
   */
  private function isImageFile($file) {
    $imageExtensions = ['jpg', 'jpeg', 'png', 'gif', 'svg', 'webp', 'bmp'];
    return in_array(strtolower($file['extension']), $imageExtensions);
  }

  /**
   * Generate export URL for current directory data
   *
   * @param string $format Export format (csv, json)
   * @return string Export URL
   */
  public function getExportUrl($format = 'csv') {
    return CRM_Utils_System::url('civicrm/file-analyzer/export', [
      'directory' => CRM_Fileanalyzer_API_FileAnalysis::DIRECTORY_CUSTOM,
      'format' => $format
    ]);
  }

  /**
   * Get file statistics for export
   *
   * @return array Statistics array
   */
  public function getFileStats() {
    $scanResults = CRM_Fileanalyzer_API_FileAnalysis::getLatestScanResults(CRM_Fileanalyzer_API_FileAnalysis::DIRECTORY_CUSTOM);
    $abandonedFiles = CRM_Fileanalyzer_API_FileAnalysis::getAbandonedFilesFromJson(CRM_Fileanalyzer_API_FileAnalysis::DIRECTORY_CUSTOM);

    return [
      'total_files' => $scanResults['directoryStats']['totalFiles'] ?? 0,
      'total_size' => $scanResults['directoryStats']['totalSize'] ?? 0,
      'abandoned_count' => count($abandonedFiles),
      'abandoned_size' => array_sum(array_column($abandonedFiles, 'size')),
      'active_files' => $scanResults['active_files'] ?? 0,
      'scan_date' => $scanResults['scan_date'] ?? null,
      'directory_type' => CRM_Fileanalyzer_API_FileAnalysis::DIRECTORY_CUSTOM
    ];
  }
}
