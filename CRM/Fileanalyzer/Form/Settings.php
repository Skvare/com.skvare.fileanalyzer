<?php

use CRM_Fileanalyzer_ExtensionUtil as E;


class CRM_FileAnalyzer_Form_Settings extends CRM_Admin_Form_Setting {

  protected $_settings = [
    'fileanalyzer_scan_interval' => 'FileAnalyzer Setting',
    'fileanalyzer_auto_delete' => 'FileAnalyzer Setting',
    'fileanalyzer_auto_delete_days' => 'FileAnalyzer Setting',
    'fileanalyzer_backup_before_delete' => 'FileAnalyzer Setting',
    'fileanalyzer_excluded_extensions' => 'FileAnalyzer Setting',
  ];

  public function preProcess() {
    // Add CSS and JS resources
    CRM_Core_Resources::singleton()
      ->addStyleFile('com.skvare.fileanalyzer', 'css/settings.css');
    $config = CRM_Core_Config::singleton();
    $this->assign('customFileUploadDir', $config->customFileUploadDir);
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
          <li><strong>Scan Interval:</strong> How frequently the system checks for abandoned files</li>
          <li><strong>Auto-delete:</strong> Enable automatic cleanup of abandoned files</li>
          <li><strong>Backup:</strong> Create backups before deletion (highly recommended)</li>
          <li><strong>Excluded Extensions:</strong> File types to ignore during analysis</li>
        </ul>
      </div>
    ');

    // Add JavaScript for conditional fields
    CRM_Core_Resources::singleton()->addScript('
      CRM.$(function($) {
        $("#fileanalyzer_auto_delete").change(function() {
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
}
