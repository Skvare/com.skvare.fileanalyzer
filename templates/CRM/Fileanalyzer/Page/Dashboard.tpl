{*
 +--------------------------------------------------------------------+
 | CiviCRM File Analyzer Extension                                   |
 +--------------------------------------------------------------------+
 | Dashboard Template                                                 |
 +--------------------------------------------------------------------+
*}

<div class="crm-container">
  <div class="crm-block crm-content-block">

    {* Page Header *}
    <div class="file-analyzer-header">
      <div class="header-content">
        <h1 class="page-title">{ts}File Analyzer Dashboard{/ts}</h1>
        <p class="page-description">{ts}Monitor, analyze, and manage your CiviCRM file uploads{/ts}</p>
        <div class="header-actions">
          <a href="{crmURL p='civicrm/admin/setting/fileanalyzer'}" class="button">
            <i class="crm-i fa-cog"></i> {ts}Settings{/ts}
          </a>
          <button onclick="refreshData()" class="button" id="refreshBtn">
            <i class="crm-i fa-refresh"></i> {ts}Refresh{/ts}
          </button>
        </div>
      </div>
    </div>

    {* Statistics Cards *}
    <div class="file-analyzer-stats">
      <div class="stats-grid">
        <div class="stat-card">
          <div class="stat-icon">
            <i class="crm-i fa-files-o"></i>
          </div>
          <div class="stat-content">
            <div class="stat-number" id="totalFiles">{$directoryStats.totalFiles|number_format}</div>
            <div class="stat-label">{ts}Total Files{/ts}</div>
          </div>
        </div>

        <div class="stat-card">
          <div class="stat-icon">
            <i class="crm-i fa-hdd-o"></i>
          </div>
          <div class="stat-content">
            <div class="stat-number" id="totalSize">{$directoryStats.totalSize|crmMoney:0:' ':' '}</div>
            <div class="stat-label">{ts}Total Size{/ts}</div>
          </div>
        </div>

        <div class="stat-card warning">
          <div class="stat-icon">
            <i class="crm-i fa-exclamation-triangle"></i>
          </div>
          <div class="stat-content">
            <div class="stat-number" id="abandonedCount">{$abandonedFiles|@count}</div>
            <div class="stat-label">{ts}Abandoned Files{/ts}</div>
          </div>
        </div>

        <div class="stat-card danger">
          <div class="stat-icon">
            <i class="crm-i fa-trash"></i>
          </div>
          <div class="stat-content">
            <div class="stat-number" id="wastedSpace">{$totalAbandonedSize|crmMoney:0:' ':' '}</div>
            <div class="stat-label">{ts}Wasted Space{/ts}</div>
          </div>
        </div>
      </div>
    </div>

    {* Charts Section *}
    <div class="file-analyzer-charts">
      <div class="charts-container">
        <div class="chart-panel timeline-chart">
          <div class="chart-header">
            <h3>{ts}File Storage Growth Over Time{/ts}</h3>
            <div class="chart-controls">
              <select id="timelineMetric" onchange="updateTimelineChart()">
                <option value="size">{ts}Storage Size{/ts}</option>
                <option value="count">{ts}File Count{/ts}</option>
              </select>
            </div>
          </div>
          <div class="chart-body">
            <canvas id="timelineChart" width="800" height="300"></canvas>
          </div>
        </div>

        <div class="chart-panel filetype-chart">
          <div class="chart-header">
            <h3>{ts}Files by Type{/ts}</h3>
            <div class="chart-legend" id="fileTypeLegend"></div>
          </div>
          <div class="chart-body">
            <canvas id="fileTypeChart" width="400" height="400"></canvas>
          </div>
        </div>
      </div>
    </div>

    {* Abandoned Files Section *}
    <div class="file-analyzer-files">
      <div class="files-panel">
        <div class="panel-header">
          <div class="header-left">
            <h3 class="panel-title">
              {ts}Abandoned Files{/ts}
              <span class="file-count">({$abandonedFiles|@count} {ts}files{/ts})</span>
            </h3>
            <p class="panel-description">
              {ts}Files that are not linked to any CiviCRM entity and can be safely deleted{/ts}
            </p>
          </div>
          <div class="header-right">
            {if $abandonedFiles|@count > 0}
              <button class="button danger" onclick="showBulkDeleteDialog()" id="bulkDeleteBtn" style="display:none;">
                <i class="crm-i fa-trash"></i> {ts}Delete Selected{/ts}
              </button>
            {/if}
          </div>
        </div>

        <div class="panel-body">
          {if $abandonedFiles|@count == 0}
            <div class="empty-state">
              <div class="empty-icon">
                <i class="crm-i fa-check-circle"></i>
              </div>
              <h4>{ts}No Abandoned Files Found!{/ts}</h4>
              <p>{ts}All files are properly linked to CiviCRM entities.{/ts}</p>
            </div>
          {else}
            <div class="table-container">
              <table class="crm-table files-table">
                <thead>
                <tr>
                  <th width="30">
                    <input type="checkbox" id="selectAll" onchange="toggleSelectAll()" />
                  </th>
                  <th>{ts}Filename{/ts}</th>
                  <th width="80">{ts}Type{/ts}</th>
                  <th width="100">{ts}Size{/ts}</th>
                  <th width="150">{ts}Modified{/ts}</th>
                  <th width="120">{ts}Actions{/ts}</th>
                </tr>
                </thead>
                <tbody>
                {foreach from=$abandonedFiles item=file}
                  <tr class="file-row" data-filename="{$file.filename}">
                    <td>
                      <input type="checkbox" class="file-checkbox" value="{$file.filename}" onchange="updateBulkActions()" />
                    </td>
                    <td>
                      <div class="file-info">
                        <strong class="filename">{$file.filenameOnly}</strong>
                        <div class="file-path">{$file.path}</div>
                      </div>
                    </td>
                    <td>
                        <span class="file-extension ext-{$file.extension}">
                          {$file.extension|upper}
                        </span>
                    </td>
                    <td class="file-size">
                        <span class="size-bytes" data-bytes="{$file.size}">
                          {$file.size|crmMoney:0:' ':' '}
                        </span>
                    </td>
                    <td class="file-date">
                      {$file.modified|crmDate:'%B %d, %Y at %I:%M %p'}
                    </td>
                    <td class="file-actions">
                      <div class="action-buttons">
                        <button class="button small" onclick="showFileInfo('{$file.filenameOnly}')" title="{ts}View Details{/ts}">
                          <i class="crm-i fa-info"></i>
                        </button>
                        <button class="button small danger" onclick="deleteFile('{$file.filenameOnly}')" title="{ts}Delete File{/ts}">
                          <i class="crm-i fa-trash"></i>
                        </button>
                      </div>
                    </td>
                  </tr>
                {/foreach}
                </tbody>
              </table>
            </div>

            {* Bulk Actions Bar *}
            <div class="bulk-actions" id="bulkActionsBar" style="display:none;">
              <div class="bulk-info">
                <i class="crm-i fa-warning"></i>
                <span class="bulk-message">
                  <span id="selectedCount">0</span> {ts}files selected{/ts}
                </span>
              </div>
              <div class="bulk-buttons">
                <button class="button" onclick="clearSelection()">
                  {ts}Clear Selection{/ts}
                </button>
                <button class="button danger" onclick="bulkDeleteFiles()">
                  <i class="crm-i fa-trash"></i> {ts}Delete Selected{/ts}
                </button>
              </div>
            </div>
          {/if}
        </div>
      </div>
    </div>
  </div>
</div>

{* File Info Modal *}
<div id="fileInfoModal" class="ui-dialog ui-widget ui-widget-content ui-corner-all" style="display:none;">
  <div class="ui-dialog-titlebar ui-widget-header ui-corner-all ui-helper-clearfix">
    <span class="ui-dialog-title">{ts}File Information{/ts}</span>
    <button class="ui-dialog-titlebar-close ui-corner-all" onclick="closeFileInfoModal()">
      <span class="ui-icon ui-icon-closethick">{ts}close{/ts}</span>
    </button>
  </div>
  <div class="ui-dialog-content ui-widget-content" id="fileInfoContent">
    <!-- Content will be populated by JavaScript -->
  </div>
</div>

{* Initialize data for JavaScript *}
<script type="text/javascript">
  {literal}
  var FileAnalyzerData = {
    fileData: {/literal}{$fileData}{literal},
    abandonedFiles: {/literal}{$abandonedFiles|@json_encode}{literal},
    directoryStats: {/literal}{$directoryStats|@json_encode}{literal},
    ajaxUrl: '{/literal}{crmURL p="civicrm/ajax/file-analyzer" h=0}{literal}',
    confirmDeleteMsg: '{/literal}{ts escape="js"}Are you sure you want to delete this file? This action cannot be undone.{/ts}{literal}',
    confirmBulkDeleteMsg: '{/literal}{ts escape="js"}Are you sure you want to delete the selected files? This action cannot be undone.{/ts}{literal}',
    deletingMsg: '{/literal}{ts escape="js"}Deleting...{/ts}{literal}',
    deletedMsg: '{/literal}{ts escape="js"}File deleted successfully{/ts}{literal}',
    errorMsg: '{/literal}{ts escape="js"}An error occurred while deleting the file{/ts}{literal}'
  };
  {/literal}
</script>
