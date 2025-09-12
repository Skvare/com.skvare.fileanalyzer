{*
 +--------------------------------------------------------------------+
 | CiviCRM File Analyzer Extension                                   |
 +--------------------------------------------------------------------+
 | Enhanced File Preview Template with Multi-Directory Support       |
 +--------------------------------------------------------------------+
*}

<div class="crm-container">
  <div class="crm-block crm-content-block crm-fileanalyzer-preview">

    {* Breadcrumb Navigation *}
    {if $breadcrumb}
      <div class="crm-breadcrumb">
        <ol class="breadcrumb">
          {foreach from=$breadcrumb item=crumb}
            <li class="breadcrumb-item">
              {if $crumb.url}
                <a href="{$crumb.url}">{$crumb.title}</a>
              {else}
                <span class="current">{$crumb.title}</span>
              {/if}
            </li>
          {/foreach}
        </ol>
      </div>
    {/if}

    {* Header with Actions *}
    <div class="preview-header">
      <div class="header-content">
        <div class="header-left">
          <h2 class="preview-title">
            <i class="crm-i {if $fileInfo.is_image}fa-image{elseif $fileInfo.is_pdf}fa-file-pdf-o{elseif $fileInfo.is_text}fa-file-text-o{elseif $fileInfo.is_video}fa-file-video-o{elseif $fileInfo.is_audio}fa-file-audio-o{else}fa-file-o{/if}"></i>
            {$fileInfo.name}
          </h2>
          <div class="file-meta">
            <span class="file-size">{$fileInfo.size_formatted}</span>
            <span class="file-type">{$fileInfo.extension|upper}</span>
            <span class="directory-type">
              {if $directoryType == 'contribute'}
                <i class="crm-i fa-heart"></i> {ts}Contribute Images{/ts}
              {else}
                <i class="crm-i fa-files-o"></i> {ts}Custom Files{/ts}
              {/if}
            </span>
            {if $fileInfo.is_referenced}
              <span class="reference-status referenced">
                <i class="crm-i fa-link"></i> {ts}Referenced{/ts}
              </span>
            {else}
              <span class="reference-status orphaned">
                <i class="crm-i fa-unlink"></i> {ts}Orphaned{/ts}
              </span>
            {/if}
          </div>
        </div>
        <div class="header-actions">
          <a href="{$fileInfo.download_url}" class="button" target="_blank">
            <i class="crm-i fa-download"></i> {ts}Download{/ts}
          </a>
          {if $fileInfo.is_image}
            <button onclick="openImageInNewTab()" class="button">
              <i class="crm-i fa-external-link"></i> {ts}Open Original{/ts}
            </button>
          {/if}
          <button onclick="window.close()" class="button">
            <i class="crm-i fa-times"></i> {ts}Close{/ts}
          </button>
        </div>
      </div>
    </div>

    {* Main Content Area *}
    <div class="preview-main">
      <div class="preview-content">
        {if $previewType == 'image'}
          {$previewContent}
        {elseif $previewType == 'pdf'}
          {$previewContent}
        {elseif $previewType == 'text'}
          {$previewContent}
        {elseif $previewType == 'video'}
          {$previewContent}
        {elseif $previewType == 'audio'}
          {$previewContent}
        {else}
          {$previewContent}
        {/if}
      </div>

      {* File Information Sidebar *}
      <div class="preview-sidebar">
        <div class="info-panel">
          <h3>{ts}File Information{/ts}</h3>
          <div class="info-grid">
            <div class="info-row">
              <label>{ts}Name:{/ts}</label>
              <span class="value filename">{$fileInfo.name}</span>
            </div>
            <div class="info-row">
              <label>{ts}Size:{/ts}</label>
              <span class="value">{$fileInfo.size_formatted} ({$fileInfo.size|number_format} bytes)</span>
            </div>
            <div class="info-row">
              <label>{ts}Type:{/ts}</label>
              <span class="value">{$fileInfo.mime_type}</span>
            </div>
            <div class="info-row">
              <label>{ts}Modified:{/ts}</label>
              <span class="value">{$fileInfo.modified|crmDate:'%B %d, %Y at %I:%M %p'}</span>
            </div>
            <div class="info-row">
              <label>{ts}Created:{/ts}</label>
              <span class="value">{$fileInfo.created|crmDate:'%B %d, %Y at %I:%M %p'}</span>
            </div>
            {if $fileInfo.dimensions}
              <div class="info-row">
                <label>{ts}Dimensions:{/ts}</label>
                <span class="value">{$fileInfo.dimensions}</span>
              </div>
            {/if}
            {if $fileInfo.line_count}
              <div class="info-row">
                <label>{ts}Lines:{/ts}</label>
                <span class="value">{$fileInfo.line_count|number_format}</span>
              </div>
            {/if}
            {if $fileInfo.encoding}
              <div class="info-row">
                <label>{ts}Encoding:{/ts}</label>
                <span class="value">{$fileInfo.encoding}</span>
              </div>
            {/if}
          </div>
        </div>

        {* Directory Information *}
        <div class="info-panel">
          <h3>{ts}Directory Information{/ts}</h3>
          <div class="directory-info">
            <h4>{$fileInfo.directory_info.type}</h4>
            <p class="description">{$fileInfo.directory_info.description}</p>
            <div class="info-grid">
              <div class="info-row">
                <label>{ts}Path:{/ts}</label>
                <span class="value path">{$fileInfo.directory_info.base_path}</span>
              </div>
              <div class="info-row">
                <label>{ts}Web Access:{/ts}</label>
                <span class="value">
                  {if $fileInfo.directory_info.web_accessible}
                    <i class="crm-i fa-check text-success"></i> {ts}Yes{/ts}
                  {else}
                    <i class="crm-i fa-times text-danger"></i> {ts}No{/ts}
                  {/if}
                </span>
              </div>
              <div class="info-row">
                <label>{ts}Typical Use:{/ts}</label>
                <span class="value">{$fileInfo.directory_info.typical_use}</span>
              </div>
              <div class="info-row">
                <label>{ts}Common Types:{/ts}</label>
                <span class="value">{$fileInfo.directory_info.common_file_types}</span>
              </div>
            </div>
          </div>
        </div>

        {* Reference Information *}
        <div class="info-panel">
          <h3>{ts}Database References{/ts}</h3>
          {if $fileInfo.is_referenced && $fileInfo.reference_info}
            <div class="reference-info">
              <div class="reference-status-good">
                <i class="crm-i fa-check-circle"></i>
                <strong>{ts}File is referenced{/ts}</strong>
                <p>{ts count=$fileInfo.reference_info|@count plural="Found %count references in the database."}Found %count reference in the database.{/ts}</p>
              </div>

              <div class="reference-details">
                {foreach from=$fileInfo.reference_info item=ref}
                  <div class="reference-item">
                    {if $ref.type == 'file_record'}
                      <div class="ref-header">
                        <i class="crm-i fa-file"></i>
                        <strong>{ts}File Record{/ts} #{$ref.id}</strong>
                      </div>
                      <div class="ref-details">
                        <p><strong>{ts}URI:{/ts}</strong> {$ref.uri}</p>
                        {if $ref.description}
                          <p><strong>{ts}Description:{/ts}</strong> {$ref.description}</p>
                        {/if}
                        {if $ref.upload_date}
                          <p><strong>{ts}Upload Date:{/ts}</strong> {$ref.upload_date|crmDate}</p>
                        {/if}
                        {if $ref.entities}
                          <div class="entity-list">
                            <strong>{ts}Used by:{/ts}</strong>
                            <ul>
                              {foreach from=$ref.entities item=entity}
                                <li>{$entity.table} (ID: {$entity.id})</li>
                              {/foreach}
                            </ul>
                          </div>
                        {/if}
                      </div>
                    {elseif $ref.type == 'contribution_page'}
                      <div class="ref-header">
                        <i class="crm-i fa-heart"></i>
                        <strong>{ts}Contribution Page{/ts}</strong>
                      </div>
                      <div class="ref-details">
                        <p><strong>{ts}Title:{/ts}</strong> {$ref.title}</p>
                        <p><strong>{ts}Page ID:{/ts}</strong> {$ref.id}</p>
                        {if $ref.found_in}
                          <p><strong>{ts}Found in:{/ts}</strong> {$ref.found_in|@implode:', '}</p>
                        {/if}
                        <p>
                          <a href="{crmURL p='civicrm/admin/contribute/settings' q="reset=1&action=update&id=`$ref.id`"}" target="_blank" class="button small">
                            <i class="crm-i fa-edit"></i> {ts}Edit Page{/ts}
                          </a>
                        </p>
                      </div>
                    {elseif $ref.type == 'message_template'}
                      <div class="ref-header">
                        <i class="crm-i fa-envelope"></i>
                        <strong>{ts}Message Template{/ts}</strong>
                      </div>
                      <div class="ref-details">
                        <p><strong>{ts}Title:{/ts}</strong> {$ref.title}</p>
                        <p><strong>{ts}Template ID:{/ts}</strong> {$ref.id}</p>
                        {if $ref.subject}
                          <p><strong>{ts}Subject:{/ts}</strong> {$ref.subject}</p>
                        {/if}
                        <p>
                          <a href="{crmURL p='civicrm/admin/messageTemplates' q="reset=1&action=update&id=`$ref.id`"}" target="_blank" class="button small">
                            <i class="crm-i fa-edit"></i> {ts}Edit Template{/ts}
                          </a>
                        </p>
                      </div>
                    {/if}
                  </div>
                {/foreach}
              </div>
            </div>
          {else}
            <div class="reference-info">
              <div class="reference-status-warning">
                <i class="crm-i fa-exclamation-triangle"></i>
                <strong>{ts}File is orphaned{/ts}</strong>
                <p>{ts}This file is not referenced in the database and can be safely deleted.{/ts}</p>
              </div>

              {if $directoryType == 'contribute'}
                <div class="orphan-explanation">
                  <h4>{ts}What this means for contribute images:{/ts}</h4>
                  <ul>
                    <li>{ts}The image is not used in any contribution page content{/ts}</li>
                    <li>{ts}It's not referenced in headers, footers, or thank-you text{/ts}</li>
                    <li>{ts}It's not used in any message templates{/ts}</li>
                    <li>{ts}Safe to delete to free up storage space{/ts}</li>
                  </ul>
                </div>
              {else}
                <div class="orphan-explanation">
                  <h4>{ts}What this means for custom files:{/ts}</h4>
                  <ul>
                    <li>{ts}The file is not attached to any contact or entity{/ts}</li>
                    <li>{ts}It's not used in custom fields or activities{/ts}</li>
                    <li>{ts}It's not linked to any case or relationship{/ts}</li>
                    <li>{ts}Safe to delete to free up storage space{/ts}</li>
                  </ul>
                </div>
              {/if}
            </div>
          {/if}
        </div>

        {* Security Information *}
        <div class="info-panel">
          <h3>{ts}Security & Permissions{/ts}</h3>
          <div class="security-info">
            <div class="info-grid">
              <div class="info-row">
                <label>{ts}Readable:{/ts}</label>
                <span class="value">
                  {if $fileInfo.readable}
                    <i class="crm-i fa-check text-success"></i> {ts}Yes{/ts}
                  {else}
                    <i class="crm-i fa-times text-danger"></i> {ts}No{/ts}
                  {/if}
                </span>
              </div>
              <div class="info-row">
                <label>{ts}Writable:{/ts}</label>
                <span class="value">
                  {if $fileInfo.writable}
                    <i class="crm-i fa-check text-success"></i> {ts}Yes{/ts}
                  {else}
                    <i class="crm-i fa-times text-danger"></i> {ts}No{/ts}
                  {/if}
                </span>
              </div>
              <div class="info-row">
                <label>{ts}Preview Supported:{/ts}</label>
                <span class="value">
                  {if $fileInfo.preview_supported}
                    <i class="crm-i fa-check text-success"></i> {ts}Yes{/ts}
                  {else}
                    <i class="crm-i fa-times text-warning"></i> {ts}No{/ts}
                  {/if}
                </span>
              </div>
            </div>
          </div>
        </div>

        {* EXIF Data for Images *}
        {if $fileInfo.is_image && $fileInfo.exif}
          <div class="info-panel">
            <h3>{ts}Camera Information{/ts}</h3>
            <div class="exif-info">
              <div class="info-grid">
                {if $fileInfo.exif.camera_make}
                  <div class="info-row">
                    <label>{ts}Camera:{/ts}</label>
                    <span class="value">{$fileInfo.exif.camera_make} {$fileInfo.exif.camera_model}</span>
                  </div>
                {/if}
                {if $fileInfo.exif.date_taken}
                  <div class="info-row">
                    <label>{ts}Date Taken:{/ts}</label>
                    <span class="value">{$fileInfo.exif.date_taken}</span>
                  </div>
                {/if}
                {if $fileInfo.exif.f_number}
                  <div class="info-row">
                    <label>{ts}Aperture:{/ts}</label>
                    <span class="value">{$fileInfo.exif.f_number}</span>
                  </div>
                {/if}
                {if $fileInfo.exif.exposure_time}
                  <div class="info-row">
                    <label>{ts}Shutter Speed:{/ts}</label>
                    <span class="value">{$fileInfo.exif.exposure_time}s</span>
                  </div>
                {/if}
                {if $fileInfo.exif.iso}
                  <div class="info-row">
                    <label>{ts}ISO:{/ts}</label>
                    <span class="value">{$fileInfo.exif.iso}</span>
                  </div>
                {/if}
                {if $fileInfo.exif.focal_length}
                  <div class="info-row">
                    <label>{ts}Focal Length:{/ts}</label>
                    <span class="value">{$fileInfo.exif.focal_length}</span>
                  </div>
                {/if}
              </div>

              {if $fileInfo.exif.gps}
                <div class="gps-info">
                  <h4>{ts}Location Information{/ts}</h4>
                  <p><strong>{ts}Coordinates:{/ts}</strong> {$fileInfo.exif.gps.latitude}, {$fileInfo.exif.gps.longitude}</p>
                  <button onclick="showLocationOnMap()" class="button small">
                    <i class="crm-i fa-map-marker"></i> {ts}Show on Map{/ts}
                  </button>
                </div>
              {/if}
            </div>
          </div>
        {/if}
      </div>
    </div>

    {* Footer Actions *}
    <div class="preview-footer">
      <div class="footer-actions">
        <div class="danger-zone">
          {if !$fileInfo.is_referenced}
            <button onclick="deleteFile()" class="button danger">
              <i class="crm-i fa-trash"></i> {ts}Delete Orphaned File{/ts}
            </button>
            <span class="help-text">{ts}This file is safe to delete as it's not referenced anywhere.{/ts}</span>
          {else}
            <button onclick="confirmDeleteReferenced()" class="button danger" disabled title="{ts}Cannot delete referenced file{/ts}">
              <i class="crm-i fa-ban"></i> {ts}Cannot Delete (Referenced){/ts}
            </button>
            <span class="help-text">{ts}This file cannot be deleted as it's still being used.{/ts}</span>
          {/if}
        </div>

        <div class="info-actions">
          <button onclick="copyPath()" class="button">
            <i class="crm-i fa-copy"></i> {ts}Copy Path{/ts}
          </button>
          <button onclick="refreshInfo()" class="button">
            <i class="crm-i fa-refresh"></i> {ts}Refresh Info{/ts}
          </button>
        </div>
      </div>
    </div>
  </div>
</div>

{* JavaScript for enhanced functionality *}
<script type="text/javascript">
  {literal}
  function openImageInNewTab() {
    var previewUrl = '{/literal}{$fileInfo.preview_url}{literal}';
    window.open(previewUrl, '_blank');
  }

  function deleteFile() {
    if (!confirm('{/literal}{ts escape="js"}Are you sure you want to delete this file? This action cannot be undone.{/ts}{literal}')) {
      return;
    }

    // Make AJAX call to delete file
    CRM.api3('FileAnalyzer', 'deleteFile', {
      'file_path': '{/literal}{$fileInfo.path|escape:"js"}{literal}',
      'directory_type': '{/literal}{$directoryType|escape:"js"}{literal}'
    }).done(function(result) {
      CRM.alert('{/literal}{ts escape="js"}File deleted successfully{/ts}{literal}', '{/literal}{ts escape="js"}Success{/ts}{literal}', 'success');
      setTimeout(function() {
        window.close();
      }, 2000);
    }).fail(function(error) {
      CRM.alert('{/literal}{ts escape="js"}Failed to delete file{/ts}{literal}', '{/literal}{ts escape="js"}Error{/ts}{literal}', 'error');
    });
  }

  function confirmDeleteReferenced() {
    CRM.alert('{/literal}{ts escape="js"}This file cannot be deleted because it is still being used. Please remove all references first.{/ts}{literal}', '{/literal}{ts escape="js"}Cannot Delete{/ts}{literal}', 'info');
  }

  function copyPath() {
    var path = '{/literal}{$fileInfo.path|escape:"js"}{literal}';
    if (navigator.clipboard) {
      navigator.clipboard.writeText(path).then(function() {
        CRM.alert('{/literal}{ts escape="js"}File path copied to clipboard{/ts}{literal}', '', 'success', {expires: 2000});
      });
    } else {
      // Fallback for older browsers
      var textArea = document.createElement('textarea');
      textArea.value = path;
      document.body.appendChild(textArea);
      textArea.select();
      document.execCommand('copy');
      document.body.removeChild(textArea);
      CRM.alert('{/literal}{ts escape="js"}File path copied to clipboard{/ts}{literal}', '', 'success', {expires: 2000});
    }
  }

  function refreshInfo() {
    location.reload();
  }

  function showLocationOnMap() {
    var lat = '{/literal}{$fileInfo.exif.gps.latitude|default:""}{literal}';
    var lng = '{/literal}{$fileInfo.exif.gps.longitude|default:""}{literal}';

    if (lat && lng) {
      var mapsUrl = 'https://www.google.com/maps?q=' + lat + ',' + lng;
      window.open(mapsUrl, '_blank');
    }
  }

  // Keyboard shortcuts
  document.addEventListener('keydown', function(e) {
    // Escape key closes window
    if (e.keyCode === 27) {
      window.close();
    }

    // Delete key for orphaned files
    if (e.keyCode === 46 && !{/literal}{if $fileInfo.is_referenced}true{else}false{/if}{literal}) {
      deleteFile();
    }

    // Ctrl/Cmd + D for download
    if ((e.ctrlKey || e.metaKey) && e.keyCode === 68) {
      e.preventDefault();
      window.open('{/literal}{$fileInfo.download_url}{literal}', '_blank');
    }

    // Ctrl/Cmd + C for copy path
    if ((e.ctrlKey || e.metaKey) && e.keyCode === 67 && e.target.tagName !== 'INPUT' && e.target.tagName !== 'TEXTAREA') {
      e.preventDefault();
      copyPath();
    }
  });

  // Auto-focus for better keyboard navigation
  window.addEventListener('load', function() {
    document.body.tabIndex = -1;
    document.body.focus();
  });
  {/literal}
</script>

{* Enhanced CSS for better presentation *}
<style type="text/css">
  {literal}
  .crm-fileanalyzer-preview {
    padding: 0;
    margin: 0;
    min-height: 100vh;
    background: #f8f9fa;
  }

  .crm-breadcrumb {
    background: #fff;
    border-bottom: 1px solid #dee2e6;
    padding: 0.5rem 1.5rem;
  }

  .breadcrumb {
    display: flex;
    list-style: none;
    margin: 0;
    padding: 0;
  }

  .breadcrumb-item {
    display: flex;
    align-items: center;
  }

  .breadcrumb-item:not(:last-child)::after {
    content: '/';
    margin: 0 0.5rem;
    color: #6c757d;
  }

  .breadcrumb-item a {
    color: #007bff;
    text-decoration: none;
  }

  .breadcrumb-item .current {
    color: #6c757d;
    font-weight: 500;
  }

  .preview-header {
    background: #fff;
    border-bottom: 1px solid #dee2e6;
    padding: 1.5rem;
  }

  .header-content {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    gap: 1rem;
  }

  .preview-title {
    margin: 0 0 0.5rem 0;
    font-size: 1.5rem;
    font-weight: 600;
    display: flex;
    align-items: center;
    gap: 0.5rem;
  }

  .file-meta {
    display: flex;
    gap: 1rem;
    flex-wrap: wrap;
    align-items: center;
    font-size: 0.9rem;
    color: #6c757d;
  }

  .file-meta span {
    display: flex;
    align-items: center;
    gap: 0.25rem;
    padding: 0.25rem 0.5rem;
    background: #f8f9fa;
    border-radius: 4px;
    border: 1px solid #dee2e6;
  }

  .reference-status.referenced {
    background: #d4edda !important;
    color: #155724 !important;
    border-color: #c3e6cb !important;
  }

  .reference-status.orphaned {
    background: #fff3cd !important;
    color: #856404 !important;
    border-color: #ffeaa7 !important;
  }

  .header-actions {
    display: flex;
    gap: 0.5rem;
    flex-shrink: 0;
  }

  .preview-main {
    display: grid;
    grid-template-columns: 1fr 350px;
    gap: 0;
    min-height: calc(100vh - 200px);
  }

  .preview-content {
    background: #fff;
    padding: 2rem;
    overflow: auto;
  }

  .preview-sidebar {
    background: #f8f9fa;
    border-left: 1px solid #dee2e6;
    padding: 1.5rem;
    overflow-y: auto;
    max-height: calc(100vh - 200px);
  }

  .info-panel {
    background: #fff;
    border: 1px solid #dee2e6;
    border-radius: 6px;
    margin-bottom: 1.5rem;
    overflow: hidden;
  }

  .info-panel h3 {
    margin: 0;
    padding: 1rem 1.5rem;
    background: #f8f9fa;
    border-bottom: 1px solid #dee2e6;
    font-size: 1rem;
    font-weight: 600;
    color: #495057;
  }

  .info-panel h4 {
    margin: 0 0 0.5rem 0;
    font-size: 0.95rem;
    font-weight: 600;
    color: #495057;
  }

  .info-grid {
    padding: 1.5rem;
  }

  .info-row {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 0.75rem;
    gap: 1rem;
  }

  .info-row:last-child {
    margin-bottom: 0;
  }

  .info-row label {
    font-weight: 500;
    color: #495057;
    white-space: nowrap;
    min-width: 80px;
  }

  .info-row .value {
    color: #6c757d;
    word-break: break-all;
    text-align: right;
    flex: 1;
  }

  .value.filename {
    font-family: 'Courier New', monospace;
    font-size: 0.9rem;
  }

  .value.path {
    font-family: 'Courier New', monospace;
    font-size: 0.8rem;
    word-break: break-all;
  }

  .directory-info {
    padding: 1.5rem;
  }

  .directory-info .description {
    color: #6c757d;
    margin-bottom: 1rem;
    line-height: 1.5;
  }

  .reference-info {
    padding: 1.5rem;
  }

  .reference-status-good {
    background: #d4edda;
    border: 1px solid #c3e6cb;
    border-radius: 4px;
    padding: 1rem;
    margin-bottom: 1rem;
    color: #155724;
  }

  .reference-status-warning {
    background: #fff3cd;
    border: 1px solid #ffeaa7;
    border-radius: 4px;
    padding: 1rem;
    margin-bottom: 1rem;
    color: #856404;
  }

  .reference-details {
    margin-top: 1rem;
  }

  .reference-item {
    background: #f8f9fa;
    border: 1px solid #dee2e6;
    border-radius: 4px;
    padding: 1rem;
    margin-bottom: 1rem;
  }

  .reference-item:last-child {
    margin-bottom: 0;
  }

  .ref-header {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    margin-bottom: 0.5rem;
    font-weight: 600;
    color: #495057;
  }

  .ref-details {
    color: #6c757d;
    font-size: 0.9rem;
  }

  .ref-details p {
    margin-bottom: 0.5rem;
  }

  .entity-list ul {
    margin: 0.5rem 0 0 1rem;
    padding: 0;
  }

  .entity-list li {
    margin-bottom: 0.25rem;
  }

  .orphan-explanation {
    background: #f8f9fa;
    border: 1px solid #dee2e6;
    border-radius: 4px;
    padding: 1rem;
    margin-top: 1rem;
  }

  .orphan-explanation ul {
    margin: 0.5rem 0 0 1.5rem;
    color: #6c757d;
  }

  .security-info {
    padding: 1.5rem;
  }

  .exif-info {
    padding: 1.5rem;
  }

  .gps-info {
    background: #f8f9fa;
    border: 1px solid #dee2e6;
    border-radius: 4px;
    padding: 1rem;
    margin-top: 1rem;
  }

  .preview-footer {
    background: #fff;
    border-top: 1px solid #dee2e6;
    padding: 1.5rem;
  }

  .footer-actions {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 2rem;
  }

  .danger-zone {
    display: flex;
    align-items: center;
    gap: 1rem;
  }

  .help-text {
    font-size: 0.875rem;
    color: #6c757d;
  }

  .info-actions {
    display: flex;
    gap: 0.5rem;
  }

  .text-success {
    color: #28a745 !important;
  }

  .text-danger {
    color: #dc3545 !important;
  }

  .text-warning {
    color: #ffc107 !important;
  }

  .button {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.5rem 1rem;
    border: 1px solid #dee2e6;
    border-radius: 4px;
    background: #fff;
    color: #495057;
    text-decoration: none;
    font-size: 0.875rem;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.2s ease;
  }

  .button:hover {
    background: #f8f9fa;
    border-color: #adb5bd;
    transform: translateY(-1px);
  }

  .button.small {
    padding: 0.25rem 0.5rem;
    font-size: 0.8125rem;
  }

  .button.danger {
    background: #dc3545;
    color: #fff;
    border-color: #dc3545;
  }

  .button.danger:hover {
    background: #c82333;
    border-color: #bd2130;
  }

  .button:disabled {
    opacity: 0.5;
    cursor: not-allowed;
    transform: none !important;
  }

  /* Image preview specific styles */
  .image-preview-container {
    text-align: center;
  }

  .image-preview-main {
    margin-bottom: 2rem;
  }

  .image-info {
    padding: 1rem;
    background: #f8f9fa;
    border-radius: 6px;
    border: 1px solid #dee2e6;
  }

  /* PDF preview specific styles */
  .pdf-preview-container {
    max-width: 100%;
  }

  .pdf-preview-toolbar {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1rem;
    padding: 1rem;
    background: #f8f9fa;
    border-radius: 6px;
    border: 1px solid #dee2e6;
  }

  /* Text preview specific styles */
  .text-preview-container {
    max-width: 100%;
  }

  .text-info {
    font-size: 0.875rem;
    color: #6c757d;
  }

  /* Generic preview styles */
  .generic-preview-container {
    padding: 3rem;
    text-align: center;
  }

  .file-icon {
    margin-bottom: 2rem;
  }

  /* Responsive design */
  @media (max-width: 1200px) {
    .preview-main {
      grid-template-columns: 1fr 300px;
    }
  }

  @media (max-width: 992px) {
    .preview-main {
      grid-template-columns: 1fr;
    }

    .preview-sidebar {
      border-left: none;
      border-top: 1px solid #dee2e6;
      max-height: none;
    }
  }

  @media (max-width: 768px) {
    .header-content {
      flex-direction: column;
      align-items: stretch;
    }

    .header-actions {
      justify-content: flex-start;
    }

    .file-meta {
      flex-direction: column;
      align-items: flex-start;
      gap: 0.5rem;
    }

    .footer-actions {
      flex-direction: column;
      align-items: stretch;
      gap: 1rem;
    }

    .danger-zone {
      flex-direction: column;
      align-items: stretch;
      gap: 0.5rem;
    }
  }
  {/literal}
</style>
