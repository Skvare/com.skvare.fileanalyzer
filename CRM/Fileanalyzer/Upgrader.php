<?php

use CRM_Fileanalyzer_ExtensionUtil as E;

/**
 * Collection of upgrade steps.
 */
class CRM_Fileanalyzer_Upgrader extends CRM_Extension_Upgrader_Base {

  // By convention, functions that look like "function upgrade_NNNN()" are
  // upgrade tasks. They are executed in order (like Drupal's hook_update_N).

  /**
   * Example: Run an external SQL script when the module is installed.
   *
   * Note that if a file is present sql\auto_install that will run regardless of this hook.
   */
  public function install(): void {
    $this->createDirectories();
    $this->setDefaultSettings();
  }

  /**
   * Create necessary directories
   */
  private function createDirectories() {
    $backupDir = CRM_Core_Config::singleton()->customFileUploadDir . '/file_analyzer_backups';

    if (!is_dir($backupDir)) {
      if (!mkdir($backupDir, 0755, TRUE)) {
        CRM_Core_Error::debug_log_message('FileAnalyzer: Failed to create backup directory');
      }
    }

    // Create .htaccess to protect backup directory
    $htaccessPath = $backupDir . '/.htaccess';
    if (!file_exists($htaccessPath)) {
      $htaccessContent = "Order deny,allow\nDeny from all\n";
      file_put_contents($htaccessPath, $htaccessContent);
    }
    $subdirs = ['deleted_files', 'exports', 'reports'];
    foreach ($subdirs as $subdir) {
      mkdir($backupDir . '/' . $subdir, 0755, TRUE);
    }
  }

  /**
   * Set default settings
   */
  private function setDefaultSettings() {
    $settings = [
      'fileanalyzer_auto_delete' => 0,
      'fileanalyzer_auto_delete_days' => 90,
      'fileanalyzer_backup_before_delete' => 1,
      'fileanalyzer_excluded_extensions' => 'tmp,log,cache,htaccess',
      'fileanalyzer_excluded_folders' => 'thumbnails,static',
    ];

    foreach ($settings as $name => $value) {
      Civi::settings()->set($name, $value);
    }
  }


  /**
   * Example: Run a couple simple queries.
   *
   * @return TRUE on success
   * @throws CRM_Core_Exception
   */
  public function upgrade_1100(): bool {
    $this->ctx->log->info('Applying update 1100');
    if (!CRM_Core_BAO_SchemaHandler::checkIfFieldExists('civicrm_file_analyzer', 'item_table') &&
      CRM_Core_BAO_SchemaHandler::checkIfFieldExists('civicrm_file_analyzer', 'entity_table')) {
      CRM_Core_DAO::executeQuery("ALTER TABLE `civicrm_file_analyzer` DROP INDEX `index_entity`");
      CRM_Core_DAO::executeQuery("ALTER TABLE `civicrm_file_analyzer` CHANGE `entity_table` `item_table` VARCHAR(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL COMMENT 'Related entity table name'");
    }

    if (!CRM_Core_BAO_SchemaHandler::checkIfFieldExists('civicrm_file_analyzer', 'item_id') &&
      CRM_Core_BAO_SchemaHandler::checkIfFieldExists('civicrm_file_analyzer', 'entity_id')) {
      CRM_Core_DAO::executeQuery("ALTER TABLE `civicrm_file_analyzer` CHANGE `entity_id` `item_id` INT UNSIGNED NULL DEFAULT NULL COMMENT 'Related entity ID'");
      CRM_Core_DAO::executeQuery("ALTER TABLE `civicrm_file_analyzer` ADD INDEX `index_item` (`item_table`, `item_id`)");
    }

    return TRUE;
  }
}
