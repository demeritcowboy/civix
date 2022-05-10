<?php
echo "<?php\n";

if ($testNamespace) {
  echo "namespace $testNamespace;\n";
}
$_namespace = preg_replace(':/:', '_', $namespace);
$explodedNamespace = explode('/', $namespace);
$baseNamespace = array_pop($explodedNamespace);
?>

use <?php echo $_namespace ?>_ExtensionUtil as E;
use Drupal\Tests\civicrm\FunctionalJavascript\CiviCrmTestBase;

/**
 * Base class for Mink tests.
 */
class <?php echo $baseNamespace ?>Base extends CiviCrmTestBase {

  use \Drupal\Tests\mink_civicrm_helpers\Traits\Utils;

  /**
   * @var array
   * This is a special variable that the Drupal testing system uses. Several
   * helper functions live in this Drupal module, so if testing locally you
   * would want to `composer require semperit/minkcivicrmhelpers` into your
   * project.
   */
  protected static $modules = [
    'mink_civicrm_helpers',
  ];

  /**
   * @var int
   *   The uf_id of the logged in user.
   */
  protected $_loggedInUser = NULL;

  public function setUp(): void {
    parent::setUp();

    // Install extension and other tasks.
    // Note that unlike regular civi tests, the entire system is wiped
    // between tests.
    $this->setUpExtension('<?php echo $fullName ?>');

    $this->configureMySettings();
  }

  /**
   * Pretty much every test is going to start with this.
   */
  public function createUserAndLogIn(): void {
    $account = $this->createUser([
      'administer CiviCRM',
      'access CiviCRM',
      'administer CiviCRM system',
      'administer CiviCRM data',
      'access all custom data',
      'edit all contacts',
      'delete contacts',
      'access CiviContribute',
      'edit contributions',
      'delete in CiviContribute',
      // Add more permissions as needed
      // 'skip IDS check',
    ]);
    /**
     * Alternatively, you may want to use the built-in superuser with uid=1,
     * in which case comment out the above and uncomment below.
     * Since we don't know the password, we need to reset it, hence the extra
     * password manipulation below.
     */
    /*
    $account = \Drupal\user\Entity\User::load(1);
    $newpass = \Drupal::service('password_generator')->generate();
    $account->setPassword($newpass);
    $account->save();
    $account->passRaw = $newpass;
     */

    $this->drupalLogin($account);
    $this->_loggedInUser = (int) $account->id();
  }

  /**
   * Configure the extension, i.e. fill out the settings page.
   */
  private function configureMySettings(): void {
    \Civi::settings()->add([
      'my_setting' => '1',
    ]);
  }

}
