<?php
/**
 * Return a list of all the counties
 */
function liberiacounties_listcounties() {
  $countryIso = 'LR';
  $counties = array(
    'Bomi' => array(
      'Bomi 1',
      'Bomi 2',
      'Bomi 3',
    ),
    'Bong' => array(
      'Bong 1',
      'Bong 2',
      'Bong 3',
      'Bong 4',
      'Bong 5',
      'Bong 6',
      'Bong 7',
    ),
    'Gbarpolu' => array(
      'Gbarpolu 1',
      'Gbarpolu 2',
      'Gbarpolu 3',
    ),
    'Grand Bassa' => array(
      'Grand Bassa 1',
      'Grand Bassa 2',
      'Grand Bassa 3',
      'Grand Bassa 4',
      'Grand Bassa 5',
    ),
    'Grand Cape Mount' => array(
      'Grand Cape Mount 1',
      'Grand Cape Mount 2',
      'Grand Cape Mount 3',
    ),
    'Grand Gedeh' => array(
      'Grand Gedeh 1',
      'Grand Gedeh 2',
      'Grand Gedeh 3',
    ),
    'Grand Kru' => array(
      'Grand Kru 1',
      'Grand Kru 2',
    ),
    'Lofa' => array(
      'Lofa 1',
      'Lofa 2',
      'Lofa 3',
      'Lofa 4',
      'Lofa 5',
    ),
    'Margibi' => array(
      'Margibi 1',
      'Margibi 2',
      'Margibi 3',
      'Margibi 4',
      'Margibi 5',
    ),
    'Maryland' => array(
      'Maryland 1',
      'Maryland 2',
      'Maryland 3',
    ),
    'Montserrado' => array(
      'Montserrado 1',
      'Montserrado 2',
      'Montserrado 3',
      'Montserrado 4',
      'Montserrado 5',
      'Montserrado 6',
      'Montserrado 7',
      'Montserrado 8',
      'Montserrado 9',
      'Montserrado 10',
      'Montserrado 11',
      'Montserrado 12',
      'Montserrado 13',
      'Montserrado 14',
      'Montserrado 15',
      'Montserrado 16',
      'Montserrado 17',
    ),
    'Nimba' => array(
      'Nimba 1',
      'Nimba 2',
      'Nimba 3',
      'Nimba 4',
      'Nimba 5',
      'Nimba 6',
      'Nimba 7',
      'Nimba 8',
      'Nimba 9',
    ),
    'Rivercess' => array(
      'Rivercess 1',
      'Rivercess 2',
    ),
    'River Gee' => array(
      'River Gee 1',
      'River Gee 2',
      'River Gee 3',
    ),
    'Sinoe' => array(
      'Sinoe 1',
      'Sinoe 2',
      'Sinoe 3',
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
