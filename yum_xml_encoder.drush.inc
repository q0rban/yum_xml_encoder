<?php

/**
 * @file
 * Drush commands for Yum XML Encoder module.
 */

use Drupal\yum_xml_encoder\Fetcher\XmlFetcher;

/**
 * Implements hook_drush_help().
 */
function yum_xml_encoder_drush_help($section) {
  switch ($section) {
    case 'drush:yum-xml-decode':
      return dt('Decode a Yum XML file.');
  }
}

/**
 * Implements hook_drush_command().
 */
function yum_xml_encoder_drush_command() {
  $items['yum-xml-decode'] = array(
    'description' => 'Decode a Yum XML file.',
    'arguments' => array(
      'file' => 'The path to the XML file.',
    ),
  );

  return $items;
}

/**
 * Command callback for drush yum-xml-decode.
 */
function drush_yum_xml_encoder_yum_xml_decode($file) {
  $fetcher = new XmlFetcher();
  $xml = $fetcher->getContents($file);

  $serializer = \Drupal::service('serializer');
  $output = $serializer->decode($xml, 'yum_xml');

  drush_print_r($output);
}