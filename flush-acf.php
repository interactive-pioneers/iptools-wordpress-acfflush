<?php

class ChioAcfFlush {

  private $chioAcfFields = array('inhalte');

  private $fieldKeys = array();

  public function __construct() {
    add_action('acf/save_post', array($this, 'flush', 1));
  }

  public function flush($postId) {
    $dbFieldKeys = $this->getDBFieldKeys($postId);
    $dbFieldValues = $this->getDBFieldValues($postId, $dbFieldKeys);

    $all = array();
    foreach($dbFieldKeys as $item) {
      $all[] = $item['meta_key'];
    }
    foreach($dbFieldValues as $item) {
      $all[] = $item['meta_key'];
    }

    $this->flushFields($postId, $all);

    foreach($_POST['acf'] as $key => $val) {
      update_field($key, $val, $postId);
    }
  }

  private function getAcfFieldKeys($postId) {
    $aFieldObjects = get_field_objects($postId);
    array_walk_recursive($aFieldObjects, function($val, $key) {
      if ($key === 'key' && substr($val, 0, 6) === 'field_') {
        $this->fieldKeys[] = $val;
      }
    });
  }

  private function getDBFieldKeys($postId) {
    global $wpdb;

    $this->getAcfFieldKeys($postId);
    $sList = "'" . implode("','", $this->fieldKeys) . "'";

    return $wpdb->get_results('
      SELECT
        *
        #meta_key
      FROM
        wp_postmeta
      WHERE
        post_id = ' . $postId . '
        AND meta_key LIKE "_%"
        AND meta_value IN (' . $sList . ')
    ', ARRAY_A);
  }

  private function getDBFieldValues($postId, $dbFieldKeys) {
    global $wpdb;

    $aList = array();
    foreach($dbFieldKeys as $item) {
      $aList[] = substr($item['meta_key'], 1);
    }
    $sList = "'" . implode("','", $aList) . "'";

    return $wpdb->get_results('
      SELECT
        *
        #meta_key
      FROM
        wp_postmeta
      WHERE
        post_id = ' . $postId . '
        AND meta_key IN (' . $sList . ')
    ', ARRAY_A);
  }

  private function flushFields($postId, $fields) {
    global $wpdb;

    $sList = "'" . implode("','", $fields) . "'";

    $oResults = $wpdb->get_results('
      DELETE FROM
        wp_postmeta
      WHERE
        post_id = ' . $postId . '
        AND meta_key IN (' . $sList . ')
    ', ARRAY_A);
  }
}

$oChioAcfFlush = new ChioAcfFlush();
