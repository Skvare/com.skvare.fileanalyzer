{* File Preview Template *}
<div class="crm-container">
  <div class="crm-block crm-content-block crm-fileanalyzer-preview">

    <div class="crm-submit-buttons">
      <a href="javascript:window.close()" class="button">
        <span><i class="crm-i fa-times"></i> {ts}Close{/ts}</span>
      </a>
    </div>

    <div class="file-preview-header">
      <h3><i class="crm-i fa-file-o"></i> {ts}File Preview{/ts}</h3>
    </div>

    <div class="file-info-section">
      <table class="file-info-table">
        <tr>
          <td><strong>{ts}Name:{/ts}</strong></td>
          <td>{$fileInfo.name}</td>
        </tr>
        <tr>
          <td><strong>{ts}Size:{/ts}</strong></td>
          <td>{$fileInfo.size_formatted}</td>
        </tr>
        <tr>
          <td><strong>{ts}Modified:{/ts}</strong></td>
          <td>{$fileInfo.modified}</td>
        </tr>
        <tr>
          <td><strong>{ts}Type:{/ts}</strong></td>
          <td>{$fileInfo.mime_type}</td>
        </tr>
        {if $fileInfo.dimensions}
          <tr>
            <td><strong>{ts}Dimensions:{/ts}</strong></td>
            <td>{$fileInfo.dimensions}</td>
          </tr>
        {/if}
        <tr>
          <td><strong>{ts}Status:{/ts}</strong></td>
          <td>
            {if $fileInfo.is_referenced}
              <span class="label label-success">{ts}Referenced{/ts}</span>
            {else}
              <span class="label label-warning">{ts}Abandoned{/ts}</span>
            {/if}
          </td>
        </tr>
      </table>
    </div>

    {if $fileInfo.is_image && $previewUrl}
      <div class="file-preview-section">
        <h4>{ts}Image Preview{/ts}</h4>
        <div class="image-preview">
          <img src="{$previewUrl}" alt="{$fileInfo.name}" style="max-width: 100%; max-height: 500px; border: 1px solid #ccc;" />
        </div>
      </div>
    {else}
      <div class="file-preview-section">
        <div class="no-preview">
          <i class="crm-i fa-file-o" style="font-size: 4em; color: #ccc;"></i>
          <p>{ts}Preview not available for this file type.{/ts}</p>
        </div>
      </div>
    {/if}

  </div>
</div>

<style>
  .crm-fileanalyzer-preview {
    padding: 20px;
  }

  .file-preview-header {
    border-bottom: 2px solid #ddd;
    padding-bottom: 10px;
    margin-bottom: 20px;
  }

  .file-info-section {
    margin: 20px 0;
  }

  .file-info-table {
    width: 100%;
    border-collapse: collapse;
  }

  .file-info-table td {
    padding: 8px 12px;
    border-bottom: 1px solid #eee;
  }

  .file-info-table td:first-child {
    width: 150px;
    background: #f9f9f9;
  }

  .file-preview-section {
    margin: 30px 0;
    text-align: center;
  }

  .no-preview {
    padding: 40px;
    color: #666;
  }

  .label {
    padding: 3px 8px;
    border-radius: 3px;
    font-size: 0.9em;
    font-weight: bold;
  }

  .label-success {
    background: #46b450;
    color: white;
  }

  .label-warning {
    background: #ffb900;
    color: white;
  }

  .image-preview {
    margin: 20px 0;
    text-align: center;
  }
</style>
