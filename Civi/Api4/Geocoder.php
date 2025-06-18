<?php

namespace Civi\Api4;

use Civi\Api4\Action\UpdateTimezones;

/**
 * Geocoder entity.
 *
 * Provided by the Geocoder extension.
 *
 * @search secondary
 * @orderBy weight
 *
 * @package Civi\Api4
 */
class Geocoder extends Generic\DAOEntity {
  use Generic\Traits\SortableEntity;

  /**
   * Add timezone data from a web service
   */
  public static function updateTimezones(bool $checkPermissions = FALSE): UpdateTimezones {
    return (new UpdateTimezones(__CLASS__, __FUNCTION__))
      ->setCheckPermissions($checkPermissions);
  }

}
