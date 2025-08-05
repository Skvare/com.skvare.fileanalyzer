{*
 +--------------------------------------------------------------------+
 | CiviCRM File Analyzer Extension                                   |
 +--------------------------------------------------------------------+
 | Settings Form Template                                             |
 +--------------------------------------------------------------------+
*}

{crmStyle ext=com.skvare.fileanalyzer file=css/settings.css}

<div class="crm-container">
  <div class="crm-block crm-form-block crm-fileanalyzer-settings-form-block">

    {* Page Header *}
    <div class="settings-header">
      <h1 class="page-title">{ts}File Analyzer Settings{/ts}</h1>
      <p class="page-description">
        {ts}Configure how the File Analyzer extension monitors and manages your CiviCRM files.{/ts}
      </p>
      <div class="header-links">
        <a href="{crmURL p='civicrm/file-analyzer/dashboard'}" class="button">
          <i class="crm-i fa-dashboard"></i> {ts}View Dashboard{/ts}
        </a>
      </div>
    </div>

    {* Settings Form *}
    <div class="crm-form">

      {* Scanning Settings *}
      <fieldset class="settings-section">
        <legend>{ts}File Scanning{/ts}</legend>
        <div class="section-description">
          {ts}Control how often the system scans for abandoned files and which files to analyze.{/ts}
        </div>

        <div class="crm-section">
          <div class="label">{$form.fileanalyzer_scan_interval.label}</div>
          <div class="content">
            {$form.fileanalyzer_scan_interval.html}
            <div class="description">
              {ts}How often the scheduled job should run to scan for abandoned files. Recommended: 24 hours for most installations.{/ts}
            </div>
          </div>
          <div class="clear"></div>
        </div>

        <div class="crm-section">
          <div class="label">{$form.fileanalyzer_excluded_extensions.label}</div>
          <div class="content">
            {$form.fileanalyzer_excluded_extensions.html}
            <div class="description">
              {ts}Comma-separated list of file extensions to exclude from analysis (e.g., tmp,log,cache).{/ts}
            </div>
            <div class="example">
              <strong>{ts}Examples:{/ts}</strong> tmp,log,cache,bak,temp
            </div>
          </div>
          <div class="clear"></div>
        </div>
      </fieldset>

      {* Auto-Delete Settings *}
      <fieldset class="settings-section">
        <legend>{ts}Automatic Cleanup{/ts}</legend>
        <div class="section-description">
          {ts}Configure automatic deletion of old abandoned files to maintain disk space.{/ts}
        </div>

        <div class="crm-section">
          <div class="label">{$form.fileanalyzer_auto_delete.label}</div>
          <div class="content">
            {$form.fileanalyzer_auto_delete.html}
            <div class="description">
              {ts}Enable automatic deletion of abandoned files older than the specified number of days.{/ts}
            </div>
            <div class="warning-box" style="display:none;" id="autoDeleteWarning">
              <i class="crm-i fa-warning"></i>
              <strong>{ts}Warning:{/ts}</strong> {ts}Auto-delete will permanently remove files. Ensure backup is enabled before using this feature.{/ts}
            </div>
          </div>
          <div class="clear"></div>
        </div>

        <div class="crm-section auto-delete-dependent" style="display:none;">
          <div class="label">{$form.fileanalyzer_auto_delete_days.label}</div>
          <div class="content">
            {$form.fileanalyzer_auto_delete_days.html}
            <div class="description">
              {ts}Files that have been abandoned for more than this number of days will be automatically deleted.{/ts}
            </div>
          </div>
          <div class="clear"></div>
        </div>
      </fieldset>

      {* Backup Settings *}
      <fieldset class="settings-section">
        <legend>{ts}File Backup{/ts}</legend>
        <div class="section-description">
          {ts}Configure backup options to protect against accidental data loss.{/ts}
        </div>

        <div class="crm-section">
          <div class="label">{$form.fileanalyzer_backup_before_delete.label}</div>
          <div class="content">
            {$form.fileanalyzer_backup_before_delete.html}
            <div class="description">
              {ts}Create a backup copy of files before deletion. Highly recommended for safety.{/ts}
            </div>
            <div class="info-box">
              <i class="crm-i fa-info-circle"></i>
              {ts}Backup files are stored in:{/ts} <code>{$backupPath}</code>
            </div>
          </div>
          <div class="clear"></div>
        </div>
      </fieldset>

      {* System Information *}
      <fieldset class="settings-section">
        <legend>{ts}System Information{/ts}</legend>
        <div class="section-description">
          {ts}Current system status and file directory information.{/ts}
        </div>

        <div class="system-info">
          <div class="info-grid">
            <div class="info-item">
              <label>{ts}Custom File Directory:{/ts}</label>
              <span class="value">{$customFileDir}</span>
            </div>
            <div class="info-item">
              <label>{ts}Directory Writable:{/ts}</label>
              <span class="value {if $dirWritable}success{else}error{/if}">
                {if $dirWritable}
                  <i class="crm-i fa-check"></i> {ts}Yes{/ts}
                {else}
                  <i class="crm-i fa-times"></i> {ts}No{/ts}
                {/if}
              </span>
            </div>
            <div class="info-item">
              <label>{ts}Backup Directory:{/ts}</label>
              <span class="value">{$backupPath}</span>
            </div>
            <div class="info-item">
              <label>{ts}Last Scan:{/ts}</label>
              <span class="value">{$lastScan|default:'{ts}Never{/ts}'}</span>
            </div>
            <div class="info-item">
              <label>{ts}Scheduled Job Status:{/ts}</label>
              <span class="value {if $scheduledJobActive}success{else}warning{/if}">
                {if $scheduledJobActive}
                  <i class="crm-i fa-check"></i> {ts}Active{/ts}
                {else}
                  <i class="crm-i fa-warning"></i> {ts}Inactive{/ts}
                {/if}
              </span>
            </div>
            <div class="info-item">
              <label>{ts}PHP Memory Limit:{/ts}</label>
              <span class="value">{$phpMemoryLimit}</span>
            </div>
          </div>
        </div>
      </fieldset>

      {* Test & Actions *}
      <fieldset class="settings-section">
        <legend>{ts}Test & Actions{/ts}</legend>
        <div class="section-description">
          {ts}Test the file analyzer functionality and perform manual operations.{/ts}
        </div>

        <div class="action-buttons">
          <button type="button" class="button" onclick="testFileScan()" id="testScanBtn">
            <i class="crm-i fa-search"></i> {ts}Test File Scan{/ts}
          </button>
          <button type="button" class="button" onclick="runScheduledJob()" id="runJobBtn">
            <i class="crm-i fa-play"></i> {ts}Run Scheduled Job{/ts}
          </button>
          <button type="button" class="button" onclick="clearBackups()" id="clearBackupsBtn">
            <i class="crm-i fa-trash"></i> {ts}Clear Old Backups{/ts}
          </button>
        </div>

        <div id="testResults" class="test-results" style="display:none;">
          <div class="results-header">
            <h4>{ts}Test Results{/ts}</h4>
          </div>
          <div class="results-content" id="testResultsContent">
            <!-- Results will be populated by JavaScript -->
          </div>
        </div>
      </fieldset>

      {* Form Buttons *}
      <div class="crm-submit-buttons">
        {include file="CRM/common/formButtons.tpl" location="bottom"}
        <span class="crm-button">
          <input type="button" class="crm-form-submit default" name="_qf_Settings_refresh" value="{ts}Reset to Defaults{/ts}" onclick="resetToDefaults()" />
        </span>
      </div>
    </div>
  </div>
</div>

{* Help Text Overlay *}
<div id="helpOverlay" class="help-overlay" style="display:none;">
  <div class="help-content">
    <div class="help-header">
      <h3>{ts}File Analyzer Help{/ts}</h3>
      <button class="close-help" onclick="closeHelp()">
        <i class="crm-i fa-times"></i>
      </button>
    </div>
    <div class="help-body">
      <h4>{ts}Scan Interval{/ts}</h4>
      <p>{ts}Controls how frequently the system checks for abandoned files. Lower values provide more frequent monitoring but may impact performance on large installations.{/ts}</p>

      <h4>{ts}Auto-Delete{/ts}</h4>
      <p>{ts}When enabled, files that have been abandoned for longer than the specified period will be automatically deleted. Use with caution and ensure backups are enabled.{/ts}</p>

      <h4>{ts}File Exclusions{/ts}</h4>
      <p>{ts}Specify file extensions that should be ignored during analysis. Common exclusions include temporary files, logs, and cache files.{/ts}</p>

      <h4>{ts}Security Notes{/ts}</h4>
      <ul>
        <li>{ts}Only users with 'administer CiviCRM' permission can access these settings{/ts}</li>
        <li>{ts}Files are only deleted if they're not referenced in the database{/ts}</li>
        <li>{ts}Backup system prevents accidental data loss{/ts}</li>
      </ul>
    </div>
  </div>
</div>

{* JavaScript for form interactions *}
<script type="text/javascript">
  {literal}
  CRM.$(function($) {
    // Toggle auto-delete dependent fields
    $('#fileanalyzer_auto_delete').change(function() {
      if ($(this).is(':checked')) {
        $('.auto-delete-dependent').show();
        $('#autoDeleteWarning').show();
      } else {
        $('.auto-delete-dependent').hide();
        $('#autoDeleteWarning').hide();
      }
    }).trigger('change');

    // Form validation
    $('form').submit(function(e) {
      var scanInterval = parseInt($('#fileanalyzer_scan_interval').val());
      var autoDeleteDays = parseInt($('#fileanalyzer_auto_delete_days').val());

      if (scanInterval < 1 || scanInterval > 168) {
        CRM.alert('{/literal}{ts escape="js"}Scan interval must be between 1 and 168 hours.{/ts}{literal}', '{/literal}{ts escape="js"}Invalid Input{/ts}{literal}', 'error');
        e.preventDefault();
        return false;
      }

      if ($('#fileanalyzer_auto_delete').is(':checked') && (autoDeleteDays < 1 || autoDeleteDays > 365)) {
        CRM.alert('{/literal}{ts escape="js"}Auto-delete days must be between 1 and 365.{/ts}{literal}', '{/literal}{ts escape="js"}Invalid Input{/ts}{literal}', 'error');
        e.preventDefault();
        return false;
      }

      return true;
    });
  });

  // Test file scan functionality
  function testFileScan() {
    var btn = $('#testScanBtn');
    btn.prop('disabled', true).html('<i class="crm-i fa-spinner fa-spin"></i> {/literal}{ts escape="js"}Testing...{/ts}{literal}');

    CRM.api3('FileAnalyzer', 'getstats')
      .done(function(result) {
        showTestResults({
          success: true,
          message: '{/literal}{ts escape="js"}File scan completed successfully{/ts}{literal}',
          data: result.values
        });
      })
      .fail(function(error) {
        showTestResults({
          success: false,
          message: '{/literal}{ts escape="js"}File scan failed{/ts}{literal}',
          error: error.error_message
        });
      })
      .always(function() {
        btn.prop('disabled', false).html('<i class="crm-i fa-search"></i> {/literal}{ts escape="js"}Test File Scan{/ts}{literal}');
      });
  }

  // Run scheduled job manually
  function runScheduledJob() {
    var btn = $('#runJobBtn');
    btn.prop('disabled', true).html('<i class="crm-i fa-spinner fa-spin"></i> {/literal}{ts escape="js"}Running...{/ts}{literal}');

    CRM.api3('FileAnalyzer', 'scan', { force_scan: 1 })
      .done(function(result) {
        showTestResults({
          success: true,
          message: '{/literal}{ts escape="js"}Scheduled job completed successfully{/ts}{literal}',
          data: result.values
        });
      })
      .fail(function(error) {
        showTestResults({
          success: false,
          message: '{/literal}{ts escape="js"}Scheduled job failed{/ts}{literal}',
          error: error.error_message
        });
      })
      .always(function() {
        btn.prop('disabled', false).html('<i class="crm-i fa-play"></i> {/literal}{ts escape="js"}Run Scheduled Job{/ts}{literal}');
      });
  }

  // Clear old backup files
  function clearBackups() {
    if (!confirm('{/literal}{ts escape="js"}Are you sure you want to clear old backup files? This action cannot be undone.{/ts}{literal}')) {
      return;
    }

    var btn = $('#clearBackupsBtn');
    btn.prop('disabled', true).html('<i class="crm-i fa-spinner fa-spin"></i> {/literal}{ts escape="js"}Clearing...{/ts}{literal}');

    // This would need a custom API endpoint
    CRM.alert('{/literal}{ts escape="js"}This feature will be implemented in a future version.{/ts}{literal}', '{/literal}{ts escape="js"}Coming Soon{/ts}{literal}', 'info');

    btn.prop('disabled', false).html('<i class="crm-i fa-trash"></i> {/literal}{ts escape="js"}Clear Old Backups{/ts}{literal}');
  }

  // Show test results
  function showTestResults(result) {
    var resultsDiv = $('#testResults');
    var contentDiv = $('#testResultsContent');

    var html = '<div class="result-item ' + (result.success ? 'success' : 'error') + '">';
    html += '<div class="result-status">';
    html += '<i class="crm-i fa-' + (result.success ? 'check' : 'times') + '"></i>';
    html += '<strong>' + result.message + '</strong>';
    html += '</div>';

    if (result.data) {
      html += '<div class="result-data">';
      html += '<ul>';
      for (var key in result.data) {
        if (result.data.hasOwnProperty(key)) {
          html += '<li><strong>' + key + ':</strong> ' + result.data[key] + '</li>';
        }
      }
      html += '</ul>';
      html += '</div>';
    }

    if (result.error) {
      html += '<div class="result-error">' + result.error + '</div>';
    }

    html += '</div>';

    contentDiv.html(html);
    resultsDiv.show();
  }

  // Reset form to defaults
  function resetToDefaults() {
    if (!confirm('{/literal}{ts escape="js"}Are you sure you want to reset all settings to their default values?{/ts}{literal}')) {
      return;
    }

    $('#fileanalyzer_scan_interval').val('24');
    $('#fileanalyzer_auto_delete').prop('checked', false).trigger('change');
    $('#fileanalyzer_auto_delete_days').val('90');
    $('#fileanalyzer_backup_before_delete').prop('checked', true);
    $('#fileanalyzer_excluded_extensions').val('tmp,log,cache');
  }

  // Show help overlay
  function showHelp() {
    $('#helpOverlay').show();
  }

  // Close help overlay
  function closeHelp() {
    $('#helpOverlay').hide();
  }
  {/literal}
</script>

{* Add help button to page title *}
<script type="text/javascript">
  {literal}
  CRM.$(function($) {
    $('.page-title').append(' <a href="#" onclick="showHelp(); return false;" class="help-link" title="{/literal}{ts escape="js"}Show Help{/ts}{literal}"><i class="crm-i fa-question-circle"></i></a>');
  });
  {/literal}
</script>
