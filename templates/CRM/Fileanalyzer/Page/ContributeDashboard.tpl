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
    <div class="file-analyzer-header">
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
      <div class="panel-header">
      <p class="page-description">
        {ts}Last Scan{/ts}: {if $lastScanDate}{$lastScanDate|crmDate:'%B %d, %Y at %I:%M %p'}{else}{ts}Never{/ts}{/if}
      </p>
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
                          <div class="preview-thumbnail image-thumb" onclick="showImagePreview('{$file.preview_url}', '{$file.filenameOnly}')" data-preview-url="{$file.preview_url}">
                            {if false && $file.thumbnail_url}
                              <img src="{$file.thumbnail_url}" alt="{$file.filenameOnly}" class="thumb-img" />
                            {else}
                              <i class="crm-i fa-image"></i>
                            {/if}
                            <span class="preview-label">{ts}Image{/ts}</span>
                          </div>
                        {elseif $file.preview_type == 'pdf'}
                          <div class="preview-thumbnail" onclick="showFilePreview('{$file.preview_url}', '{$file.filenameOnly}')">
                            <i class="crm-i fa-file-pdf-o"></i>
                            <span class="preview-label">{ts}PDF{/ts}</span>
                          </div>
                        {else}
                          <div class="preview-thumbnail" onclick="showFilePreview('{$file.preview_url}', '{$file.filenameOnly}')">
                            <i class="crm-i fa-file-o"></i>
                            <span class="preview-label">{ts}File{/ts}</span>
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
                          <button class="button small" onclick="showFilePreview('{$file.preview_url}', '{$file.filenameOnly}')" title="{ts}Preview Image{/ts}">
                            <i class="crm-i fa-eye"></i>
                          </button>
                        {/if}
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

            <div style="margin-top: 2rem; padding-top: 2rem; border-top: 1px solid #e2e8f0;">
              <h4 style="color: #1e293b; margin-bottom: 1rem;">{ts}Preview and Management Features{/ts}</h4>
              <div class="feature-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 1rem;">
                <div class="feature-item" style="background: #f8f9fa; padding: 1rem; border-radius: 6px; border: 1px solid #dee2e6;">
                  <h5 style="color: #495057; margin-bottom: 0.5rem;"><i class="crm-i fa-eye"></i> {ts}Image Preview{/ts}</h5>
                  <p style="color: #6c757d; font-size: 0.875rem; margin: 0;">{ts}Click on any image thumbnail to preview it in full size with metadata{/ts}</p>
                </div>
                <div class="feature-item" style="background: #f8f9fa; padding: 1rem; border-radius: 6px; border: 1px solid #dee2e6;">
                  <h5 style="color: #495057; margin-bottom: 0.5rem;"><i class="crm-i fa-download"></i> {ts}Bulk Export{/ts}</h5>
                  <p style="color: #6c757d; font-size: 0.875rem; margin: 0;">{ts}Export analysis data in CSV or JSON format for reporting{/ts}</p>
                </div>
                <div class="feature-item" style="background: #f8f9fa; padding: 1rem; border-radius: 6px; border: 1px solid #dee2e6;">
                  <h5 style="color: #495057; margin-bottom: 0.5rem;"><i class="crm-i fa-trash"></i> {ts}Safe Deletion{/ts}</h5>
                  <p style="color: #6c757d; font-size: 0.875rem; margin: 0;">{ts}Only orphaned images can be deleted, preventing accidental removal of active content{/ts}</p>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

{* Enhanced File Info Modal *}
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

{* Enhanced File Preview Modal *}
<div id="filePreviewModal" class="preview-modal" style="display:none;">
  <div class="preview-modal-overlay" onclick="closePreviewModal()"></div>
  <div class="preview-modal-content">
    <div class="preview-modal-header">
      <h3 id="previewModalTitle">{ts}Image Preview{/ts}</h3>
      <div class="preview-modal-actions">
        <button class="button" onclick="openFullPreview()" id="fullPreviewBtn">
          <i class="crm-i fa-external-link"></i> {ts}Open Full View{/ts}
        </button>
        <button class="button" onclick="downloadCurrentFile()" id="downloadBtn">
          <i class="crm-i fa-download"></i> {ts}Download{/ts}
        </button>
        <button class="button" onclick="closePreviewModal()">
          <i class="crm-i fa-times"></i> {ts}Close{/ts}
        </button>
      </div>
    </div>
    <div class="preview-modal-body" id="previewModalBody">
      <!-- Preview content will be loaded here -->
    </div>
    <div class="preview-modal-info" id="previewModalInfo" style="display:none;">
      <!-- Image metadata will be shown here -->
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
    confirmDeleteMsg: '{/literal}{ts escape="js"}Are you sure you want to delete this image? This action cannot be undone.{/ts}{literal}',
    confirmBulkDeleteMsg: '{/literal}{ts escape="js"}Are you sure you want to delete the selected images? This action cannot be undone.{/ts}{literal}',
    deletingMsg: '{/literal}{ts escape="js"}Deleting...{/ts}{literal}',
    deletedMsg: '{/literal}{ts escape="js"}Image deleted successfully{/ts}{literal}',
    errorMsg: '{/literal}{ts escape="js"}An error occurred while deleting the image{/ts}{literal}',
    previewLoadingMsg: '{/literal}{ts escape="js"}Loading preview...{/ts}{literal}',
    previewErrorMsg: '{/literal}{ts escape="js"}Unable to load preview{/ts}{literal}',
    downloadingMsg: '{/literal}{ts escape="js"}Preparing download...{/ts}{literal}'
  };

  // Enhanced functions specific to contribute images
  window.showImagePreview = function(previewUrl, filename) {
    var modal = document.getElementById('filePreviewModal');
    var body = document.getElementById('previewModalBody');
    var title = document.getElementById('previewModalTitle');
    var fullPreviewBtn = document.getElementById('fullPreviewBtn');
    var downloadBtn = document.getElementById('downloadBtn');
    var infoDiv = document.getElementById('previewModalInfo');

    title.textContent = filename;
    fullPreviewBtn.setAttribute('data-url', previewUrl);
    downloadBtn.setAttribute('data-filename', filename);

    modal.style.display = 'block';
    body.innerHTML = '<div class="loading"><i class="crm-i fa-spinner fa-spin"></i> ' + FileAnalyzerData.previewLoadingMsg + '</div>';

    // Load image with enhanced presentation
    var img = new Image();
    img.onload = function() {
      var aspectRatio = this.naturalWidth / this.naturalHeight;
      var maxWidth = Math.min(800, window.innerWidth * 0.8);
      var maxHeight = Math.min(600, window.innerHeight * 0.6);

      var displayWidth = maxWidth;
      var displayHeight = maxWidth / aspectRatio;

      if (displayHeight > maxHeight) {
        displayHeight = maxHeight;
        displayWidth = maxHeight * aspectRatio;
      }

      body.innerHTML =
        '<div class="image-preview-enhanced">' +
        '<img src="' + previewUrl + '" alt="' + filename + '" ' +
        'style="max-width: 100%; max-height: 70vh; border-radius: 4px; box-shadow: 0 4px 12px rgba(0,0,0,0.15);" />' +
        '</div>';

      // Show metadata
      infoDiv.innerHTML =
        '<div class="image-metadata">' +
        '<span class="meta-item"><strong>Dimensions:</strong> ' + this.naturalWidth + ' × ' + this.naturalHeight + ' pixels</span>' +
        '<span class="meta-item"><strong>Aspect Ratio:</strong> ' + aspectRatio.toFixed(2) + ':1</span>' +
        '<span class="meta-item"><strong>File:</strong> ' + filename + '</span>' +
        '</div>';
      infoDiv.style.display = 'block';
    };

    img.onerror = function() {
      body.innerHTML = '<div class="preview-error"><i class="crm-i fa-exclamation-triangle"></i> ' + FileAnalyzerData.previewErrorMsg + '</div>';
    };

    img.src = previewUrl;
  };

  window.downloadCurrentFile = function() {
    var filename = document.getElementById('downloadBtn').getAttribute('data-filename');
    if (filename) {
      // Find the file in our data
      var file = FileAnalyzerData.abandonedFiles.find(function(f) {
        return f.filenameOnly === filename;
      });

      if (file && file.preview_url) {
        var link = document.createElement('a');
        link.href = file.preview_url;
        link.download = filename;
        link.target = '_blank';
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);

        FileAnalyzer.showNotification(FileAnalyzerData.downloadingMsg, 'info');
      }
    }
  };

  // Enhanced image gallery for bulk preview
  window.previewSelectedFiles = function() {
    var selectedFiles = FileAnalyzer.selectedFiles;
    if (selectedFiles.length === 0) {
      FileAnalyzer.showNotification('Please select images to preview', 'error');
      return;
    }

    var modal = document.getElementById('filePreviewModal');
    var body = document.getElementById('previewModalBody');
    var title = document.getElementById('previewModalTitle');
    var infoDiv = document.getElementById('previewModalInfo');

    title.textContent = 'Selected Images Gallery (' + selectedFiles.length + ' images)';
    modal.style.display = 'block';
    infoDiv.style.display = 'none';

    var galleryHtml = '<div class="preview-gallery contribute-gallery">';
    var imageCount = 0;

    selectedFiles.forEach(function(filename) {
      var file = FileAnalyzerData.abandonedFiles.find(function(f) {
        return f.filename === filename;
      });

      if (file && file.can_preview && file.preview_type === 'image') {
        imageCount++;
        galleryHtml += '<div class="gallery-item contribute-item" onclick="showImagePreview(\'' + file.preview_url + '\', \'' + file.filenameOnly + '\')">';
        galleryHtml += '<div class="gallery-thumbnail">';

        if (file.thumbnail_url) {
          galleryHtml += '<img src="' + file.thumbnail_url + '" alt="' + file.filenameOnly + '" />';
        } else {
          galleryHtml += '<img src="' + file.preview_url + '" alt="' + file.filenameOnly + '" />';
        }

        galleryHtml += '</div>';
        galleryHtml += '<div class="gallery-filename">' + file.filenameOnly + '</div>';
        galleryHtml += '<div class="gallery-size">' + FileAnalyzer.formatBytes(file.size) + '</div>';
        galleryHtml += '</div>';
      }
    });

    if (imageCount === 0) {
      galleryHtml += '<div class="no-images"><i class="crm-i fa-info-circle"></i> No previewable images in selection</div>';
    }

    galleryHtml += '</div>';
    body.innerHTML = galleryHtml;
  };
  {/literal}
</script>

{* Additional CSS for contribute-specific enhancements *}
<style>
  {literal}
  .image-thumb {
    position: relative;
    overflow: hidden;
  }

  .image-thumb .thumb-img {
    width: 40px;
    height: 30px;
    object-fit: cover;
    border-radius: 3px;
    border: 1px solid #dee2e6;
  }

  .preview-thumbnail.image-thumb:hover .thumb-img {
    transform: scale(1.1);
    transition: transform 0.2s ease;
  }

  .preview-modal-info {
    padding: 1rem 1.5rem;
    background: #f8f9fa;
    border-top: 1px solid #dee2e6;
  }

  .image-metadata {
    display: flex;
    flex-wrap: wrap;
    gap: 1rem;
    font-size: 0.875rem;
    color: #6c757d;
  }

  .meta-item {
    white-space: nowrap;
  }

  .contribute-gallery {
    grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
    gap: 1.5rem;
  }

  .contribute-item {
    background: #fff;
    border: 2px solid #dee2e6;
    transition: all 0.3s ease;
  }

  .contribute-item:hover {
    border-color: #764ba2;
    box-shadow: 0 4px 12px rgba(118, 75, 162, 0.15);
    transform: translateY(-3px);
  }

  .contribute-item .gallery-thumbnail {
    height: 120px;
    background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
  }

  .contribute-item .gallery-thumbnail img {
    border-radius: 4px;
    border: 1px solid #dee2e6;
  }

  .gallery-size {
    font-size: 0.75rem;
    color: #6c757d;
    text-align: center;
    margin-top: 0.25rem;
  }

  .no-images {
    text-align: center;
    padding: 3rem;
    color: #6c757d;
    grid-column: 1 / -1;
  }

  .image-preview-enhanced {
    text-align: center;
    padding: 1rem;
  }

  .preview-error {
    text-align: center;
    padding: 3rem;
    color: #dc3545;
    font-size: 1.1rem;
  }

  .feature-grid .feature-item:hover {
    transform: translateY(-2px);
    transition: transform 0.2s ease;
    box-shadow: 0 4px 8px rgba(0,0,0,0.1);
  }

  /* Contribute-specific color scheme */

  .stat-card {
    border-left-color: #764ba2;
  }

  .stat-card.warning {
    border-left-color: #f59e0b;
  }

  .stat-card.danger {
    border-left-color: #ef4444;
  }

  /* Enhanced export dropdown for contribute dashboard */
  .export-dropdown .export-option {
    transition: all 0.2s ease;
  }

  .export-dropdown .export-option:hover {
    background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
    transform: translateX(3px);
  }

  /* Enhanced button styles for contribute theme */
  .button {
    transition: all 0.2s ease;
  }

  .button:hover {
    transform: translateY(-1px);
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
  }

  .button.danger:hover {
    box-shadow: 0 2px 8px rgba(220, 53, 69, 0.3);
  }

  /* Loading animation enhancement */
  .loading {
    text-align: center;
    padding: 3rem;
    color: #764ba2;
  }

  .loading .fa-spinner {
    font-size: 2rem;
    margin-bottom: 1rem;
  }

  /* Responsive enhancements for contribute dashboard */
  @media (max-width: 768px) {
    .contribute-gallery {
      grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
      gap: 1rem;
    }

    .contribute-item .gallery-thumbnail {
      height: 100px;
    }

    .image-metadata {
      flex-direction: column;
      gap: 0.5rem;
    }

    .export-dropdown {
      width: 100%;
    }

    .export-menu {
      right: 0;
      left: 0;
      width: auto;
    }
  }

  /* Enhanced accessibility */
  .preview-thumbnail:focus,
  .gallery-item:focus {
    outline: 2px solid #764ba2;
    outline-offset: 2px;
  }

  .button:focus {
    outline: 2px solid #667eea;
    outline-offset: 2px;
  }

  /* Enhanced tooltips */
  .action-buttons .button[title]:hover::after {
    content: attr(title);
    position: absolute;
    bottom: -2rem;
    left: 50%;
    transform: translateX(-50%);
    background: #333;
    color: white;
    padding: 0.25rem 0.5rem;
    border-radius: 3px;
    font-size: 0.75rem;
    white-space: nowrap;
    z-index: 1000;
  }

  .action-buttons .button {
    position: relative;
  }

  /* Enhanced table styling for contribute images */
  .files-table tbody tr:hover {
    background: linear-gradient(135deg, #f8f9fa 0%, rgba(118, 75, 162, 0.05) 100%);
  }

  .files-table .file-extension {
    font-weight: 600;
    text-transform: uppercase;
    font-size: 0.75rem;
  }

  .ext-jpg, .ext-jpeg {
    background: linear-gradient(135deg, #dbeafe 0%, #bfdbfe 100%);
    color: #1d4ed8;
  }

  .ext-png {
    background: linear-gradient(135deg, #dcfce7 0%, #bbf7d0 100%);
    color: #059669;
  }

  .ext-gif {
    background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%);
    color: #d97706;
  }

  .ext-webp {
    background: linear-gradient(135deg, #e0e7ff 0%, #c7d2fe 100%);
    color: #6366f1;
  }

  .ext-svg {
    background: linear-gradient(135deg, #fce7f3 0%, #fbcfe8 100%);
    color: #be185d;
  }

  /* Enhanced modal animations */
  .preview-modal {
    animation: fadeIn 0.3s ease;
  }

  @keyframes fadeIn {
    from { opacity: 0; }
    to { opacity: 1; }
  }

  .preview-modal-content {
    animation: slideIn 0.3s ease;
  }

  @keyframes slideIn {
    from {
      transform: translate(-50%, -60%);
      opacity: 0;
    }
    to {
      transform: translate(-50%, -50%);
      opacity: 1;
    }
  }

  /* Enhanced stats cards with hover effects */
  .stat-card {
    transition: all 0.3s ease;
    cursor: pointer;
  }

  .stat-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 8px 25px rgba(0,0,0,0.1);
  }

  .stat-card:hover .stat-icon {
    transform: scale(1.1);
    transition: transform 0.2s ease;
  }

  /* Enhanced chart containers */
  .chart-panel {
    transition: all 0.3s ease;
  }

  .chart-panel:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(0,0,0,0.08);
  }

  /* Enhanced empty state */
  .empty-state {
    background: linear-gradient(135deg, #f0fdf4 0%, #ecfdf5 100%);
    border: 2px dashed #22c55e;
    border-radius: 12px;
    margin: 2rem;
    transition: all 0.3s ease;
  }

  .empty-state:hover {
    background: linear-gradient(135deg, #ecfdf5 0%, #d1fae5 100%);
    border-color: #16a34a;
  }

  .empty-icon {
    font-size: 4rem;
    color: #22c55e;
    margin-bottom: 1.5rem;
    animation: pulse 2s infinite;
  }

  @keyframes pulse {
    0%, 100% { opacity: 1; }
    50% { opacity: 0.7; }
  }

  /* Enhanced info section styling */
  .info-grid h4 {
    background: linear-gradient(135deg, #667eea, #764ba2);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
    font-weight: 700;
  }

  .info-grid ul li {
    padding: 0.25rem 0;
    border-left: 3px solid transparent;
    padding-left: 1rem;
    transition: all 0.2s ease;
  }

  .info-grid ul li:hover {
    border-left-color: #764ba2;
    background: rgba(118, 75, 162, 0.05);
    transform: translateX(5px);
  }
  {/literal}
</style>
