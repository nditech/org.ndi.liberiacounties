<?php
/**
 * Return a list of all the counties
 */
function liberiacounties_listcounties() {
  $countryIso = 'LR';
  $counties = array(
    'Bomi' => array(
      'Chitipa East',
      'Chitipa South',
      'Chitipa Central',
      'Chitipa North',
      'Chitipa Wenya',
    ),
    'Bong' => array(
     'Karonga North',
      'Karonga North West',
      'Karonga Central',
      'Karonga Nyungwe',
      'Karonga South',
    ),
    'Gbarpolu' => array(
      'Rumphi East',
      'Rumphi Central',
      'Rumphi West',
      'Rumphi North',
    ),
    'Grand Bassa' => array(
      'Nkhatabay North',
      'Nkhatabay Central',
      'Nkhatabay West',
      'Nkhatabay North West',
      'Nkhatabay South East',
      'Nkhatabay South',
    ),
    'Grand Cape Mount' => array(
      'Likoma Islands',
    ),
    'Grand Gedeh' => array(
      'Mzuzu City',
    ),
    'Grand Kru' => array(
      'Mzimba North',
      'Mzimba North East',
      'Mzimba West',
      'Mzimba South',
      'Mzimba Central',
      'Mzimba Hora',
      'Mzimba Luwelezi',
      'Mzimba Solola',
      'Mzimba East',
      'Mzimba South West',
      'Mzimba South East',
    ),
    'Lofa' => array(
      'Blantyre North East',
      'Blantyre Rural East',
      'Blantyre South West',
      'Blantyre City Centre',
      'Blantyre Malabada',
      'Blantyre City South',
      'Blantyre City East',
      'Blantyre Bangwe',
      'Blantyre City South East',
      'Blantyre City West',
      'Blantye Kabula',
      'Blantyre West',
    ),
    'Margibi' => array(
      'Mwanza Central',
      'Mwanza West',
    ),
    'Maryland' => array(
      'Neno South',
      'Neno North',
    ),
    'Montserrado' => array(
      'Thyolo North',
      'Thyolo West',
      'Thyolo Central',
      'Thyolo South',
      'Thyolo East',
      'Thyolo South West',
      'Thyolo Thava',
    ),
    'Nimba' => array(
      'Phalombe South',
      'Phalombe Central',
      'Phalombe North',
      'Phalombe East',
      'Phalombe North East',
    ),
    'Rivercess' => array(
      'Mulanje South East',
      'Mulanje South',
      'Mulanje Central',
      'Mulanje Limbuli',
      'Mulanje Bale',
      'Mulanje South West',
      'Mulanje Pasani',
      'Mulanje West',
      'Mulanje North',
    ),
    'River Gee' => array(
      'Kasungu North',
      'Kasungu North North East',
      'Kasungu West',
      'Kasungu North West',
      'Kasungu South',
      'Kasungu South East',
      'Kasungu East',
      'Kasungu Central',
      'Kasungu North East',
    ),
    'Sinoe' => array(
      'Nkhotakota North',
      'Nkhotakota North East',
      'Nkhotakota Central',
      'Nkhotakota South',
      'Nkhotakota South East',
    ),
  );
  return array($countryIso => $counties);
}
/**
 * Check and load counties
 */
function liberiacounties_loadcounties() {
  $allCounties = liberiacounties_listcounties();
  foreach ($allCounties as $countryIso => $counties) {
    static $dao = NULL;
    if (!$dao) {
      $dao = new CRM_Core_DAO();
    }
    // Get array of states.
    try {
      $result = civicrm_api3('Country', 'getsingle', array(
        'iso_code' => $countryIso,
        'api.Address.getoptions' => array(
          'field' => 'state_province_id',
          'country_id' => '$value.id',
          'sequential' => 0,
        ),
      ));
    }
    catch (CiviCRM_API3_Exception $e) {
      $error = $e->getMessage();
      CRM_Core_Error::debug_log_message(ts('API Error: %1', array(
        'domain' => 'org.ndi.liberiacounties',
        1 => $error,
      )));
      return FALSE;
    }
    if (empty($result['api.Address.getoptions']['values'])) {
      return FALSE;
    }
    $states = $result['api.Address.getoptions']['values'];
    // go state-by-state to check existing counties
    foreach ($counties as $stateName => $state) {
      $id = array_search($stateName, $states);
      if ($id === FALSE) {
        continue;
      }
      $check = "SELECT name FROM civicrm_county WHERE state_province_id = $id";
      $results = CRM_Core_DAO::executeQuery($check);
      $existing = array();
      while ($results->fetch()) {
        $existing[] = $results->name;
      }
      // identify counties needing to be loaded
      $add = array_diff($state, $existing);
      $insert = array();
      foreach ($add as $county) {
        $countye = $dao->escape($county);
        $insert[] = "('$countye', $id)";
      }
      // put it into queries of 50 counties each
      for($i = 0; $i < count($insert); $i = $i+50) {
        $inserts = array_slice($insert, $i, 50);
        $query = "INSERT INTO civicrm_county (name, state_province_id) VALUES ";
        $query .= implode(', ', $inserts);
        CRM_Core_DAO::executeQuery($query);
      }
    }
  }
  return TRUE;
}
/**
 * Implementation of hook_civicrm_install
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_install
 */
function liberiacounties_civicrm_install() {
  liberiacounties_loadcounties();
}
/**
 * Implementation of hook_civicrm_enable
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_enable
 */
function liberiacounties_civicrm_enable() {
  liberiacounties_loadcounties();
}
/**
 * Implementation of hook_civicrm_upgrade
 *
 * @param $op string, the type of operation being performed; 'check' or 'enqueue'
 * @param $queue CRM_Queue_Queue, (for 'enqueue') the modifiable list of pending up upgrade tasks
 *
 * @return mixed  based on op. for 'check', returns array(boolean) (TRUE if upgrades are pending)
 *                for 'enqueue', returns void
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_upgrade
 */
function liberiacounties_civicrm_upgrade($op, CRM_Queue_Queue $queue = NULL) {
  liberiacounties_loadcounties();
}
