{*
 +--------------------------------------------------------------------+
 | CiviCRM File Analyzer Extension                                   |
 +--------------------------------------------------------------------+
 | Enhanced Dashboard Template with Preview and Export               |
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
          <a href="{crmURL p='civicrm/file-analyzer/contribute-dashboard'}" class="button">
            <i class="crm-i fa-files-o"></i> {ts}Contribute Files{/ts}
          </a>
          <a href="{crmURL p='civicrm/admin/setting/fileanalyzer'}" class="button">
            <i class="crm-i fa-cog"></i> {ts}Settings{/ts}
          </a>
          {if $canExport}
            <div class="export-dropdown">
              <button class="button export-btn" onclick="toggleExportMenu()">
                <i class="crm-i fa-download"></i> {ts}Export Data{/ts} <i class="crm-i fa-caret-down"></i>
              </button>
              <div class="export-menu" id="exportMenu" style="display:none;">
                {foreach from=$exportFormats key=format item=label}
                  <a href="{$exportUrl}&format={$format}" class="export-option">
                    <i class="crm-i fa-file-text-o"></i> {ts}Export as{/ts} {$label}
                  </a>
                {/foreach}
              </div>
            </div>
          {/if}
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
              <button class="button" onclick="previewSelectedFiles()" id="bulkPreviewBtn" style="display:none;">
                <i class="crm-i fa-eye"></i> {ts}Preview Selected{/ts}
              </button>
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
                  <th width="120">{ts}Preview{/ts}</th>
                  <th width="150">{ts}Modified{/ts}</th>
                  <th width="150">{ts}Actions{/ts}</th>
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
                    <td class="file-preview">
                      {if $file.can_preview}
                        {if $file.preview_type == 'image'}
                          <div class="preview-thumbnail" onclick="showImagePreview('{$file.preview_url}', '{$file.filenameOnly}')">
                            <i class="crm-i fa-eye"></i>
                            <span class="preview-label">{ts}Image{/ts}</span>
                          </div>
                        {elseif $file.preview_type == 'pdf'}
                          <div class="preview-thumbnail" onclick="showFilePreview('{$file.preview_url}')">
                            <i class="crm-i fa-file-pdf-o"></i>
                            <span class="preview-label">{ts}PDF{/ts}</span>
                          </div>
                        {elseif $file.preview_type == 'text'}
                          <div class="preview-thumbnail" onclick="showFilePreview('{$file.preview_url}')">
                            <i class="crm-i fa-file-text-o"></i>
                            <span class="preview-label">{ts}Text{/ts}</span>
                          </div>
                        {/if}
                      {else}
                        <span class="no-preview">
                          <i class="crm-i fa-ban"></i>
                          <span class="preview-label">{ts}N/A{/ts}</span>
                        </span>
                      {/if}
                    </td>
                    <td class="file-date">
                      {$file.modified|crmDate:'%B %d, %Y at %I:%M %p'}
                    </td>
                    <td class="file-actions">
                      <div class="action-buttons">
                        <button class="button small" onclick="showFileInfo('{$file.filenameOnly}')" title="{ts}View Details{/ts}">
                          <i class="crm-i fa-info"></i>
                        </button>
                        {if $file.can_preview}
                          <button class="button small" onclick="showFilePreview('{$file.preview_url}')" title="{ts}Preview File{/ts}">
                            <i class="crm-i fa-eye"></i>
                          </button>
                        {/if}
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
                <button class="button" onclick="previewSelectedFiles()">
                  <i class="crm-i fa-eye"></i> {ts}Preview Selected{/ts}
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

{* Enhanced File Info Modal *}
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

{* File Preview Modal *}
<div id="filePreviewModal" class="preview-modal" style="display:none;">
  <div class="preview-modal-overlay" onclick="closePreviewModal()"></div>
  <div class="preview-modal-content">
    <div class="preview-modal-header">
      <h3 id="previewModalTitle">{ts}File Preview{/ts}</h3>
      <div class="preview-modal-actions">
        <button class="button" onclick="openFullPreview()" id="fullPreviewBtn">
          <i class="crm-i fa-external-link"></i> {ts}Open Full View{/ts}
        </button>
        <button class="button" onclick="closePreviewModal()">
          <i class="crm-i fa-times"></i> {ts}Close{/ts}
        </button>
      </div>
    </div>
    <div class="preview-modal-body" id="previewModalBody">
      <!-- Preview content will be loaded here -->
    </div>
  </div>
</div>

{* Initialize enhanced data for JavaScript *}
<script type="text/javascript">
  {literal}
  var FileAnalyzerData = {
    fileData: {/literal}{$fileData}{literal},
    abandonedFiles: {/literal}{$abandonedFiles|@json_encode}{literal},
    directoryStats: {/literal}{$directoryStats|@json_encode}{literal},
    ajaxUrl: '{/literal}{$ajaxUrl}{literal}',
    previewUrl: '{/literal}{$previewUrl}{literal}',
    exportUrl: '{/literal}{$exportUrl}{literal}',
    directoryType: '{/literal}{$directoryType}{literal}',
    confirmDeleteMsg: '{/literal}{ts escape="js"}Are you sure you want to delete this file? This action cannot be undone.{/ts}{literal}',
    confirmBulkDeleteMsg: '{/literal}{ts escape="js"}Are you sure you want to delete the selected files? This action cannot be undone.{/ts}{literal}',
    deletingMsg: '{/literal}{ts escape="js"}Deleting...{/ts}{literal}',
    deletedMsg: '{/literal}{ts escape="js"}File deleted successfully{/ts}{literal}',
    errorMsg: '{/literal}{ts escape="js"}An error occurred while deleting the file{/ts}{literal}',
    previewLoadingMsg: '{/literal}{ts escape="js"}Loading preview...{/ts}{literal}',
    previewErrorMsg: '{/literal}{ts escape="js"}Unable to load preview{/ts}{literal}'
  };

  // Enhanced functions for preview and export
  window.showFilePreview = function(previewUrl) {
    var modal = document.getElementById('filePreviewModal');
    var body = document.getElementById('previewModalBody');
    var fullPreviewBtn = document.getElementById('fullPreviewBtn');

    // Set the full preview URL
    fullPreviewBtn.setAttribute('data-url', previewUrl);

    // Show modal
    modal.style.display = 'block';
    body.innerHTML = '<div class="loading"><i class="crm-i fa-spinner fa-spin"></i> ' + FileAnalyzerData.previewLoadingMsg + '</div>';

    // Load preview content
    loadPreviewContent(previewUrl, body);
  };

  window.showImagePreview = function(previewUrl, filename) {
    var modal = document.getElementById('filePreviewModal');
    var body = document.getElementById('previewModalBody');
    var title = document.getElementById('previewModalTitle');
    var fullPreviewBtn = document.getElementById('fullPreviewBtn');

    title.textContent = filename;
    fullPreviewBtn.setAttribute('data-url', previewUrl);

    modal.style.display = 'block';
    body.innerHTML = '<div class="image-preview"><img src="' + previewUrl + '" alt="' + filename + '" style="max-width: 100%; max-height: 70vh;" /></div>';
  };

  window.closePreviewModal = function() {
    document.getElementById('filePreviewModal').style.display = 'none';
  };

  window.openFullPreview = function() {
    var url = document.getElementById('fullPreviewBtn').getAttribute('data-url');
    if (url) {
      window.open(url, '_blank', 'width=1024,height=768,scrollbars=yes,resizable=yes');
    }
  };

  window.toggleExportMenu = function() {
    var menu = document.getElementById('exportMenu');
    menu.style.display = menu.style.display === 'none' ? 'block' : 'none';
  };

  window.previewSelectedFiles = function() {
    var selectedFiles = FileAnalyzer.selectedFiles;
    if (selectedFiles.length === 0) {
      FileAnalyzer.showNotification('Please select files to preview', 'error');
      return;
    }

    // Create a preview gallery for selected files
    var modal = document.getElementById('filePreviewModal');
    var body = document.getElementById('previewModalBody');
    var title = document.getElementById('previewModalTitle');

    title.textContent = 'Selected Files Preview (' + selectedFiles.length + ' files)';
    modal.style.display = 'block';

    var galleryHtml = '<div class="preview-gallery">';
    selectedFiles.forEach(function(filename) {
      var file = FileAnalyzerData.abandonedFiles.find(function(f) {
        return f.filename === filename;
      });

      if (file && file.can_preview) {
        galleryHtml += '<div class="gallery-item" onclick="showFilePreview(\'' + file.preview_url + '\')">';
        galleryHtml += '<div class="gallery-thumbnail">';

        if (file.preview_type === 'image') {
          galleryHtml += '<img src="' + file.preview_url + '" alt="' + file.filenameOnly + '" />';
        } else {
          galleryHtml += '<i class="crm-i fa-file-o"></i>';
        }

        galleryHtml += '</div>';
        galleryHtml += '<div class="gallery-filename">' + file.filenameOnly + '</div>';
        galleryHtml += '</div>';
      }
    });
    galleryHtml += '</div>';

    body.innerHTML = galleryHtml;
  };

  function loadPreviewContent(url, container) {
    // This would typically use iframe or AJAX to load content
    container.innerHTML = '<iframe src="' + url + '" style="width: 100%; height: 400px; border: none;"></iframe>';
  }

  // Close modal when clicking outside
  document.addEventListener('click', function(e) {
    if (e.target.classList.contains('preview-modal-overlay')) {
      closePreviewModal();
    }
  });

  // Keyboard shortcuts
  document.addEventListener('keydown', function(e) {
    if (e.keyCode === 27) { // Escape key
      closePreviewModal();
      closeFileInfoModal();
    }
  });

  // Close export menu when clicking outside
  document.addEventListener('click', function(e) {
    if (!e.target.closest('.export-dropdown')) {
      var menu = document.getElementById('exportMenu');
      if (menu) {
        menu.style.display = 'none';
      }
    }
  });
  {/literal}
</script>
