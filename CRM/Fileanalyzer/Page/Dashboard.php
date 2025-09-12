<?php
use CRM_Fileanalyzer_ExtensionUtil as E;

/**
 * CiviCRM File Analyzer Extension Dashboard Page
 *
 * This class handles the main dashboard display for the File Analyzer extension.
 * It reads pre-generated JSON files created by the scheduled scan job and presents
 * the data in a user-friendly dashboard with charts and statistics.
 *
 * Key Features:
 * - Reads cached JSON data instead of performing live file scanning
 * - Displays file analysis statistics and charts
 * - Shows abandoned files information
 * - Provides directory usage statistics
 * - Handles cases where no scan data is available
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
   * 5. Calling parent run method to render the page
   *
   * @return void
   */
  public function run() {
    // Set the browser title and page heading
    CRM_Utils_System::setTitle(ts('File Analyzer Dashboard'));

    // Retrieve the latest scan results from cached JSON file
    // This avoids expensive real-time directory scanning
    $scanResults = CRM_Fileanalyzer_API_FileAnalysis::getLatestScanResults(CRM_Fileanalyzer_API_FileAnalysis::DIRECTORY_CUSTOM);
    // This date is used to inform users when the last scan was performed
    $this->assign('lastScanDate', $scanResults['scan_date']);

    $this->assign('fileData', json_encode($scanResults['fileAnalysis']));

    // Get abandoned files data from separate JSON file
    // Abandoned files are stored separately for quick access
    $abandonedFiles = CRM_Fileanalyzer_API_FileAnalysis::getAbandonedFilesFromJson(CRM_Fileanalyzer_API_FileAnalysis::DIRECTORY_CUSTOM);

    // Assign file analysis data to template for chart rendering
    // JSON encode is needed for JavaScript chart libraries
    $this->assign('fileData', json_encode($scanResults['fileAnalysis']));

    // Assign abandoned files array to template for table display
    $this->assign('abandonedFiles', $abandonedFiles);

    // Assign directory statistics for summary widgets
    $this->assign('directoryStats', $scanResults['directoryStats']);

    // Calculate total size of abandoned files for summary display
    // Uses array_column to extract 'size' values, then sum them
    $this->assign('totalAbandonedSize', array_sum(array_column($abandonedFiles, 'size')));

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
