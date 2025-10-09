<?php
use CRM_Fileanalyzer_ExtensionUtil as E;

/**
 * CiviCRM File Analyzer Extension - Contribute Images Dashboard Page
 *
 * This class handles the contribute images dashboard display for the File Analyzer extension.
 * It reads pre-generated JSON files created by the scheduled scan job and presents
 * the data in a user-friendly dashboard with charts and statistics specifically for
 * contribute page images.
 *
 * Key Features:
 * - Reads cached JSON data for contribute directory instead of performing live file scanning
 * - Displays file analysis statistics and charts for contribute images
 * - Shows abandoned contribute image files information
 * - Provides directory usage statistics for contribute images
 * - Handles cases where no scan data is available
 * - Reuses the same JavaScript dashboard components
 */
class CRM_Fileanalyzer_Page_ContributeDashboard extends CRM_Core_Page {

  /**
   * Main run method - Entry point for contribute dashboard page
   *
   * This method orchestrates the contribute dashboard display by:
   * 1. Setting the page title
   * 2. Retrieving cached scan results from JSON files for contribute directory
   * 3. Processing and assigning data to the template
   * 4. Loading required CSS/JS resources (reusing existing dashboard assets)
   * 5. Calling parent run method to render the page
   *
   * @return void
   */
  public function run() {
    // Set the browser title and page heading
    CRM_Utils_System::setTitle(ts('Public Images Analyzer Dashboard'));

    // Retrieve the latest scan results from cached JSON file for contribute directory
    $scanResults = CRM_Fileanalyzer_API_FileAnalysis::getLatestScanResults(CRM_Fileanalyzer_API_FileAnalysis::DIRECTORY_CONTRIBUTE);

    // Handle case where no contribute scan results are available
    if (!$scanResults) {
      // Create empty structure to prevent template errors
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
        'active_files' => 0
      ];

      // Show message to user about no data
      CRM_Core_Session::setStatus(
        ts('No public images scan data available. Please run the scheduled job first.'),
        ts('No Data'),
        'info'
      );
    }

    // Assign scan date for display
    $this->assign('lastScanDate', $scanResults['scan_date']);

    // Assign file analysis data to template for chart rendering
    // JSON encode is needed for JavaScript chart libraries
    $this->assign('fileData', json_encode($scanResults['fileAnalysis']));

    // Assign directory statistics for summary widgets
    $this->assign('directoryStats', $scanResults['directoryStats']);

    // Assign directory type for template logic
    $this->assign('directoryType', CRM_Fileanalyzer_API_FileAnalysis::DIRECTORY_CONTRIBUTE);

    // Assign directory display name
    $this->assign('directoryDisplayName', ts('Contribute Images'));

    // Assign directory path for display
    $config = CRM_Core_Config::singleton();
    $baseDir = dirname($config->customFileUploadDir);
    $contributePath = $baseDir . '/persist/contribute/images';
    $this->assign('directoryPath', $contributePath);

    // Assign URLs for preview and export functionality
    $this->assign('previewUrl', CRM_Utils_System::url('civicrm/file-analyzer/preview'));
    $this->assign('exportUrl', CRM_Utils_System::url('civicrm/file-analyzer/export'));
    $this->assign('ajaxUrl', CRM_Utils_System::url('civicrm/ajax/file-analyzer'));

    // Check if files have export data available
    $this->assign('exportFormats', ['csv' => 'CSV', 'json' => 'JSON']);

    // Load required frontend resources for dashboard functionality
    // Reuse the same CSS and JS files from the main dashboard
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
   * Contribute images are typically web-friendly formats that can be previewed
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

      // For contribute images, most files should be images
      if ($file['is_image']) {
        $file['thumbnail_url'] = $this->getThumbnailUrl($file);
      }

      $enhancedFiles[] = $file;
    }

    return $enhancedFiles;
  }

  /**
   * Check if a file can be previewed (contribute images focus)
   *
   * @param array $file File information array
   * @return bool True if file can be previewed
   */
  private function canPreviewFile($file) {
    $previewableExtensions = [
      'jpg', 'jpeg', 'png', 'gif', 'svg', 'webp', 'bmp', // Images
      'pdf', // PDFs sometimes used in contribute pages
    ];

    return in_array(strtolower($file['extension']), $previewableExtensions);
  }

  /**
   * Get the preview type for a file (contribute images focus)
   *
   * @param array $file File information array
   * @return string Preview type (image, pdf, none)
   */
  private function getPreviewType($file) {
    $extension = strtolower($file['extension']);

    if (in_array($extension, ['jpg', 'jpeg', 'png', 'gif', 'svg', 'webp', 'bmp'])) {
      return 'image';
    }

    if ($extension === 'pdf') {
      return 'pdf';
    }

    return 'none';
  }

  /**
   * Generate preview URL for a contribute image file
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
   * Generate thumbnail URL for contribute images
   *
   * @param array $file File information array
   * @return string|null Thumbnail URL or null if not available
   */
  private function getThumbnailUrl($file) {
    // For contribute images, we can often generate direct URLs
    $config = CRM_Core_Config::singleton();
    $baseDir = dirname($config->customFileUploadDir);
    $contributePath = $baseDir . '/persist/contribute/images';

    if (strpos($file['path'], $contributePath) === 0) {
      $relativePath = substr($file['path'], strlen($contributePath) + 1);
      $baseUrl = str_replace('/custom/', '/persist/contribute/images/', $config->imageUploadURL);
      return $baseUrl . $relativePath;
    }

    return null;
  }

  /**
   * Check if file is an image (contribute focus)
   *
   * @param array $file File information array
   * @return bool True if file is an image
   */
  private function isImageFile($file) {
    $imageExtensions = ['jpg', 'jpeg', 'png', 'gif', 'svg', 'webp', 'bmp'];
    return in_array(strtolower($file['extension']), $imageExtensions);
  }

  /**
   * Generate export URL for contribute directory data
   *
   * @param string $format Export format (csv, json)
   * @return string Export URL
   */
  public function getExportUrl($format = 'csv') {
    return CRM_Utils_System::url('civicrm/file-analyzer/export', [
      'directory' => CRM_Fileanalyzer_API_FileAnalysis::DIRECTORY_CONTRIBUTE,
      'format' => $format
    ]);
  }

  /**
   * Get contribute images statistics for export
   *
   * @return array Statistics array
   */
  public function getFileStats() {
    $scanResults = CRM_Fileanalyzer_API_FileAnalysis::getLatestScanResults(CRM_Fileanalyzer_API_FileAnalysis::DIRECTORY_CONTRIBUTE);
    $abandonedFiles = CRM_Fileanalyzer_API_FileAnalysis::getAbandonedFilesFromJson(CRM_Fileanalyzer_API_FileAnalysis::DIRECTORY_CONTRIBUTE);

    return [
      'total_files' => $scanResults['directoryStats']['totalFiles'] ?? 0,
      'total_size' => $scanResults['directoryStats']['totalSize'] ?? 0,
      'abandoned_count' => count($abandonedFiles),
      'abandoned_size' => array_sum(array_column($abandonedFiles, 'size')),
      'active_files' => $scanResults['active_files'] ?? 0,
      'scan_date' => $scanResults['scan_date'] ?? NULL,
      'directory_type' => CRM_Fileanalyzer_API_FileAnalysis::DIRECTORY_CONTRIBUTE
    ];
  }
}
