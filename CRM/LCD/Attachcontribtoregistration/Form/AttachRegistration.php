<?php

require_once 'CRM/Core/Form.php';

/**
 * Form controller class
 *
 * @see http://wiki.civicrm.org/confluence/display/CRMDOC43/QuickForm+Reference
 */
class CRM_LCD_Attachcontribtoregistration_Form_AttachRegistration extends CRM_Core_Form {
  /**
   * check permissions
   */
  public function preProcess() {
    if (!CRM_Core_Permission::checkActionPermission('CiviContribute', CRM_Core_Action::UPDATE)) {
      CRM_Core_Error::fatal(ts('You do not have permission to access this page.'));
    }
    parent::preProcess();
  }

  public function buildQuickForm() {
    $this->_contributionId = CRM_Utils_Request::retrieve('id', 'Positive', $this);
    $this->_contactId = civicrm_api3('contribution', 'getvalue', array(
      'id' => $this->_contributionId,
      'return' => 'contact_id',
    ));

    //get current contact name.
    $this->assign('currentContactName', CRM_Contact_BAO_Contact::displayName($this->_contactId));

    $registrationList = array();
    $registrations = civicrm_api3('participant', 'get', array('contact_id' => $this->_contactId));
    foreach ($registrations['values'] as $registration) {
      $regStartDate = date('m/d/Y', strtotime($registration['event_start_date']));
      $registrationList[$registration['id']] = $registration['event_title'].' :: '.$regStartDate." ({$registration['event_id']})";
    }
    $this->add('select', 'participant_id', ts('Select Event Registration'), $registrationList, TRUE);

    $this->add('hidden', 'contribution_id', $this->_contributionId, array('id' => 'contribution_id'));
    $this->add('hidden', 'contact_id', $this->_contactId, array('id' => 'contact_id'));

    // export form elements
    $this->assign('elementNames', $this->getRenderableElementNames());

    $this->addButtons(array(
      array(
        'type' => 'submit',
        'name' => ts('Submit'),
        'isDefault' => TRUE,
      ),
    ));

    parent::buildQuickForm();
  }

  public function postProcess() {
    $values = $this->exportValues();
    //Civi::log()->debug('postProcess', array('values' => $values));

    //process
    $result = $this->AttachToRegistration($values);

    if ($result) {
      CRM_Core_Session::setStatus(ts('Contribution attached to event registration successfully.'), ts('Attached'), 'success');
    }
    else {
      CRM_Core_Session::setStatus(ts('Unable to attach contribution to event registration.'), ts('Error'), 'error');
    }

    parent::postProcess();
  }

  /**
   * Get the fields/elements defined in this form.
   *
   * @return array (string)
   */
  public function getRenderableElementNames() {
    // The _elements list includes some items which should not be
    // auto-rendered in the loop -- such as "qfKey" and "buttons".  These
    // items don't have labels.  We'll identify renderable by filtering on
    // the 'label'.
    $elementNames = array();
    foreach ($this->_elements as $element) {
      /** @var HTML_QuickForm_Element $element */
      $label = $element->getLabel();
      if (!empty($label)) {
        $elementNames[] = $element->getName();
      }
    }
    return $elementNames;
  }

  function AttachToRegistration($params) {
    try {
      $pp = civicrm_api3('participant_payment', 'create', array(
        'participant_id' => $params['participant_id'],
        'contribution_id' => $params['contribution_id'],
      ));

      if ($pp) {
        $subject = "Contribution #{$params['contribution_id']} Attached to Registration";
        $details = "Contribution #{$params['contribution_id']} was attached to registration #{$params['participant_id']}.";

        $activityTypeID = CRM_Core_OptionGroup::getValue('activity_type',
          'contribution_attached_to_registration',
          'name'
        );

        $activityParams = [
          'activity_type_id' => $activityTypeID,
          'activity_date_time' => date('YmdHis'),
          'subject' => $subject,
          'details' => $details,
          'status_id' => 2,
        ];

        $activityParams['source_contact_id'] = CRM_Core_Session::getLoggedInContactID();
        $activityParams['target_contact_id'][] = $params['contact_id'];

        civicrm_api3('activity', 'create', $activityParams);

        return TRUE;
      }
    }
    catch (CiviCRM_API3_Exception $e) {}

    return FALSE;
  }
}
