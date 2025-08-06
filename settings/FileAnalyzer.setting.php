<?php
/**
 * FileAnalyzer settings
 *
 * This file defines the settings for the FileAnalyzer extension.
 */
return [
  'fileanalyzer_auto_delete' => [
    'name' => 'fileanalyzer_auto_delete',
    'type' => 'Boolean',
    'default' => 0,
    'add' => '5.0',
    'title' => ts('Auto-delete Abandoned Files'),
    'description' => ts('Automatically delete files that have been abandoned for more than the specified days'),
    'is_domain' => 1,
    'is_contact' => 0,
    'settings_pages' => ['fileanalyzer' => ['weight' => 20]],
    'html_type' => 'checkbox',
  ],
  'fileanalyzer_auto_delete_days' => [
    'name' => 'fileanalyzer_auto_delete_days',
    'type' => 'Integer',
    'default' => 90,
    'add' => '5.0',
    'title' => ts('Auto-delete After (days)'),
    'description' => ts('Number of days after which abandoned files will be automatically deleted'),
    'is_domain' => 1,
    'is_contact' => 0,
    'settings_pages' => ['fileanalyzer' => ['weight' => 30]],
    'html_type' => 'number',
    'html_attributes' => [
      'min' => 1,
      'max' => 365,
    ],
  ],
  'fileanalyzer_backup_before_delete' => [
    'name' => 'fileanalyzer_backup_before_delete',
    'type' => 'Boolean',
    'default' => 1,
    'add' => '5.0',
    'title' => ts('Backup Files Before Deletion'),
    'description' => ts('Create a backup of files before deletion (recommended)'),
    'is_domain' => 1,
    'is_contact' => 0,
    'settings_pages' => ['fileanalyzer' => ['weight' => 40]],
    'html_type' => 'checkbox',
  ],
  'fileanalyzer_excluded_extensions' => [
    'name' => 'fileanalyzer_excluded_extensions',
    'type' => 'String',
    'default' => 'tmp,log,cache,htaccess',
    'add' => '5.0',
    'title' => ts('Excluded File Extensions'),
    'description' => ts('Comma-separated list of file extensions to exclude from analysis'),
    'is_domain' => 1,
    'is_contact' => 0,
    'settings_pages' => ['fileanalyzer' => ['weight' => 50]],
    'html_type' => 'text',
    'html_attributes' => [
      'placeholder' => 'tmp,log,cache,htaccess',
    ],
  ],
];
