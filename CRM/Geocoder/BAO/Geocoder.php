<?php
use CRM_Geocoder_ExtensionUtil as E;

class CRM_Geocoder_BAO_Geocoder extends CRM_Geocoder_DAO_Geocoder implements \Civi\Core\HookInterface {


  /**
   * Event fired before an action is taken on an ACL record.
   * @param \Civi\Core\Event\PreEvent $event
   */
  public static function self_hook_civicrm_pre(\Civi\Core\Event\PreEvent $event) {
    CRM_Utils_Geocode_Geocoder::resetGeoCoders();
  }

}
