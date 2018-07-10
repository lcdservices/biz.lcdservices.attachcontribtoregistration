<?php

require_once 'attachcontribtoregistration.civix.php';

/**
 * Implements hook_civicrm_config().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_config
 */
function attachcontribtoregistration_civicrm_config(&$config) {
  _attachcontribtoregistration_civix_civicrm_config($config);
}

/**
 * Implements hook_civicrm_xmlMenu().
 *
 * @param $files array(string)
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_xmlMenu
 */
function attachcontribtoregistration_civicrm_xmlMenu(&$files) {
  _attachcontribtoregistration_civix_civicrm_xmlMenu($files);
}

/**
 * Implements hook_civicrm_install().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_install
 */
function attachcontribtoregistration_civicrm_install() {
  _attachcontribtoregistration_civix_civicrm_install();
}

/**
 * Implements hook_civicrm_uninstall().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_uninstall
 */
function attachcontribtoregistration_civicrm_uninstall() {
  _attachcontribtoregistration_civix_civicrm_uninstall();
}

/**
 * Implements hook_civicrm_enable().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_enable
 */
function attachcontribtoregistration_civicrm_enable() {
  _attachcontribtoregistration_civix_civicrm_enable();
}

/**
 * Implements hook_civicrm_disable().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_disable
 */
function attachcontribtoregistration_civicrm_disable() {
  _attachcontribtoregistration_civix_civicrm_disable();
}

/**
 * Implements hook_civicrm_upgrade().
 *
 * @param $op string, the type of operation being performed; 'check' or 'enqueue'
 * @param $queue CRM_Queue_Queue, (for 'enqueue') the modifiable list of pending up upgrade tasks
 *
 * @return mixed
 *   Based on op. for 'check', returns array(boolean) (TRUE if upgrades are pending)
 *                for 'enqueue', returns void
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_upgrade
 */
function attachcontribtoregistration_civicrm_upgrade($op, CRM_Queue_Queue $queue = NULL) {
  return _attachcontribtoregistration_civix_civicrm_upgrade($op, $queue);
}

/**
 * Implements hook_civicrm_managed().
 *
 * Generate a list of entities to create/deactivate/delete when this module
 * is installed, disabled, uninstalled.
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_managed
 */
function attachcontribtoregistration_civicrm_managed(&$entities) {
  _attachcontribtoregistration_civix_civicrm_managed($entities);
}

/**
 * Implements hook_civicrm_caseTypes().
 *
 * Generate a list of case-types
 *
 * Note: This hook only runs in CiviCRM 4.4+.
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_caseTypes
 */
function attachcontribtoregistration_civicrm_caseTypes(&$caseTypes) {
  _attachcontribtoregistration_civix_civicrm_caseTypes($caseTypes);
}

/**
 * Implements hook_civicrm_angularModules().
 *
 * Generate a list of Angular modules.
 *
 * Note: This hook only runs in CiviCRM 4.5+. It may
 * use features only available in v4.6+.
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_caseTypes
 */
function attachcontribtoregistration_civicrm_angularModules(&$angularModules) {
_attachcontribtoregistration_civix_civicrm_angularModules($angularModules);
}

/**
 * Implements hook_civicrm_alterSettingsFolders().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_alterSettingsFolders
 */
function attachcontribtoregistration_civicrm_alterSettingsFolders(&$metaDataFolders = NULL) {
  _attachcontribtoregistration_civix_civicrm_alterSettingsFolders($metaDataFolders);
}

function attachcontribtoregistration_civicrm_searchColumns($objectName, &$headers, &$rows, &$selector) {
  /*Civi::log()->debug('attachcontribtoregistration_civicrm_searchColumns', array(
    'objectName' => $objectName,
    '$headers' => $headers,
    '$rows' => $rows,
    //'$selector' => $selector,
  ));*/

  if ($objectName == 'contribution') {
    foreach ($rows as &$row) {
      //only make available if not already attached to an event or membership
      $connectionExists = CRM_Core_DAO::singleValueQuery("
        SELECT COUNT(pp.id) + COUNT(mp.id)
        FROM civicrm_contribution c
        LEFT JOIN civicrm_participant_payment pp
          ON c.id = pp.contribution_id
        LEFT JOIN civicrm_membership_payment mp
          ON c.id = mp.contribution_id
        WHERE c.id = %1
      ", array(
        1 => array($row['contribution_id'], 'Positive'),
      ));

      if ($connectionExists) {
        continue;
      }

      //action column is either a series of links, or a series of links plus a subset
      //unordered list (more button) -- all of which is enclosed in a span
      //we want to inject our option at the end, regardless, so we look for the existence
      //of a <ul> tag and adjust our injection accordingly
      $url = CRM_Utils_System::url('civicrm/attachtoreg', "reset=1&id={$row['contribution_id']}");
      $urlLink = "<a href='{$url}' class='action-item crm-hover-button medium-popup move-contrib'>Attach to Registration</a>";
      if (strpos($row['action'], '</ul>') !== FALSE) {
        $row['action'] = str_replace('</ul></span>', '<li>'.$urlLink.'</li></ul></span>', $row['action']);
      }
      else {
        //if there is no more... link, let's create one
        $more = "
          <span class='btn-slide crm-hover-button'>more
            <ul class='panel' style='display: none;'>
              <li>{$urlLink}</li>
            </ul>
          </span>
        ";
        $row['action'] = str_replace('</span>', '</span>'.$more, $row['action']);
      }
    }
  }
}
