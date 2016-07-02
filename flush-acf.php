<?php
/**
 * Plugin Name: IPToolsACFFlush
 * Plugin URI: https://github.com/interactive-pioneers/iptools-wordpress-acfflush
 * Description: Flush obsolete postmeta data for Wordpress plugin ACF on save posts.
 * Author: Interactive Pioneers GmbH
 * Author URI: https://www.interactive-pioneers.de
 * Author Email: gm@interactive-pioneers.de
 * Version: 0.0.1
 * Text Domain: acf-flush
 */

if(!defined('WPINC')) die;

class IPToolsAcfFlush {

  private $fieldKeys = array();

  public function __construct() {
    add_action('acf/save_post', array($this, 'flush'), 1);
  }

  public function flush($postId) {
    $dbFieldKeys = $this->getDBFieldKeys($postId);
    $dbFieldValues = $this->getDBFieldValues($postId, $dbFieldKeys);

    $all = array_merge($dbFieldKeys, $dbFieldValues);

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

    $results = $wpdb->get_results('
      SELECT
        meta_key
      FROM
        wp_postmeta
      WHERE
        post_id = ' . $postId . '
        AND meta_key LIKE "_%"
        AND meta_value IN (' . $sList . ')
    ', ARRAY_A);

    $flattenResults = array_map(array($this, 'flattenResult'), $results);

    return $flattenResults;
  }

  private function getDBFieldValues($postId, $dbFieldKeys) {
    global $wpdb;

    $aList = array();
    foreach($dbFieldKeys as $item) {
      $aList[] = substr($item, 1);
    }
    $sList = "'" . implode("','", $aList) . "'";

    $results = $wpdb->get_results('
      SELECT
        meta_key
      FROM
        wp_postmeta
      WHERE
        post_id = ' . $postId . '
        AND meta_key IN (' . $sList . ')
    ', ARRAY_A);

    $flattenResults = array_map(array($this, 'flattenResult'), $results);

    return $flattenResults;
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

  private function flattenResult($item) {
    return $item['meta_key'];
  }
}

$oIPToolsAcfFlush = new IPToolsAcfFlush();
