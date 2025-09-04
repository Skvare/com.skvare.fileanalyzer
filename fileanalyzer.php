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
 * Menu hook to add navigation items for both custom files and contribute images dashboards
 */
function fileanalyzer_civicrm_navigationMenu(&$menu) {
  // Add main File Analyzer menu item with submenu
  _fileanalyzer_civix_insert_navigation_menu($menu, 'Administer/System Settings', [
    'label' => ts('File Analyzer'),
    'name' => 'file_analyzer_main',
    'url' => NULL, // This will be a parent menu item
    'permission' => 'administer CiviCRM',
    'operator' => 'OR',
    'separator' => 0,
  ]);

  // Add Custom Files Dashboard submenu item
  _fileanalyzer_civix_insert_navigation_menu($menu, 'Administer/System Settings/file_analyzer_main', [
    'label' => ts('Custom Files Dashboard'),
    'name' => 'file_analyzer_custom',
    'url' => 'civicrm/file-analyzer/dashboard',
    'permission' => 'administer CiviCRM',
    'operator' => 'OR',
    'separator' => 0,
  ]);

  // Add Contribute Images Dashboard submenu item
  _fileanalyzer_civix_insert_navigation_menu($menu, 'Administer/System Settings/file_analyzer_main', [
    'label' => ts('Contribute Images Dashboard'),
    'name' => 'file_analyzer_contribute',
    'url' => 'civicrm/file-analyzer/contribute-dashboard',
    'permission' => 'administer CiviCRM',
    'operator' => 'OR',
    'separator' => 0,
  ]);

  // Add Settings submenu item
  _fileanalyzer_civix_insert_navigation_menu($menu, 'Administer/System Settings/file_analyzer_main', [
    'label' => ts('File Analyzer Settings'),
    'name' => 'file_analyzer_settings',
    'url' => 'civicrm/admin/setting/fileanalyzer',
    'permission' => 'administer CiviCRM',
    'operator' => 'OR',
    'separator' => 1, // Add separator before settings
  ]);

  _fileanalyzer_civix_navigationMenu($menu);
}
