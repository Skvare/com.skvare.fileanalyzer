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
          <a href="{crmURL p='civicrm/fileanalyzer/search'}" class="button">
            <i class="crm-i fa-files-o"></i> {ts}File Listing{/ts}
          </a>
          <a href="{crmURL p='civicrm/file-analyzer/public-dashboard'}" class="button">
            <i class="crm-i fa-files-o"></i> {ts}Public Files Dashboard{/ts}
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
            <div class="stat-number" id="abandonedCount">{$directoryStats.abandonedFiles}</div>
            <div class="stat-label">{ts}Abandoned Files{/ts}</div>
          </div>
        </div>

        <div class="stat-card danger">
          <div class="stat-icon">
            <i class="crm-i fa-trash"></i>
          </div>
          <div class="stat-content">
            <div class="stat-number" id="wastedSpace">{$directoryStats.abandonedSize|crmMoney:0:' ':' '}</div>
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
              <div>
              <label>
                <input type="checkbox" id="showAbandonedOnly" onchange="updateTimelineChart()">{ts}Show Only Abandoned Files{/ts}
              </label>
              </div>
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
