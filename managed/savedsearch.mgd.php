<?php

use CRM_Fileanalyzer_ExtensionUtil as E;

return [
  // Saved Search for Fileanalyzer_Search
  [
    'name' => 'SavedSearch_Fileanalyzer_Search',
    'entity' => 'SavedSearch',
    'cleanup' => 'always',
    'update' => 'unmodified',
    'params' => [
      'version' => 4,
      'values' => [
        'name' => 'Fileanalyzer_Search',
        'label' => E::ts('Fileanalyzer Search'),
        'api_entity' => 'Fileanalyzer',
        'api_params' => [
          'version' => 4,
          'select' => [
            'id',
            'is_active',
            'file_id',
            'filename',
            'file_size',
            'is_abandoned',
            'last_scanned_date',
            'scan_status:label',
            'is_contact_file',
            'contact_id',
            'created_date',
            'item_table:label',
            'field_name',
            'item_id',
            'is_table_reference',
            'reference_type:label',
            'mime_type',
          ],
          'orderBy' => [],
          'where' => [
            [
              'directory_type:name',
              '=',
              'custom',
            ],
            [
              'scan_status:name',
              '=',
              'scanned',
            ],
          ],
          'groupBy' => [],
          'join' => [],
          'having' => [],
        ],
      ],
      'match' => [
        'name',
      ],
    ],
  ],

  // Search Display for Fileanalyzer_Search.
  [
    'name' => 'SearchDisplay_Fileanalyzer_Search_Table_1',
    'entity' => 'SearchDisplay',
    'cleanup' => 'always',
    'update' => 'unmodified',
    'params' => [
      'version' => 4,
      'values' => [
        'name' => 'Fileanalyzer_Search_Table_1',
        'label' => E::ts('Fileanalyzer Search Table'),
        'saved_search_id.name' => 'Fileanalyzer_Search',
        'type' => 'table',
        'settings' => [
          'description' => NULL,
          'sort' => [],
          'limit' => 50,
          'pager' => [],
          'placeholder' => 5,
          'columns' => [
            [
              'type' => 'html',
              'key' => 'filename',
              'label' => E::ts('Filename'),
              'sortable' => TRUE,
              'rewrite' => "{capture assign=filename}{\"[filename]\"}{/capture}\n{capture assign=file_id}{\"[file_id]\"}{/capture}\n{capture assign=mime_type}{\"[mime_type]\"}{/capture}\n\n{if \$file_id}\n <a target=\"_blank\" href=\"[file_id]\">{\$filename|truncate:40:' .... ':true:true}<a/>\n{else}\n {capture assign=crmURL}{crmURL p='civicrm/file' q=\"reset=1&filename=[filename]&mime-type=`\$mime_type`&reset=1\"}{/capture}\n <a target=\"_blank\" href=\"{\$crmURL}\">{\$filename|truncate:40:' .... ':true:true}<a/>\n{/if}",
            ],
            [
              'type' => 'html',
              'key' => 'file_size',
              'label' => E::ts('File Size'),
              'sortable' => TRUE,
              'rewrite' => '{capture assign=file_size}{"[file_size]"}{/capture}
{$file_size|filesize}',
            ],
            [
              'type' => 'field',
              'key' => 'is_abandoned',
              'label' => E::ts('Is Abandoned'),
              'sortable' => TRUE,
            ],
            [
              'type' => 'field',
              'key' => 'created_date',
              'label' => E::ts('File Upload Date'),
              'sortable' => TRUE,
            ],
            [
              'type' => 'field',
              'key' => 'last_scanned_date',
              'label' => E::ts('Last Scanned Date'),
              'sortable' => TRUE,
            ],
            [
              'type' => 'field',
              'key' => 'is_active',
              'label' => E::ts('Is Active'),
              'sortable' => TRUE,
            ],
            [
              'type' => 'field',
              'key' => 'is_contact_file',
              'label' => E::ts('Is Contact File'),
              'sortable' => TRUE,
            ],
            [
              'type' => 'html',
              'key' => 'item_table:label',
              'label' => E::ts('Entity Table'),
              'sortable' => TRUE,
              'rewrite' => '<span class="badge badge-dark">[item_table:label]</span>
<span class="badge badge-success">[reference_type:label]</span>
<span class="badge badge-danger">[field_name]</span>',
            ],
            [
              'links' => [
                [
                  'task' => 'delete',
                  'entity' => 'Fileanalyzer',
                  'join' => '',
                  'target' => 'crm-popup',
                  'icon' => 'fa-random',
                  'text' => E::ts('Delete File(s)'),
                  'style' => 'danger',
                  'path' => '',
                  'action' => '',
                  'conditions' => [
                    [
                      'is_abandoned',
                      '=',
                      TRUE,
                    ],
                  ],
                  'condition' => [],
                ],
              ],
              'type' => 'links',
              'alignment' => 'text-right',
            ],
          ],
          'actions' => [
            'delete',
          ],
          'classes' => [
            'table',
            'table-striped',
          ],
          'actions_display_mode' => 'menu',
          'headerCount' => TRUE,
        ],
        'acl_bypass' => FALSE,
      ],
    ],
  ],

  // Saved Search for Fileanalyzer Public Search
  [
    'name' => 'SavedSearch_Fileanalyzer_Public_Search',
    'entity' => 'SavedSearch',
    'cleanup' => 'always',
    'update' => 'unmodified',
    'params' => [
      'version' => 4,
      'values' => [
        'name' => 'Fileanalyzer_Public_Search',
        'label' => E::ts('Fileanalyzer Public Search'),
        'api_entity' => 'Fileanalyzer',
        'api_params' => [
          'version' => 4,
          'select' => [
            'id',
            'is_active',
            'file_id',
            'filename',
            'file_size',
            'is_abandoned',
            'last_scanned_date',
            'scan_status:label',
            'is_contact_file',
            'contact_id',
            'created_date',
            'item_table:label',
            'item_id',
            'field_name',
            'reference_type:label',
          ],
          'orderBy' => [],
          'where' => [
            [
              'directory_type:name',
              '=',
              'contribute',
            ],
            [
              'scan_status:name',
              '=',
              'scanned',
            ],
          ],
          'groupBy' => [],
          'join' => [],
          'having' => [],
        ],
      ],
      'match' => [
        'name',
      ],
    ],
  ],

  // Search Display for Fileanalyzer Public Search
  [
    'name' => 'SearchDisplay_Fileanalyzer_Public_Search_Table_1',
    'entity' => 'SearchDisplay',
    'cleanup' => 'always',
    'update' => 'unmodified',
    'params' => [
      'version' => 4,
      'values' => [
        'name' => 'Fileanalyzer_Public_Search_Table_1',
        'label' => E::ts('Fileanalyzer Public Search Table'),
        'saved_search_id.name' => 'Fileanalyzer_Public_Search',
        'type' => 'table',
        'settings' => [
          'description' => NULL,
          'sort' => [],
          'limit' => 50,
          'pager' => [],
          'placeholder' => 5,
          'columns' => [
            [
              'type' => 'html',
              'key' => 'filename',
              'label' => E::ts('Filename'),
              'sortable' => TRUE,
              'rewrite' => "{capture assign=filename}{\"[filename]\"}{/capture}\n{capture assign=file_path}{\"[file_path]\"}{/capture}\n<a target=\"_blank\" href=\"{\$config->imageUploadURL}/{\$file_path|replace:{\$config->imageUploadDir}:''}\">{\$filename|truncate:40:' .... ':true:true}</a",
            ],
            [
              'type' => 'html',
              'key' => 'file_size',
              'label' => E::ts('File Size'),
              'sortable' => TRUE,
              'rewrite' => '{capture assign=file_size}{"[file_size]"}{/capture}
    {$file_size|filesize}',
            ],
            [
              'type' => 'field',
              'key' => 'created_date',
              'label' => E::ts('File Upload Date'),
              'sortable' => TRUE,
            ],
            [
              'type' => 'field',
              'key' => 'last_scanned_date',
              'label' => E::ts('Last Scanned Date'),
              'sortable' => TRUE,
            ],
            [
              'type' => 'field',
              'key' => 'is_abandoned',
              'label' => E::ts('Is Abandoned'),
              'sortable' => TRUE,
            ],
            [
              'type' => 'html',
              'key' => 'item_table:label',
              'label' => E::ts('Entity Info'),
              'sortable' => TRUE,
              'rewrite' => "{capture assign=item_id}{\"[item_id]\"}{/capture}\n\n{if \$item_id}\t\n{capture assign=item_table}{\"[item_table:label]\"}{/capture}\n\n{if \$item_table eq 'Contribution Page'}\n {capture assign=crmURL}{crmURL p='civicrm/admin/contribute/settings' q=\"action=update&id=`\$item_id`&reset=1\"}{/capture}\n{elseif \$item_table eq 'Event'}\n {capture assign=crmURL}{crmURL p='civicrm/event/manage/settings' q=\"action=update&id=`\$item_id`&reset=1\"}{/capture}\n{elseif \$item_table eq 'Message Template'}\n {capture assign=crmURL}{crmURL p='civicrm/admin/messageTemplates/add' q=\"action=update&id=`\$item_id`&reset=1\"}{/capture}\n{/if}\n\n<span><a target=\"_blank\" href=\"{\$crmURL}\">[item_table:label]</a></span>\n<span>[field_name]</span>\n{/if}",
            ],
            [
              'links' => [
                [
                  'task' => 'delete',
                  'entity' => 'Fileanalyzer',
                  'join' => '',
                  'target' => 'crm-popup',
                  'icon' => 'fa-random',
                  'text' => E::ts('Delete File(s)'),
                  'style' => 'danger',
                  'path' => '',
                  'action' => '',
                  'conditions' => [
                    [
                      'is_abandoned',
                      '=',
                      TRUE,
                    ],
                  ],
                  'condition' => [],
                ],
              ],
              'type' => 'links',
              'alignment' => 'text-right',
            ],
          ],
          'actions' => [
            'delete',
          ],
          'classes' => [
            'table',
            'table-striped',
          ],
          'actions_display_mode' => 'menu',
          'headerCount' => TRUE,
        ],
        'acl_bypass' => FALSE,
      ],
    ],
  ],
];