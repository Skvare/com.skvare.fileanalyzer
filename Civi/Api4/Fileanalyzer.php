<?php
namespace Civi\Api4;

/**
 * Fileanalyzer entity.
 *
 * Provided by the File Analyzer extension.
 *
 * @package Civi\Api4
 */
class Fileanalyzer extends Generic\DAOEntity {

  /**
   * @param bool $checkPermissions
   * @return Action\Fileanalyzer\Create
   */
  public static function create($checkPermissions = TRUE) {
    return (new Action\Fileanalyzer\Create(self::getEntityName(), __FUNCTION__))
      ->setCheckPermissions($checkPermissions);
  }

  /**
   * @param bool $checkPermissions
   * @return Action\Fileanalyzer\Update
   */
  public static function update($checkPermissions = TRUE) {
    return (new Action\Fileanalyzer\Update(self::getEntityName(), __FUNCTION__))
      ->setCheckPermissions($checkPermissions);
  }

  /**
   * @param bool $checkPermissions
   * @return Action\Fileanalyzer\Save
   */
  public static function save($checkPermissions = TRUE) {
    return (new Action\Fileanalyzer\Save(self::getEntityName(), __FUNCTION__))
      ->setCheckPermissions($checkPermissions);
  }

  /**
   * @param bool $checkPermissions
   * @return Action\Fileanalyzer\Delete
   */
  public static function delete($checkPermissions = TRUE) {
    return (new Action\Fileanalyzer\Delete(self::getEntityName(), __FUNCTION__))
      ->setCheckPermissions($checkPermissions);
  }
}
