<?php
use CRM_Fileanalyzer_ExtensionUtil as E;

class CRM_Fileanalyzer_Page_Export extends CRM_Core_Page {

  public function run() {
    $directory = CRM_Utils_Request::retrieve('directory', 'String', $this, FALSE, 'custom');
    $format = CRM_Utils_Request::retrieve('format', 'String', $this, FALSE, 'csv');

    try {
      $result = civicrm_api3('FileAnalyzer', 'exportReport', [
        'directory' => $directory,
        'format' => $format,
      ]);

      $filename = $result['values']['export_file'];
      $config = CRM_Core_Config::singleton();
      $filePath = $config->customFileUploadDir . '/file_analyzer_backups/exports/' . $filename;

      if (file_exists($filePath)) {
        // Set appropriate headers for download
        $mimeType = ($format == 'json') ? 'application/json' : 'text/csv';

        header('Content-Type: ' . $mimeType);
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . filesize($filePath));

        readfile($filePath);
        exit;
      }
      else {
        CRM_Core_Error::statusBounce('Export file not found.');
      }

    }
    catch (Exception $e) {
      CRM_Core_Error::statusBounce('Export failed: ' . $e->getMessage());
    }
  }
}
