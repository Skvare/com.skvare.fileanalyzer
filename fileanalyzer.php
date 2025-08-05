<?php

require_once 'fileanalyzer.civix.php';

use CRM_Fileanalyzer_ExtensionUtil as E;

/**
 * Implements hook_civicrm_config().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_config/
 */
function fileanalyzer_civicrm_config(&$config): void {
  _fileanalyzer_civix_civicrm_config($config);
}

/**
 * Implements hook_civicrm_install().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_install
 */
function fileanalyzer_civicrm_install(): void {
  _fileanalyzer_civix_civicrm_install();
}

/**
 * Implements hook_civicrm_enable().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_enable
 */
function fileanalyzer_civicrm_enable(): void {
  _fileanalyzer_civix_civicrm_enable();
}

/**
 * Menu hook to add navigation items
 */
function fileanalyzer_civicrm_navigationMenu(&$menu) {
  _fileanalyzer_civix_insert_navigation_menu($menu, 'Administer/System Settings', [
    'label' => ts('File Analyzer'),
    'name' => 'file_analyzer',
    'url' => 'civicrm/file-analyzer/dashboard',
    'permission' => 'administer CiviCRM',
    'operator' => 'OR',
    'separator' => 0,
  ]);
  _fileanalyzer_civix_navigationMenu($menu);
}
