<?php
use CRM_Fileanalyzer_ExtensionUtil as E;

/**
 * CiviCRM File Analyzer Extension - Public Images Dashboard Page
 *
 * This class handles the Public images dashboard display for the File Analyzer extension.
 * It reads pre-generated JSON files created by the scheduled scan job and presents
 * the data in a user-friendly dashboard with charts and statistics specifically for
 * public page images.
 *
 * Key Features:
 * - Reads cached table data for directory instead of performing live file scanning
 * - Displays file analysis statistics and charts for contribute images
 * - Shows abandoned public image files information
 * - Provides directory usage statistics for public images
 * - Handles cases where no scan data is available
 * - Reuses the same JavaScript dashboard components
 */
class CRM_Fileanalyzer_Page_ContributeDashboard extends CRM_Core_Page {

  /**
   * Main run method - Entry point for public dashboard page
   *
   * This method orchestrates the contribute dashboard display by:
   * 1. Setting the page title
   * 2. Retrieving cached scan results from table to public directory
   * 3. Processing and assigning data to the template
   * 4. Loading required CSS/JS resources (reusing existing dashboard assets)
   * 5. Calling parent run method to render the page
   *
   * @return void
   */
  public function run() {
    // Set the browser title and page heading
    CRM_Utils_System::setTitle(ts('Public Images Analyzer Dashboard'));

    // Retrieve the latest scan results for public directory
    $scanResults = CRM_Fileanalyzer_API_FileAnalysis::getLatestScanResults(CRM_Fileanalyzer_API_FileAnalysis::DIRECTORY_CONTRIBUTE);

    // Handle case where no public scan results are available
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
    $this->assign('directoryDisplayName', ts('Public Images'));

    // Assign directory path for display
    $config = CRM_Core_Config::singleton();
    $baseDir = dirname($config->customFileUploadDir);
    $contributePath = $baseDir . '/persist/contribute/images';
    $this->assign('directoryPath', $contributePath);

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
}
