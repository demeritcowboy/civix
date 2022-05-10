<?php
namespace CRM\CivixBundle\Command;

use Civix;
use CRM\CivixBundle\Utils\Files;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use CRM\CivixBundle\Builder\Dirs;
use CRM\CivixBundle\Builder\Info;
use CRM\CivixBundle\Builder\PHPUnitGenerateInitFiles;
use CRM\CivixBundle\Utils\Path;
use Exception;

class AddTestCommand extends AbstractCommand {

  protected function configure() {
    parent::configure();
    $this
      ->setName('generate:test')
      ->setDescription('Add a new PHPUnit test to a CiviCRM Module-Extension')
      ->setHelp('
Add a new PHPUnit test to a CiviCRM Module-Extension

In creating a test, you may specify a template:
  headless: A headless test boots CiviCRM once with a headless database, and
            all work can be executed in-process. These are faster and support
            automatic cleanup, but they provide a less thorough simulation
            of real-world systems.
  e2e:      An end-to-end test boots the live installation of CiviCRM and
            the real CMS This provides a more thorough simulation, and you
            may spawn  requests to Civi using HTTP or cv(). However, spawning
            separate requests will be slower, and data-cleanup may take more
            effort.
  mink:     A front-end test based on the Mink framework adapted for CiviCRM.
            They are slow but allow simulating a full system by controlling a
            browser to navigate through pages, while also being able to call
            backend php functions in between clicks.
  legacy:   A variation of `headless` based on CiviUnitTestCase.
            It is provided primarily for testing purposes.
  phpunit:  A test suite based on the PHPUnit_Framework_TestCase. Provides an 
            interface for you extension to implement unittest for your 
            classes/functions

To execute tests, call phpunit 4.x directly, e.g.

  phpunit4 tests/phpunit/CRM/Myextension/MyTest.php

Note: The design of headless and E2E tests prevent them from running
concurrently. If you have a mix of tests, you can execute them
as separate groups:

  phpunit4 --group headless
  phpunit4 --group e2e
')
      ->addOption('template', NULL, InputOption::VALUE_REQUIRED, 'The template of test to generate (headless, e2e, mink, legacy)', 'headless')
      ->addArgument('<CRM_Full_ClassName>', InputArgument::REQUIRED, 'The full class name (eg "CRM_Myextension_MyTest" or "Civi\Myextension\MyTest")');
  }

  protected function execute(InputInterface $input, OutputInterface $output) {
    $this->assertCurrentFormat();

    $ctx = [];
    $ctx['type'] = 'module';
    $ctx['basedir'] = \CRM\CivixBundle\Application::findExtDir();
    $basedir = new Path($ctx['basedir']);

    $info = new Info($basedir->string('info.xml'));
    $info->load($ctx);

    $testTemplateInfo = $this->getTestTemplateInfo($input->getOption('template'));
    $phpUnitInitFiles = new PHPUnitGenerateInitFiles();
    $ctx['xmlBuilderClassName'] = $testTemplateInfo['xmlBuilderClassName'] ?? 'CRM\CivixBundle\Builder\PhpUnitXML';
    $phpUnitInitFiles->initPhpunitXml($basedir->string($testTemplateInfo['phpunitxmlFilename']), $ctx, $output);
    $ctx['bootstrapTemplate'] = $testTemplateInfo['bootstrapTemplate'];
    $phpUnitInitFiles->initPhpunitBootstrap($basedir->string('tests', 'phpunit', $testTemplateInfo['bootstrapFilename']), $ctx, $output);
    $ctx['classnameCallback'] = $testTemplateInfo['classnameCallback'] ?? NULL;
    $ctx['filenameCallback'] = $testTemplateInfo['filenameCallback'] ?? NULL;
    foreach ($testTemplateInfo['template'] as $template) {
      $ctx['template'] = $template;
      $this->initTestClass(
        $input->getArgument('<CRM_Full_ClassName>'), $template, $basedir, $ctx, $output);
    }

    return 0;
  }

  protected function getTestTemplateInfo($type) {
    $templates = [
      'e2e' => [
        'template' => ['test-e2e.php.php'],
        'phpunitxmlFilename' => 'phpunit.xml.dist',
        'bootstrapTemplate' => 'phpunit-boot-cv.php.php',
        'bootstrapFilename' => 'bootstrap.php',
      ],
      'headless' => [
        'template' => ['test-headless.php.php'],
        'phpunitxmlFilename' => 'phpunit.xml.dist',
        'bootstrapTemplate' => 'phpunit-boot-cv.php.php',
        'bootstrapFilename' => 'bootstrap.php',
      ],
      'mink' => [
        'template' => ['test-mink.php.php', 'test-mink-base.php.php'],
        'xmlBuilderClassName' => 'CRM\CivixBundle\Builder\PhpUnitMinkXML',
        'phpunitxmlFilename' => 'phpunit.mink.xml.dist',
        'bootstrapTemplate' => 'phpunit-boot-mink.php.php',
        'bootstrapFilename' => 'bootstrap.mink.php',
        'classnameCallback' => function($fullClassName, $ctx) {
          if ($ctx['template'] === 'test-mink-base.php.php') {
            // The idea here is take the last component of the namespace in
            // info.xml and use it as the name of the base class relative to
            // the provided test class, e.g. if info.xml has
            // CRM/Myawesomeextension and the provided test class is
            // Civi\Foo\Tests\BarTest then the base class
            // becomes Civi\Foo\Tests\MyawesomeextensionBase
            $explodedNamespace = explode('/', $ctx['namespace']);
            $baseNamespace = array_pop($explodedNamespace);
            $parts = explode('\\', $fullClassName);
            array_pop($parts);
            return implode('\\', $parts) . "\\{$baseNamespace}Base";
          }
          return $fullClassName;
        },
        'filenameCallback' => function($fullClassName, $ctx) {
          // Do the same as normal just prepend a Mink folder.
          return 'Mink/' . strtr($fullClassName, ['_' => '/', '\\' => '/']) . '.php';
        },
      ],
      'legacy' => [
        'template' => ['test-legacy.php.php'],
        'phpunitxmlFilename' => 'phpunit.xml.dist',
        'bootstrapTemplate' => 'phpunit-boot-cv.php.php',
        'bootstrapFilename' => 'bootstrap.php',
      ],
      'phpunit' => [
        'template' => ['test-phpunit.php.php'],
        'phpunitxmlFilename' => 'phpunit.xml.dist',
        'bootstrapTemplate' => 'phpunit-boot-cv.php.php',
        'bootstrapFilename' => 'bootstrap.php',
      ],
    ];
    if (isset($templates[$type])) {
      return $templates[$type];
    }
    else {
      throw new \Exception("Invalid test template");
    }
  }

  /**
   * @param string $fullClassName
   * @param string $templateName
   * @param \CRM\CivixBundle\Utils\Path $basedir
   * @param array $ctx
   * @param \Symfony\Component\Console\Output\OutputInterface $output
   * @throws \Exception
   */
  protected function initTestClass($fullClassName, $templateName, $basedir, $ctx, OutputInterface $output) {
    $fullClassName = trim($fullClassName, '\\');

    if (!preg_match('/^[A-Za-z0-9_\\\\]+$/', $fullClassName)) {
      throw new Exception("Class name must be alphanumeric (with underscores and backslashes)");
    }
    if (!preg_match('/Test$/', $fullClassName)) {
      throw new Exception("Class name must end with the word \"Test\"");
    }

    $parts = explode('\\', $fullClassName);
    $ctx['testClass'] = array_pop($parts);
    $ctx['testNamespace'] = implode('\\', $parts);
    $fullClassName = empty($ctx['classnameCallback']) ? $fullClassName : call_user_func($ctx['classnameCallback'], $fullClassName, $ctx);
    $testFile = empty($ctx['filenameCallback']) ? (strtr($fullClassName, ['_' => '/', '\\' => '/']) . '.php') : call_user_func($ctx['filenameCallback'], $fullClassName, $ctx);
    $testPath = $basedir->string('tests', 'phpunit', $testFile);

    $dirs = new Dirs([
      dirname($testPath),
    ]);
    $dirs->save($ctx, $output);

    if (!file_exists($testPath)) {
      $output->writeln(sprintf('<info>Write</info> %s', Files::relativize($testPath)));
      file_put_contents($testPath, Civix::templating()
        ->render($templateName, $ctx));
    }
    else {
      $output->writeln(sprintf('<error>Skip %s: file already exists</error>', Files::relativize($testPath)));
    }
  }

}
