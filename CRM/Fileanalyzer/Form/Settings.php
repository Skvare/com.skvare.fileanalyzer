<?php

use CRM_Fileanalyzer_ExtensionUtil as E;


class CRM_Fileanalyzer_Form_Settings extends CRM_Admin_Form_Setting {

  protected $_settings = [
    'fileanalyzer_auto_delete' => 'FileAnalyzer Setting',
    'fileanalyzer_auto_delete_days' => 'FileAnalyzer Setting',
    'fileanalyzer_backup_before_delete' => 'FileAnalyzer Setting',
    'fileanalyzer_excluded_extensions' => 'FileAnalyzer Setting',
    'fileanalyzer_excluded_folders' => 'FileAnalyzer Setting',
  ];

  public function preProcess() {
    // Add CSS and JS resources
    CRM_Core_Resources::singleton()->addStyleFile('com.skvare.fileanalyzer', 'css/settings.css');
    $config = CRM_Core_Config::singleton();
    $this->assign('fileUploadDir', $config->uploadDir);
    $this->assignTemplateVars();
    parent::preProcess();
  }

  public function buildQuickForm() {
    CRM_Utils_System::setTitle(ts('File Analyzer Settings'));

    parent::buildQuickForm();

    // Add custom validation and help text
    $this->add('html', 'help_text', NULL, '
      <div class="help">
        <h3>File Analyzer Configuration</h3>
        <p>Configure how the File Analyzer extension monitors and manages your CiviCRM files.</p>
        <ul>
          <li><strong>Auto-delete:</strong> Enable automatic cleanup of abandoned files</li>
          <li><strong>Backup:</strong> Create backups before deletion (highly recommended)</li>
          <li><strong>Excluded Extensions:</strong> File types to ignore during analysis</li>
        </ul>
      </div>
    ');

    // Add JavaScript for conditional fields
    CRM_Core_Resources::singleton()->addScript('
      CRM.$(function($) {
        $("#fileanalyzer_auto_delete_fileanalyzer_auto_delete").change(function() {
          if ($(this).is(":checked")) {
            $("#fileanalyzer_auto_delete_days").closest(".crm-section").show();
          } else {
            $("#fileanalyzer_auto_delete_days").closest(".crm-section").hide();
          }
        }).trigger("change");
      });
    ');
  }

  public function postProcess() {
    parent::postProcess();

    CRM_Core_Session::setStatus(
      ts('File Analyzer settings have been saved.'),
      ts('Settings Saved'),
      'success'
    );
  }

  /**
   * Assign template variables.
   */
  private function assignTemplateVars() {
    // Directory writable check
    $backupPath = CRM_Core_Config::singleton()->uploadDir . 'file_analyzer_backups';
    if (!is_dir($backupPath)) {
      if (!mkdir($backupPath, 0755, TRUE)) {
        CRM_Core_Error::debug_log_message('FileAnalyzer: Failed to create backup directory');
      }
    }
    //$backupDir = CRM_Core_Config::singleton()->configAndLogDir .
    // '/file_analyzer_backups'
    $this->assign('dirWritable', is_writable($backupPath));

    // Backup path
    $this->assign('backupPath', $backupPath);

    // Last scan information
    $lastScan = $this->getLastScanInfo();
    $this->assign('lastScan', $lastScan);

    // Scheduled job status
    $scheduledJobActive = $this->getScheduledJobStatus();
    $this->assign('scheduledJobActive', $scheduledJobActive);

    // PHP memory limit
    $phpMemoryLimit = $this->getPhpMemoryLimit();
    $this->assign('phpMemoryLimit', $phpMemoryLimit);
  }

  /**
   * Get last scan information.
   *
   * @return string|void
   */
  private function getLastScanInfo() {
    try {
      // Query the database for last scan info
      $sql = "SELECT last_run as last_scan_date
              FROM civicrm_job j
              WHERE api_entity = 'FileAnalyzer'
              AND api_action = 'scan'
              AND is_active = 1";

      $lastRunDate = CRM_Core_DAO::singleValueQuery($sql);
      return $lastRunDate ? date('Y-m-d H:i:s', strtotime($lastRunDate)) : ts('Never');
    }
    catch (Exception $e) {
      CRM_Core_Error::debug_log_message('FileAnalyzer: Error getting last scan info - ' . $e->getMessage());
    }
  }

  /**
   * Get scheduled job status.
   *
   * @return bool
   */
  private function getScheduledJobStatus() {
    try {
      // Check if the scheduled job exists and is active
      $sql = "SELECT is_active FROM civicrm_job
              WHERE api_entity = 'FileAnalyzer'
              AND api_action = 'scan'
              LIMIT 1";

      $dao = CRM_Core_DAO::executeQuery($sql);

      if ($dao->fetch()) {
        return (bool)$dao->is_active;
      }
    }
    catch (Exception $e) {
      CRM_Core_Error::debug_log_message('FileAnalyzer: Error getting scheduled job status - ' . $e->getMessage());
    }

    return FALSE;
  }

  /**
   * Get PHP memory limit.
   *
   * @return string
   */
  private function getPhpMemoryLimit() {
    $memoryLimit = ini_get('memory_limit');

    if ($memoryLimit === FALSE || $memoryLimit == -1) {
      return ts('Unlimited');
    }

    return $memoryLimit;
  }

}
