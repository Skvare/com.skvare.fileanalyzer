<?php
use CRM_Fileanalyzer_ExtensionUtil as E;


/**
 * CiviCRM File Analyzer Extension
 * Analyzes files in custom directory, identifies abandoned files, and provides cleanup tools
 */
// Extension Info File (info.xml should be created separately)

class CRM_FileAnalyzer_Page_Dashboard extends CRM_Core_Page {

  public function run() {
    // Set page title
    CRM_Utils_System::setTitle(ts('File Analyzer Dashboard'));
    $scanResults = CRM_FileAnalyzer_API_FileAnalysis::getLatestScanResults();
    $abandonedFiles = CRM_FileAnalyzer_API_FileAnalysis::getAbandonedFilesFromJson();
    $this->assign('fileData', json_encode($scanResults['fileAnalysis']));
    $this->assign('abandonedFiles', $abandonedFiles);
    $this->assign('directoryStats', $scanResults['directoryStats']);
    $this->assign('totalAbandonedSize', array_sum(array_column($abandonedFiles, 'size')),);

    // Add CSS and JS resources
    CRM_Core_Resources::singleton()
      ->addStyleFile('com.skvare.fileanalyzer', 'css/dashboard.css')
      ->addScriptFile('com.skvare.fileanalyzer', 'js/dashboard.js')
      ->addScriptUrl('https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.9.1/chart.min.js');


    parent::run();
  }
}

