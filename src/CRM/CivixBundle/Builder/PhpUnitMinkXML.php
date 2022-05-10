<?php
namespace CRM\CivixBundle\Builder;

use SimpleXMLElement;
use CRM\CivixBundle\Builder\XML;

/**
 * Build/update phpunit.xml
 */
class PhpUnitMinkXML extends XML {

  public function init(&$ctx) {
    $xml = new SimpleXMLElement('<phpunit></phpunit>');
    // oddly, this is how you do this, to make two attributes, one for xmlns:xsi, and then one for xsi:noNamespaceSchemaLocation
    $xml->addAttribute('xsi:noNamespaceSchemaLocation', 'https://schema.phpunit.de/9.3/phpunit.xsd', 'http://www.w3.org/2001/XMLSchema-instance');
    $xml->addAttribute('colors', 'true');
    $xml->addAttribute('beStrictAboutTestsThatDoNotTestAnything', 'true');
    $xml->addAttribute('beStrictAboutOutputDuringTests', 'true');
    $xml->addAttribute('beStrictAboutChangesToGlobalState', 'true');
    $xml->addAttribute('failOnWarning', 'true');
    $xml->addAttribute('printerClass', '\Drupal\Tests\Listeners\HtmlOutputPrinter');
    $xml->addAttribute('cacheResult', 'false');
    $xml->addAttribute('bootstrap', 'tests/phpunit/bootstrap.mink.php');
    $this->set($xml);

    $this->addTestSuite('My Test Suite', ['./tests/phpunit/Mink']);

    $this->get()
      ->addChild('groups')
      ->addChild('include')
      ->addChild('group', 'mink');

    $phpXml = $this->get()->addChild('php');
    $phpXml->addChild('commentWorkaround', 'Set error reporting to E_ALL.');
    $ini = $phpXml->addChild('ini');
    $ini->addAttribute('name', 'error_reporting');
    $ini->addAttribute('value', '32767');
    $phpXml->addChild('commentWorkaround', 'Do not limit the amount of memory tests take to run.');
    $ini = $phpXml->addChild('ini');
    $ini->addAttribute('name', 'memory_limit');
    $ini->addAttribute('value', '-1');
    $phpXml->addChild('commentWorkaround', 'Set this to the path to where all the civi extensions live.');
    $ini = $phpXml->addChild('env');
    $ini->addAttribute('name', 'DEV_EXTENSION_DIR');
    $ini->addAttribute('value', dirname($ctx['basedir']));
    $phpXml->addChild('commentWorkaround', 'Change to match your test site url.');
    $ini = $phpXml->addChild('env');
    $ini->addAttribute('name', 'DEV_EXTENSION_URL');
    $ini->addAttribute('value', 'http://localhost/sites/default/files/civicrm/ext');
    $phpXml->addChild('commentWorkaround', 'Change to match your test site url.');
    $ini = $phpXml->addChild('env');
    $ini->addAttribute('name', 'SIMPLETEST_BASE_URL');
    $ini->addAttribute('value', 'http://localhost');
    $phpXml->addChild('commentWorkaround', 'Change to match your test database.');
    $ini = $phpXml->addChild('env');
    $ini->addAttribute('name', 'SIMPLETEST_DB');
    $ini->addAttribute('value', 'mysql://username:password@localhost/databasename#table_prefix');
    $phpXml->addChild('commentWorkaround', 'This is weird and mostly meaningless but needs to exist and be writable, but confusingly does NOT correspond to where output gets sent.');
    $ini = $phpXml->addChild('env');
    $ini->addAttribute('name', 'BROWSERTEST_OUTPUT_DIRECTORY');
    $ini->addAttribute('value', '/path/to/something');
    $phpXml->addChild('commentWorkaround', 'To have browsertest output use an alternative base URL. For example if SIMPLETEST_BASE_URL is an internal DDEV URL, you can set this to the external DDev URL so you can follow the links directly.');
    $ini = $phpXml->addChild('env');
    $ini->addAttribute('name', 'BROWSERTEST_OUTPUT_BASE_URL');
    $ini->addAttribute('value', '');
    $phpXml->addChild('commentWorkaround', 'Example for changing the driver class for mink tests MINK_DRIVER_CLASS value: Drupal\FunctionalJavascriptTests\DrupalSelenium2Driver');
    $ini = $phpXml->addChild('env');
    $ini->addAttribute('name', 'MINK_DRIVER_CLASS');
    $ini->addAttribute('value', '');
    $phpXml->addChild('commentWorkaround', 'Example for changing the driver args to mink tests MINK_DRIVER_ARGS value: ["http://127.0.0.1:8510"]');
    $ini = $phpXml->addChild('env');
    $ini->addAttribute('name', 'MINK_DRIVER_ARGS');
    $ini->addAttribute('value', '');
    $phpXml->addChild('commentWorkaround', 'Example for changing the driver args to webdriver tests MINK_DRIVER_ARGS_WEBDRIVER value: ["chrome", { "chromeOptions": { "w3c": false } }, "http://localhost:4444/wd/hub"]. For using the Firefox browser, replace "chrome" with "firefox".');
    $ini = $phpXml->addChild('env');
    $ini->addAttribute('name', 'MINK_DRIVER_ARGS_WEBDRIVER');
    $ini->addAttribute('value', '');

    $listenerXml = $this->get()->addChild('listeners')->addChild('listener');
    $listenerXml->addAttribute('class', '\Drupal\Tests\Listeners\DrupalListener');
  }

  /**
   * @param string $name
   * @param array $dirs
   */
  public function addTestSuite($name, $dirs) {
    $testsuites = $this->get()->addChild('testsuites'); // FIXME: find/load
    $testsuite = $testsuites->addChild('testsuite');
    $testsuite->addAttribute('name', $name);
    foreach ($dirs as $dir) {
      $testsuite->addChild('directory', $dir);
    }
  }

}
