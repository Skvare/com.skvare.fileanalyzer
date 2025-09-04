{*
 +--------------------------------------------------------------------+
 | CiviCRM File Analyzer Extension                                   |
 +--------------------------------------------------------------------+
 | Contribute Images Dashboard Template                               |
 +--------------------------------------------------------------------+
*}

<div class="crm-container">
  <div class="crm-block crm-content-block">

    {* Page Header *}
    <div class="file-analyzer-header" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
      <div class="header-content">
        <h1 class="page-title">{ts}Contribute Images Dashboard{/ts}</h1>
        <p class="page-description">{ts}Monitor, analyze, and manage your CiviCRM contribute page images{/ts}</p>
        <div class="header-actions">
          <a href="{crmURL p='civicrm/file-analyzer/dashboard'}" class="button">
            <i class="crm-i fa-files-o"></i> {ts}Custom Files{/ts}
          </a>
          <a href="{crmURL p='civicrm/admin/setting/fileanalyzer'}" class="button">
            <i class="crm-i fa-cog"></i> {ts}Settings{/ts}
          </a>
          <button onclick="refreshData()" class="button" id="refreshBtn">
            <i class="crm-i fa-refresh"></i> {ts}Refresh{/ts}
          </button>
        </div>
      </div>
    </div>

    {* Directory Info Banner *}
    <div class="file-analyzer-stats">
      <div class="stats-grid">
        <div class="stat-card">
          <div class="stat-icon">
            <i class="crm-i fa-folder-open"></i>
          </div>
          <div class="stat-content">
            <div class="stat-number">{ts}Contribute Images{/ts}</div>
            <div class="stat-label">{ts}Directory Type{/ts}</div>
          </div>
        </div>

        <div class="stat-card">
          <div class="stat-icon">
            <i class="crm-i fa-map-marker"></i>
          </div>
          <div class="stat-content">
            <div class="stat-number" style="font-size: 0.8rem; word-break: break-all;">{$directoryPath}</div>
            <div class="stat-label">{ts}Directory Path{/ts}</div>
          </div>
        </div>

        <div class="stat-card">
          <div class="stat-icon">
            <i class="crm-i fa-clock-o"></i>
          </div>
          <div class="stat-content">
            <div class="stat-number">{if $lastScanDate}{$lastScanDate|crmDate:'%B %d, %Y at %I:%M %p'}{else}{ts}Never{/ts}{/if}</div>
            <div class="stat-label">{ts}Last Scan{/ts}</div>
          </div>
        </div>

        <div class="stat-card">
          <div class="stat-icon">
            <i class="crm-i fa-info-circle"></i>
          </div>
          <div class="stat-content">
            <div class="stat-number">{ts}Contribute Pages{/ts}</div>
            <div class="stat-label">{ts}Used In{/ts}</div>
          </div>
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
            <div class="stat-label">{ts}Total Images{/ts}</div>
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
            <div class="stat-label">{ts}Orphaned Images{/ts}</div>
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
            <h3>{ts}Image Storage Growth Over Time{/ts}</h3>
            <div class="chart-controls">
              <select id="timelineMetric" onchange="updateTimelineChart()">
                <option value="size">{ts}Storage Size{/ts}</option>
                <option value="count">{ts}Image Count{/ts}</option>
              </select>
            </div>
          </div>
          <div class="chart-body">
            <canvas id="timelineChart" width="800" height="300"></canvas>
          </div>
        </div>

        <div class="chart-panel filetype-chart">
          <div class="chart-header">
            <h3>{ts}Images by Type{/ts}</h3>
            <div class="chart-legend" id="fileTypeLegend"></div>
          </div>
          <div class="chart-body">
            <canvas id="fileTypeChart" width="400" height="400"></canvas>
          </div>
        </div>
      </div>
    </div>

    {* Orphaned Images Section *}
    <div class="file-analyzer-files">
      <div class="files-panel">
        <div class="panel-header">
          <div class="header-left">
            <h3 class="panel-title">
              {ts}Orphaned Images{/ts}
              <span class="file-count">({$abandonedFiles|@count} {ts}images{/ts})</span>
            </h3>
            <p class="panel-description">
              {ts}Images that are not referenced in any contribute page content and can be safely deleted{/ts}
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
              <h4>{ts}No Orphaned Images Found!{/ts}</h4>
              <p>{ts}All images are properly referenced in contribute page content.{/ts}</p>
            </div>
          {else}
            <div class="table-container">
              <table class="crm-table files-table">
                <thead>
                <tr>
                  <th width="30">
                    <input type="checkbox" id="selectAll" onchange="toggleSelectAll()" />
                  </th>
                  <th>{ts}Image Name{/ts}</th>
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
                        <button class="button small danger" onclick="deleteFile('{$file.filenameOnly}')" title="{ts}Delete Image{/ts}">
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
                  <span id="selectedCount">0</span> {ts}images selected{/ts}
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

    {* Additional Info Section *}
    <div class="file-analyzer-files">
      <div class="files-panel">
        <div class="panel-header">
          <div class="header-left">
            <h3 class="panel-title">{ts}About Contribute Images{/ts}</h3>
            <p class="panel-description">
              {ts}Understanding how contribute page images work in CiviCRM{/ts}
            </p>
          </div>
        </div>
        <div class="panel-body">
          <div style="padding: 2rem;">
            <div class="info-grid" style="display: grid; grid-template-columns: 1fr 1fr; gap: 2rem;">
              <div>
                <h4 style="color: #1e293b; margin-bottom: 1rem;">{ts}Image Usage{/ts}</h4>
                <ul style="color: #64748b; line-height: 1.6;">
                  <li>{ts}Images are typically referenced in contribute page header and footer content{/ts}</li>
                  <li>{ts}Images may be embedded using HTML img tags or CKEditor{/ts}</li>
                  <li>{ts}Orphaned images are those not found in any contribution page content{/ts}</li>
                  <li>{ts}Safe to delete orphaned images that are no longer needed{/ts}</li>
                </ul>
              </div>
              <div>
                <h4 style="color: #1e293b; margin-bottom: 1rem;">{ts}Best Practices{/ts}</h4>
                <ul style="color: #64748b; line-height: 1.6;">
                  <li>{ts}Regularly clean up unused images to save disk space{/ts}</li>
                  <li>{ts}Use optimized image formats (WebP, compressed JPEG/PNG){/ts}</li>
                  <li>{ts}Keep image file names descriptive and organized{/ts}</li>
                  <li>{ts}Test contribute pages after deleting images to ensure no broken links{/ts}</li>
                </ul>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

{* File Info Modal - Reuse the same modal structure *}
<div id="fileInfoModal" class="ui-dialog ui-widget ui-widget-content ui-corner-all" style="display:none;">
  <div class="ui-dialog-titlebar ui-widget-header ui-corner-all ui-helper-clearfix">
    <span class="ui-dialog-title">{ts}Image Information{/ts}</span>
    <button class="ui-dialog-titlebar-close ui-corner-all" onclick="closeFileInfoModal()">
      <span class="ui-icon ui-icon-closethick">{ts}close{/ts}</span>
    </button>
  </div>
  <div class="ui-dialog-content ui-widget-content" id="fileInfoContent">
    <!-- Content will be populated by JavaScript -->
  </div>
</div>

{* Initialize data for JavaScript - Reuse the same structure *}
<script type="text/javascript">
  {literal}
  var FileAnalyzerData = {
    fileData: {/literal}{$fileData}{literal},
    abandonedFiles: {/literal}{$abandonedFiles|@json_encode}{literal},
    directoryStats: {/literal}{$directoryStats|@json_encode}{literal},
    ajaxUrl: '{/literal}{crmURL p="civicrm/ajax/file-analyzer" h=0}{literal}',
    directoryType: '{/literal}{$directoryType}{literal}',
    confirmDeleteMsg: '{/literal}{ts escape="js"}Are you sure you want to delete this image? This action cannot be undone.{/ts}{literal}',
    confirmBulkDeleteMsg: '{/literal}{ts escape="js"}Are you sure you want to delete the selected images? This action cannot be undone.{/ts}{literal}',
    deletingMsg: '{/literal}{ts escape="js"}Deleting...{/ts}{literal}',
    deletedMsg: '{/literal}{ts escape="js"}Image deleted successfully{/ts}{literal}',
    errorMsg: '{/literal}{ts escape="js"}An error occurred while deleting the image{/ts}{literal}'
  };
  {/literal}
</script>
