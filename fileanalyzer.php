<?php

require_once 'fileanalyzer.civix.php';

use CRM_Fileanalyzer_ExtensionUtil as E;

/**
 * Implements hook_civicrm_config().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_config/
 */
function fileanalyzer_civicrm_config(&$config) {
  _fileanalyzer_civix_civicrm_config($config);
  // Register modifier
  static $registered = false;
  if (!$registered) {
    $smarty = CRM_Core_Smarty::singleton();
    $smarty->registerPlugin("modifier", "filesize", "smarty_modifier_filesize");
    $registered = true;
  }
}

/**
 * Implements hook_civicrm_install().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_install
 */
function fileanalyzer_civicrm_install() {
  _fileanalyzer_civix_civicrm_install();
}

/**
 * Implements hook_civicrm_enable().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_enable
 */
function fileanalyzer_civicrm_enable() {
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

  // Add Public Images Dashboard submenu item
  _fileanalyzer_civix_insert_navigation_menu($menu, 'Administer/System Settings/file_analyzer_main', [
    'label' => ts('Public Images Dashboard'),
    'name' => 'file_analyzer_contribute',
    'url' => 'civicrm/file-analyzer/public-dashboard',
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


function smarty_modifier_filesize($bytes) {
  $units = ['B', 'KB', 'MB', 'GB', 'TB'];
  $i = 0;
  while ($bytes >= 1024 && $i < count($units) - 1) {
    $bytes /= 1024;
    $i++;
  }
  return round($bytes, 2) . ' ' . $units[$i];
}