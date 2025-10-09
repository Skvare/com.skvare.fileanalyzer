{*
 +--------------------------------------------------------------------+
 | CiviCRM File Analyzer Extension                                   |
 +--------------------------------------------------------------------+
 | Public Images Dashboard Template                               |
 +--------------------------------------------------------------------+
*}

<div class="crm-container">
  <div class="crm-block crm-content-block">

    {* Page Header *}
    <div class="file-analyzer-header">
      <div class="header-content">
        <h1 class="page-title">{ts}Public Images Dashboard{/ts}</h1>
        <p class="page-description">{ts}Monitor, analyze, and manage your CiviCRM public page images{/ts}</p>
        <div class="header-actions">
          <a href="{crmURL p='civicrm/fileanalyzer/public-search'}" class="button">
            <i class="crm-i fa-files-o"></i> {ts}File Listing{/ts}
          </a>
          <a href="{crmURL p='civicrm/file-analyzer/dashboard'}" class="button">
            <i class="crm-i fa-files-o"></i> {ts}Custom Files Dashboard{/ts}
          </a>
          <a href="{crmURL p='civicrm/admin/setting/fileanalyzer'}" class="button">
            <i class="crm-i fa-cog"></i> {ts}Settings{/ts}
          </a>
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

    {* Additional Info Section *}
    <div class="file-analyzer-files">
      <div class="files-panel">
        <div class="panel-header">
          <div class="header-left">
            <h3 class="panel-title">{ts}About Public Images{/ts}</h3>
            <p class="panel-description">
              {ts}Understanding how public page images work in CiviCRM{/ts}
            </p>
          </div>
        </div>
        <div class="panel-body">
          <div style="padding: 2rem;">
            <div class="info-grid" style="display: grid; grid-template-columns: 1fr 1fr; gap: 2rem;">
              <div>
                <h4 style="color: #1e293b; margin-bottom: 1rem;">{ts}Image Usage{/ts}</h4>
                <ul style="color: #64748b; line-height: 1.6;">
                  <li>{ts}Images are typically referenced in contribute/event page header and footer content{/ts}</li>
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

{* Initialize enhanced data for JavaScript *}
<script type="text/javascript">
  {literal}
  var FileAnalyzerData = {
    fileData: {/literal}{$fileData}{literal},
    directoryStats: {/literal}{$directoryStats|@json_encode}{literal},
    directoryType: '{/literal}{$directoryType}{literal}'
  };
  {/literal}
</script>

<style>
  {literal}
  .stat-card {
    border-left-color: #764ba2;
  }

  .stat-card.warning {
    border-left-color: #f59e0b;
  }

  .stat-card.danger {
    border-left-color: #ef4444;
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
