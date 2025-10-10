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
   * 2. Retrieving cached scan results from database files
   * 3. Processing and assigning data to the template
   * 4. Loading required CSS/JS resources
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
  
    // Assign directory statistics for summary widgets
    $this->assign('directoryStats', $scanResults['directoryStats']);

    // Assign directory type for template logic
    $this->assign('directoryType', CRM_Fileanalyzer_API_FileAnalysis::DIRECTORY_CUSTOM);

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

}
