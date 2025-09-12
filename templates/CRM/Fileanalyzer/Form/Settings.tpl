{*
 +--------------------------------------------------------------------+
 | CiviCRM File Analyzer Extension                                   |
 +--------------------------------------------------------------------+
 | Settings Form Template                                             |
 +--------------------------------------------------------------------+
*}

<div class="crm-container">
  <div class="crm-block crm-form-block crm-fileanalyzer-settings-form-block">

    {* Page Header *}
    <div class="settings-header">
      <h1 class="page-title">{ts}File Analyzer Settings{/ts}</h1>
      <p class="page-description">
        {ts}Configure automated file monitoring, cleanup policies, and backup settings for your CiviCRM file system.{/ts}
      </p>
      <div class="header-links">
        <a href="{crmURL p='civicrm/file-analyzer/dashboard'}" class="button">
          <i class="crm-i fa-dashboard"></i> {ts}Custom Directory Dashboard{/ts}
        </a>
        <span class="description">Monitor attachments stored in custom directories. This includes contact photos, uploaded documents, resumes, contracts, and other files attached through custom file fields.</span>
      </div>
      <div class="header-links">
        <a href="{crmURL p='civicrm/file-analyzer/contribute-dashboard'}" class="button">
          <i class="crm-i fa-dashboard"></i> {ts}Contribution Files Dashboard{/ts}
        </a>
        <span class="description">Track files in the contribute upload directory. This covers images and documents associated with premium gifts, thank-you materials, and CiviContribute related files.</span>
      </div>
    </div>

    {* Settings Form *}
    <div class="crm-form">

      {* Scanning Settings *}
      <fieldset class="settings-section">
        <legend>{ts}File Monitoring & Scanning{/ts}</legend>
        <div class="section-description">
          {ts}Configure which files to monitor and how frequently the system should scan for orphaned files that are no longer referenced in the database.{/ts}
        </div>

        <div class="crm-section">
          <div class="label">{$form.fileanalyzer_excluded_extensions.label}</div>
          <div class="content">
            {$form.fileanalyzer_excluded_extensions.html|crmAddClass:huge40}
            <div class="description">
              {ts}Enter file extensions to exclude from analysis, separated by commas. These file types will be ignored during scans and cleanup operations.{/ts}
            </div>
            <div class="example">
              <strong>{ts}Recommended exclusions:{/ts}</strong> tmp,log,cache,bak,temp,htaccess,gitignore
            </div>
          </div>
          <div class="clear"></div>
        </div>
      </fieldset>

      {* Auto-Delete Settings *}
      <fieldset class="settings-section">
        <legend>{ts}Automated File Cleanup{/ts}</legend>
        <div class="section-description">
          {ts}Set up automatic removal of orphaned files to prevent disk space accumulation. Files are only deleted if they're not referenced anywhere in your CiviCRM database.{/ts}
        </div>

        <div class="crm-section">
          <div class="label">{$form.fileanalyzer_auto_delete.label}</div>
          <div class="content">
            {$form.fileanalyzer_auto_delete.html}
            <div class="description">
              {ts}Enable automatic cleanup of orphaned files that have exceeded the age threshold. Only files with no database references will be removed.{/ts}
            </div>
            <div class="warning-box" style="display:none;" id="autoDeleteWarning">
              <i class="crm-i fa-warning"></i>
              <strong>{ts}Important:{/ts}</strong> {ts}Automatic deletion permanently removes files from your server. Always enable backup protection before activating this feature.{/ts}
            </div>
          </div>
          <div class="clear"></div>
        </div>

        <div class="crm-section auto-delete-dependent" style="display:none;">
          <div class="label">{$form.fileanalyzer_auto_delete_days.label}</div>
          <div class="content">
            {$form.fileanalyzer_auto_delete_days.html}
            <div class="description">
              {ts}Number of days a file must remain orphaned before automatic deletion occurs. Recommended minimum: 30 days.{/ts}
            </div>
          </div>
          <div class="clear"></div>
        </div>
      </fieldset>

      {* Backup Settings *}
      <fieldset class="settings-section">
        <legend>{ts}File Protection & Backup{/ts}</legend>
        <div class="section-description">
          {ts}Configure safety measures to protect against accidental data loss during cleanup operations.{/ts}
        </div>

        <div class="crm-section">
          <div class="label">{$form.fileanalyzer_backup_before_delete.label}</div>
          <div class="content">
            {$form.fileanalyzer_backup_before_delete.html}
            <div class="description">
              {ts}Create backup copies of files before deletion. Strongly recommended to prevent data loss and enable file recovery if needed.{/ts}
            </div>
            <div class="info-box">
              <i class="crm-i fa-info-circle"></i>
              {ts}Backup storage location:{/ts} <code>{$backupPath}</code>
            </div>
          </div>
          <div class="clear"></div>
        </div>
      </fieldset>

      {* System Information *}
      <fieldset class="settings-section">
        <legend>{ts}System Status & Information{/ts}</legend>
        <div class="section-description">
          {ts}View current system configuration, directory permissions, and operational status.{/ts}
        </div>

        <div class="system-info">
          <div class="info-grid">
            <div class="info-item">
              <label>{ts}Primary Upload Directory:{/ts}</label>
              <span class="value">{$fileUploadDir}</span>
            </div>
            <div class="info-item">
              <label>{ts}Directory Write Access:{/ts}</label>
              <span class="value {if $dirWritable}success{else}error{/if}">
                {if $dirWritable}
                  <i class="crm-i fa-check"></i> {ts}Available{/ts}
                {else}
                  <i class="crm-i fa-times"></i> {ts}Restricted{/ts}
                {/if}
              </span>
            </div>
            <div class="info-item">
              <label>{ts}Backup Storage Directory:{/ts}</label>
              <span class="value">{$backupPath}</span>
            </div>
            <div class="info-item">
              <label>{ts}Most Recent Scan:{/ts}</label>
              <span class="value">{$lastScan|default:'{ts}No scans performed{/ts}'}</span>
            </div>
            <div class="info-item">
              <label>{ts}Automated Job Status:{/ts}</label>
              <span class="value {if $scheduledJobActive}success{else}warning{/if}">
                {if $scheduledJobActive}
                  <i class="crm-i fa-check"></i> {ts}Running{/ts}
                {else}
                  <i class="crm-i fa-warning"></i> {ts}Not Active{/ts}
                {/if}
              </span>
            </div>
            <div class="info-item">
              <label>{ts}Available PHP Memory:{/ts}</label>
              <span class="value">{$phpMemoryLimit}</span>
            </div>
          </div>
        </div>
      </fieldset>

      {* Test & Actions *}
      <fieldset class="settings-section">
        <legend>{ts}Manual Operations & Testing{/ts}</legend>
        <div class="section-description">
          {ts}Run diagnostic tests, execute manual scans, and perform maintenance tasks.{/ts}
        </div>

        <div class="action-buttons">
          <button type="button" class="button" onclick="testFileScan()" id="testScanBtn">
            <i class="crm-i fa-search"></i> {ts}Run Diagnostic Scan{/ts}
          </button>
          <button type="button" class="button" onclick="runScheduledJob()" id="runJobBtn">
            <i class="crm-i fa-play"></i> {ts}Execute Manual Cleanup{/ts}
          </button>
          <button type="button" class="button" onclick="clearBackups()" id="clearBackupsBtn">
            <i class="crm-i fa-trash"></i> {ts}Clean Backup Archive{/ts}
          </button>
        </div>

        <div id="testResults" class="test-results" style="display:none;">
          <div class="results-header">
            <h4>{ts}Operation Results{/ts}</h4>
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
          <input type="button" class="crm-form-submit default" name="_qf_Settings_refresh" value="{ts}Restore Default Settings{/ts}" onclick="resetToDefaults()" />
        </span>
      </div>
    </div>
  </div>
</div>

{* Help Text Overlay *}
<div id="helpOverlay" class="help-overlay" style="display:none;">
  <div class="help-content">
    <div class="help-header">
      <h3>{ts}File Analyzer User Guide{/ts}</h3>
      <button class="close-help" onclick="closeHelp()">
        <i class="crm-i fa-times"></i>
      </button>
    </div>
    <div class="help-body">
      <h4>{ts}Automated Cleanup{/ts}</h4>
      <p>{ts}When enabled, orphaned files exceeding the age threshold are automatically removed. Files are only deleted if they have no references in your CiviCRM database. Always use with backup protection enabled.{/ts}</p>

      <h4>{ts}File Exclusion Rules{/ts}</h4>
      <p>{ts}Specify file extensions to skip during analysis. Common exclusions include system files (htaccess), temporary files (tmp), log files (log), and cache files (cache). This prevents the system from flagging important system files as orphaned.{/ts}</p>

      <h4>{ts}Security & Safety Features{/ts}</h4>
      <ul>
        <li>{ts}Access restricted to users with 'administer CiviCRM' permissions{/ts}</li>
        <li>{ts}Files are analyzed for database references before any deletion{/ts}</li>
        <li>{ts}Backup system creates recovery copies of all deleted files{/ts}</li>
        <li>{ts}Manual approval required for bulk operations{/ts}</li>
      </ul>

      <h4>{ts}Best Practices{/ts}</h4>
      <ul>
        <li>{ts}Start with a 30+ day retention period for safety{/ts}</li>
        <li>{ts}Enable backups before using automatic cleanup{/ts}</li>
        <li>{ts}Monitor results regularly through the dashboard{/ts}</li>
        <li>{ts}Test with diagnostic scan before enabling automation{/ts}</li>
      </ul>
    </div>
  </div>
</div>

{* JavaScript for form interactions *}
<script type="text/javascript">
  {literal}
  CRM.$(function($) {
    // Toggle auto-delete dependent fields
    $('#fileanalyzer_auto_delete_fileanalyzer_auto_delete').change(function() {
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
      var autoDeleteDays = parseInt($('#fileanalyzer_auto_delete_days').val());

      if ($('#fileanalyzer_auto_delete_fileanalyzer_auto_delete').is(':checked') && (autoDeleteDays < 1 || autoDeleteDays > 365)) {
        CRM.alert('{/literal}{ts escape="js"}Retention period must be between 1 and 365 days.{/ts}{literal}', '{/literal}{ts escape="js"}Invalid Configuration{/ts}{literal}', 'error');
        e.preventDefault();
        return false;
      }

      return true;
    });
  });

  // Test file scan functionality
  function testFileScan() {
    var btn = CRM.$('#testScanBtn');
    btn.prop('disabled', true).html('<i class="crm-i fa-spinner fa-spin"></i> {/literal}{ts escape="js"}Scanning...{/ts}{literal}');

    CRM.api3('FileAnalyzer', 'getstats')
      .done(function(result) {
        showTestResults({
          success: true,
          message: '{/literal}{ts escape="js"}Diagnostic scan completed successfully{/ts}{literal}',
          data: result.values
        });
      })
      .fail(function(error) {
        showTestResults({
          success: false,
          message: '{/literal}{ts escape="js"}Diagnostic scan encountered errors{/ts}{literal}',
          error: error.error_message
        });
      })
      .always(function() {
        btn.prop('disabled', false).html('<i class="crm-i fa-search"></i> {/literal}{ts escape="js"}Run Diagnostic Scan{/ts}{literal}');
      });
  }

  // Run scheduled job manually
  function runScheduledJob() {
    var btn = CRM.$('#runJobBtn');
    btn.prop('disabled', true).html('<i class="crm-i fa-spinner fa-spin"></i> {/literal}{ts escape="js"}Processing...{/ts}{literal}');

    CRM.api3('FileAnalyzer', 'scan', { force_scan: 1 })
      .done(function(result) {
        showTestResults({
          success: true,
          message: '{/literal}{ts escape="js"}Manual cleanup operation completed{/ts}{literal}',
          data: result.values
        });
      })
      .fail(function(error) {
        showTestResults({
          success: false,
          message: '{/literal}{ts escape="js"}Cleanup operation failed{/ts}{literal}',
          error: error.error_message
        });
      })
      .always(function() {
        btn.prop('disabled', false).html('<i class="crm-i fa-play"></i> {/literal}{ts escape="js"}Execute Manual Cleanup{/ts}{literal}');
      });
  }

  // Clear old backup files
  function clearBackups() {
    if (!confirm('{/literal}{ts escape="js"}Are you sure you want to permanently remove old backup files? This action cannot be reversed.{/ts}{literal}')) {
      return;
    }

    var btn = CRM.$('#clearBackupsBtn');
    btn.prop('disabled', true).html('<i class="crm-i fa-spinner fa-spin"></i> {/literal}{ts escape="js"}Cleaning...{/ts}{literal}');

    // This would need a custom API endpoint
    CRM.alert('{/literal}{ts escape="js"}Backup archive management will be available in an upcoming release.{/ts}{literal}', '{/literal}{ts escape="js"}Feature In Development{/ts}{literal}', 'info');

    btn.prop('disabled', false).html('<i class="crm-i fa-trash"></i> {/literal}{ts escape="js"}Clean Backup Archive{/ts}{literal}');
  }

  // Show test results
  function showTestResults(result) {
    var resultsDiv = CRM.$('#testResults');
    var contentDiv = CRM.$('#testResultsContent');

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
    if (!confirm('{/literal}{ts escape="js"}Are you sure you want to restore all settings to their factory defaults?{/ts}{literal}')) {
      return;
    }

    CRM.$('#fileanalyzer_auto_delete_fileanalyzer_auto_delete').prop('checked', false).trigger('change');
    CRM.$('#fileanalyzer_auto_delete_days').val('90');
    CRM.$('#fileanalyzer_backup_before_delete').prop('checked', true);
    CRM.$('#fileanalyzer_excluded_extensions').val('tmp,log,cache,htaccess');
  }

  // Show help overlay
  function showHelp() {
    CRM.$('#helpOverlay').show();
  }

  // Close help overlay
  function closeHelp() {
    CRM.$('#helpOverlay').hide();
  }
  {/literal}
</script>

{* Add help button to page title *}
<script type="text/javascript">
  {literal}
  CRM.$(function($) {
    $('.page-title').append(' <a href="#" onclick="showHelp(); return false;" class="help-link" title="{/literal}{ts escape="js"}Show User Guide{/ts}{literal}"><i class="crm-i fa-question-circle"></i></a>');
  });
  {/literal}
</script>
