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
            'Fileanalyzer_FileanalyzerReference_file_analyzer_id_01.entity_table:label',
            'Fileanalyzer_FileanalyzerReference_file_analyzer_id_01.field_name',
            'Fileanalyzer_FileanalyzerReference_file_analyzer_id_01.entity_id',
            'Fileanalyzer_FileanalyzerReference_file_analyzer_id_01.reference_type:label',
            'created_date',
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
          'join' => [
            [
              'FileanalyzerReference AS Fileanalyzer_FileanalyzerReference_file_analyzer_id_01',
              'LEFT',
              [
                'id',
                '=',
                'Fileanalyzer_FileanalyzerReference_file_analyzer_id_01.file_analyzer_id',
              ],
            ],
          ],
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
              'rewrite' => "{capture assign=filename}{\"[filename]\"}{/capture}\n{capture assign=file_id}{\"[file_id]\"}{/capture}\n{capture assign=extension}{\"[file_extension]\"}{/capture}\n\n{if \$extension == 'jpg' || \$extension == 'jpeg'}\n  {assign var=\"mime_type\" value=\"image/jpeg\"}\n{elseif \$extension == 'png'}\n  {assign var=\"mime_type\" value=\"image/png\"}\n{elseif \$extension == 'gif'}\n  {assign var=\"mime_type\" value=\"image/gif\"}\n{elseif \$extension == 'pdf'}\n  {assign var=\"mime_type\" value=\"application/pdf\"}\n{elseif \$extension == 'doc'}\n  {assign var=\"mime_type\" value=\"application/msword\"}\n{elseif \$extension == 'docx'}\n  {assign var=\"mime_type\" value=\"application/vnd.openxmlformats-officedocument.wordprocessingml.document\"}\n{elseif \$extension == 'xls'}\n  {assign var=\"mime_type\" value=\"application/vnd.ms-excel\"}\n{elseif \$extension == 'xlsx'}\n  {assign var=\"mime_type\" value=\"application/vnd.openxmlformats-officedocument.spreadsheetml.sheet\"}\n{elseif \$extension == 'zip'}\n  {assign var=\"mime_type\" value=\"application/zip\"}\n{else}\n  {assign var=\"mime_type\" value=\"application/octet-stream\"}\n{/if}\n{if \$file_id}\n  <a target=\"_blank\" href=\"[file_id]\">{\$filename|truncate:40:' .... ':true:true}<a/>\n{else}\n  {capture assign=crmURL}{crmURL p='civicrm/file' q=\"reset=1&filename=[filename]&mime-type=`\$mime_type`&reset=1\"}{/capture}\n  <a target=\"_blank\" href=\"{\$crmURL}\">{\$filename|truncate:40:' .... ':true:true}<a/>\n{/if}[file_extension]",
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
              'key' => 'Fileanalyzer_FileanalyzerReference_file_analyzer_id_01.entity_table:label',
              'label' => E::ts('Entity Info'),
              'sortable' => TRUE,
              'rewrite' => '<span class="badge badge-dark">[Fileanalyzer_FileanalyzerReference_file_analyzer_id_01.entity_table:label]</span>
<span class="badge badge-success">[Fileanalyzer_FileanalyzerReference_file_analyzer_id_01.reference_type:label]</span>
<span class="badge badge-danger">[Fileanalyzer_FileanalyzerReference_file_analyzer_id_01.field_name]</span>',
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
            'Fileanalyzer_FileanalyzerReference_file_analyzer_id_01.entity_table:label',
            'Fileanalyzer_FileanalyzerReference_file_analyzer_id_01.field_name',
            'Fileanalyzer_FileanalyzerReference_file_analyzer_id_01.entity_id',
            'Fileanalyzer_FileanalyzerReference_file_analyzer_id_01.reference_type:label',
            'created_date',
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
          'join' => [
            [
              'FileanalyzerReference AS Fileanalyzer_FileanalyzerReference_file_analyzer_id_01',
              'LEFT',
              [
                'id',
                '=',
                'Fileanalyzer_FileanalyzerReference_file_analyzer_id_01.file_analyzer_id',
              ],
            ],
          ],
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
              'rewrite' => "{capture assign=filename}{\"[filename]\"}{/capture}\n<a target=\"_blank\" href=\"/sites/default/files/civicrm/persist/contribute/images/[filename]\">{\$filename|truncate:40:' .... ':true:true}</a>",
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
              'key' => 'Fileanalyzer_FileanalyzerReference_file_analyzer_id_01.entity_table:label',
              'label' => E::ts('Entity Info'),
              'sortable' => TRUE,
              'rewrite' => "{capture assign=entity_id}{\"[Fileanalyzer_FileanalyzerReference_file_analyzer_id_01.entity_id]\"}{/capture}\n\n{if \$entity_id}\t\n{capture assign=entity_table}{\"[Fileanalyzer_FileanalyzerReference_file_analyzer_id_01.entity_table:label]\"}{/capture}\n\n{if \$entity_table eq 'Contribution Page'}\n  {capture assign=crmURL}{crmURL p='civicrm/admin/contribute/settings' q=\"action=update&id=`\$entity_id`&reset=1\"}{/capture}\n{elseif \$entity_table eq 'Event'}\n  {capture assign=crmURL}{crmURL p='civicrm/event/manage/settings' q=\"action=update&id=`\$entity_id`&reset=1\"}{/capture}\n{elseif \$entity_table eq 'Message Template'}\n  {capture assign=crmURL}{crmURL p='civicrm/admin/messageTemplates/add' q=\"action=update&id=`\$entity_id`&reset=1\"}{/capture}\n{/if}\n\n<span class=\"badge badge-success\"><a target=\"_blank\" href=\"{\$crmURL}\">[Fileanalyzer_FileanalyzerReference_file_analyzer_id_01.entity_table:label]</a></span>\n<span class=\"badge badge-danger\">[Fileanalyzer_FileanalyzerReference_file_analyzer_id_01.field_name]</span>\n{/if}",
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