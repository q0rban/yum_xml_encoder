## Yum XML Encoder

This is just a rough prototype of reading in an XML data using Drupal's
Serializer API. All it does is `print_r()` the results right now.

### Installation
- Clone to modules/custom
- Enable with `drush en yum_xml_encoder -y`

### Usage
- Copy your Double Down XML to a directory accessible by the Drupal install 
(e.g. `../source-xml`)
- Run `drush yum-xml-decode ../source-xml/manifest.xml`
