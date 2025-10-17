<?php
namespace Civi\Api4\Action\Fileanalyzer;

use Civi\Api4\Fileanalyzer;

/**
 * Delete Fileanalyzer records and their associated physical files.
 */
class Delete extends \Civi\Api4\Generic\DAODeleteAction {

  /**
   * Delete Fileanalyzer records and their associated physical files.
   */
  public function _run(\Civi\Api4\Generic\Result $result) {
    // First, get the records that will be deleted to retrieve file paths
    $recordsToDelete = Fileanalyzer::get($this->getCheckPermissions())
      ->setSelect(['id', 'file_path', 'filename', 'file_id', 'directory_type', 'is_abandoned'])
      // contains the file path
      ->setWhere($this->where)
      ->execute()->getArrayCopy();

    // Delete the physical files
    foreach ($recordsToDelete as $record) {
      $fileResult[] = $this->deletePhysicalFile($record);
    }
    if (!empty($fileResult)) {
      $result->exchangeArray($fileResult);
    }
    // Now delete the database records using parent method
    //parent::_run($result);
  }

  /**
   * Delete the physical file from the filesystem.
   *
   * @param array $record
   */
  protected function deletePhysicalFile($record) {
    $result = [];
    // Adjust this based on where your file path is stored
    $filePath = $record['file_path'] ?? $record['uri'] ?? NULL;
    $directoryType = $record['directory_type'] ?? NULL;
    if (empty($filePath)) {
      return;
    }

    // Handle different path formats
    // If it's a relative path, prepend the CiviCRM file directory
    if (!$this->isAbsolutePath($filePath) && $directoryType == 'custom') {
      $config = \CRM_Core_Config::singleton();
      $filePath = $config->customFileUploadDir . $filePath;
    }
    // Check if file exists and delete it
    if (file_exists($filePath) && is_file($filePath)) {
      // Now delete the associated record from civicrm_files if necessary
      $fileInfo = pathinfo($filePath);
      if ($this->deleteFileRecord($record)) {
        // Delete the physical file
        // @unlink($filePath);
        // Special handling for contribute directory type
        if ($directoryType === 'contribute') {
          // Delete static and thumbnail images
          $baseDir = $fileInfo['dirname'];
          $fileName = $fileInfo['basename'];

          // Paths for static and thumbnail images
          $staticPath = $baseDir . '/static/' . $fileName;
          $thumbnailPath = $baseDir . '/thumbnails/' . $fileName;

          // Delete static image if exists
          if (file_exists($staticPath) && is_file($staticPath)) {
            @unlink($staticPath);
          }

          // Delete thumbnail image if exists
          if (file_exists($thumbnailPath) && is_file($thumbnailPath)) {
            @unlink($thumbnailPath);
          }
        }

        $result = ['id' => $record['id']];
      }
      // Optionally log the deletion
      \Civi::log()->info('Deleted file: ' . $filePath);
    }
    return $result;
  }

  /**
   * Check if a path is absolute.
   *
   * @param string $path
   * @return bool
   */
  protected function isAbsolutePath($path) {
    // Unix/Linux absolute path
    if (strpos($path, '/') === 0) {
      return TRUE;
    }
    // Windows absolute path
    if (preg_match('/^[a-zA-Z]:[\/\\\\]/', $path)) {
      return TRUE;
    }
    return FALSE;
  }

  /**
   * Delete the associated record from civicrm_files if necessary.
   *
   * @param array $record
   */
  protected function deleteFileRecord($record) {
    try {
      // Use FileAnalysis API to delete the Fileanalyzer record if it exists
      \CRM_Fileanalyzer_API_FileAnalysis::deleteFileById($record['id'], FALSE, 'manual delete');
    }
    catch (\Exception $e) {
      \Civi::log()->error('Error deleting file analyzer record via API: ' . $e->getMessage());
      return FALSE;
    }

    // Now delete entry from civicrm_file table.
    $fileId = $record['file_id'] ?? NULL;
    if ($fileId) {
      try {
        // Use CiviCRM's API to delete the file record if it exists
        \CRM_Core_DAO::executeQuery('DELETE FROM civicrm_file WHERE id = %1', [
          1 => [$fileId, 'Integer']
        ]);
      }
      catch (\Exception $e) {
        // Log any errors during file record deletion
        \Civi::log()->error('Error deleting file record: ' . $e->getMessage());
      }
    }
    return TRUE;
  }

}
