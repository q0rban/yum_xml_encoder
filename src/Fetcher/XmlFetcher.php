<?php

namespace Drupal\yum_xml_encoder\Fetcher;

/**
 * Class to fetch Yum XML from the source.
 *
 * TODO: Refactor this to pull directly from source rather than a local dir.
 */
class XmlFetcher {
  protected $directory;

  /**
   * Constructor.
   *
   * @param string $directory
   *   The path to the data directory the XML file lives in. Optional.
   */
  public function __construct($directory = "") {
    $this->directory = rtrim($directory, '/');
  }

  public function getFilePath($file) {
    if (empty($this->directory)) {
      return realpath($file);
    }
    return realpath($this->directory . '/' . $file);
  }

  public function getContents($file) {
    return file_get_contents(static::getFilePath($file));
  }
}