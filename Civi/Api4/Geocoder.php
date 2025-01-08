<?php

namespace Civi\Api4;

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
}
