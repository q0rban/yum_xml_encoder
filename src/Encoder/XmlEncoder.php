<?php

namespace Drupal\yum_xml_encoder\Encoder;

use Symfony\Component\Serializer\Exception\UnexpectedValueException;
use Drupal\serialization\Encoder\XmlEncoder as DrupalXmlEncoder;

// This is basically all stolen and tweaked from the Symfony decoding methods in
// Symfony\Component\Serializer\Encoder\XmlEncoder::decode(). The reason we
// can't use that method directly is because it thinks that the first comment it
// runs into is a root node.
class XmlEncoder extends DrupalXmlEncoder {

  /**
   * {@inheritdoc}
   */
  static protected $format = ['yum_xml'];

  /**
   * A bit field of LIBXML_* constants
   *
   * @var int|null
   */
  protected $loadOptions = LIBXML_NONET | LIBXML_NOBLANKS;

  /**
   * A set of ignored XML types.
   *
   * @var array
   */
  protected $ignoredTypes = array(XML_PI_NODE, XML_COMMENT_NODE);

  /**
   * {@inheritdoc}
   */
  public function decode($data, $format, array $context = []) {
    if ('' === trim($data)) {
      throw new UnexpectedValueException('Invalid XML data, it can not be empty.');
    }

    $internalErrors = libxml_use_internal_errors(true);
    $disableEntities = libxml_disable_entity_loader(true);
    libxml_clear_errors();

    $dom = new \DOMDocument();
    $dom->loadXML($data, $this->loadOptions);

    libxml_use_internal_errors($internalErrors);
    libxml_disable_entity_loader($disableEntities);

    if ($error = libxml_get_last_error()) {
      libxml_clear_errors();

      throw new UnexpectedValueException($error->message);
    }

    $rootNode = null;
    foreach ($dom->childNodes as $child) {
      if ($child->nodeType === XML_DOCUMENT_TYPE_NODE) {
        throw new UnexpectedValueException('Document types are not allowed.');
      }
      if (!$rootNode && !in_array($child->nodeType, $this->ignoredTypes, TRUE)) {
        $rootNode = $child;
      }
    }

    // todo: throw an exception if the root node name is not correctly configured (bc)

    if ($rootNode->hasChildNodes()) {
      $xpath = new \DOMXPath($dom);
      $data = array();
      foreach ($xpath->query('namespace::*', $dom->documentElement) as $nsNode) {
        $data['@'.$nsNode->nodeName] = $nsNode->nodeValue;
      }

      unset($data['@xmlns:xml']);

      if (empty($data)) {
        return $this->parseXml($rootNode);
      }

      return array_merge($data, (array) $this->parseXml($rootNode));
    }

    if (!$rootNode->hasAttributes()) {
      return $rootNode->nodeValue;
    }

    $data = array();

    foreach ($rootNode->attributes as $attrKey => $attr) {
      $data['@'.$attrKey] = $attr->nodeValue;
    }

    $data['#'] = $rootNode->nodeValue;

    return $data;
  }

  /**
   * Parse the input DOMNode into an array or a string.
   *
   * @param \DOMNode $node xml to parse
   *
   * @return array|string
   */
  private function parseXml(\DOMNode $node)
  {
    $data = $this->parseXmlAttributes($node);

    $value = $this->parseXmlValue($node);

    if (!count($data)) {
      return $value;
    }

    if (!is_array($value)) {
      $data['#'] = $value;

      return $data;
    }

    if (1 === count($value) && key($value)) {
      $data[key($value)] = current($value);

      return $data;
    }

    foreach ($value as $key => $val) {
      $data[$key] = $val;
    }

    return $data;
  }

  /**
   * Parse the input DOMNode attributes into an array.
   *
   * @param \DOMNode $node xml to parse
   *
   * @return array
   */
  private function parseXmlAttributes(\DOMNode $node)
  {
    if (!$node->hasAttributes()) {
      return array();
    }

    $data = array();

    foreach ($node->attributes as $attr) {
      if (!is_numeric($attr->nodeValue)) {
        $data['@'.$attr->nodeName] = $attr->nodeValue;

        continue;
      }

      if (false !== $val = filter_var($attr->nodeValue, FILTER_VALIDATE_INT)) {
        $data['@'.$attr->nodeName] = $val;

        continue;
      }

      $data['@'.$attr->nodeName] = (float) $attr->nodeValue;
    }

    return $data;
  }

  /**
   * Parse the input DOMNode value (content and children) into an array or a string.
   *
   * @param \DOMNode $node xml to parse
   *
   * @return array|string
   */
  private function parseXmlValue(\DOMNode $node)
  {
    if (!$node->hasChildNodes()) {
      return $node->nodeValue;
    }

    if (1 === $node->childNodes->length && in_array($node->firstChild->nodeType, array(XML_TEXT_NODE, XML_CDATA_SECTION_NODE))) {
      return $node->firstChild->nodeValue;
    }

    $value = array();

    foreach ($node->childNodes as $subnode) {
      if ($subnode->nodeType === XML_PI_NODE) {
        continue;
      }

      $val = $this->parseXml($subnode);

      if ('item' === $subnode->nodeName && isset($val['@key'])) {
        if (isset($val['#'])) {
          $value[$val['@key']] = $val['#'];
        } else {
          $value[$val['@key']] = $val;
        }
      } else {
        $value[$subnode->nodeName][] = $val;
      }
    }

    foreach ($value as $key => $val) {
      if (is_array($val) && 1 === count($val)) {
        $value[$key] = current($val);
      }
    }

    return $value;
  }
}
