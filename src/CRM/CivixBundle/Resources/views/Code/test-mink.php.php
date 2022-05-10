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

/**
 * @group mink
 */
class <?php echo $testClass ?> extends <?php echo $baseNamespace ?>Base {

  /**
   * @var array
   *   We always create one contact to start with.
   */
  protected $contact;

  public function setUp(): void {
    parent::setUp();
    $this->createUserAndLogIn();
    // Convenience function similar to civi's individualCreate() - while we
    // have access to api functions, we're in a different environment and don't
    // have the right setup for using civi's unit testing helpers.
    $this->contact = $this->createContact();
  }

  /**
   * Create a contribution and view it.
   */
  public function testContribution() {
    // Create a contribution.
    $contribution = civicrm_api3('Contribution', 'create', [
      'contact_id' => $this->contact['id'],
      'financial_type_id' => 'Donation',
      'total_amount' => '10',
    ]);

    // View the contribution.
    $this->drupalGet(\CRM_Utils_System::url("civicrm/contact/view/contribution", "reset=1&context=dashboard&selectedChild=contribute&id={$contribution['id']}&cid={$this->contact['id']}&action=view", TRUE, NULL, FALSE));

    // Convenience function to check for error messages or error popups.
    $this->assertPageHasNoErrorMessages();

    // Check that the page contains some text we expect.
    $this->assertSession()->pageTextContains('Donation');
    $this->assertSession()->pageTextContains('10.00');

    // Click the Done button.
    // Note: Each navigation will automatically take a screenshot.
    $this->getSession()->getPage()->pressButton('Done');
    // Check the page it navigated to has some text we expect.
    $this->assertSession()->pageTextContains('Recent Contributions');

    // Navigate to the contact view page.
    $this->drupalGet(\CRM_Utils_System::url("civicrm/contact/view", "reset=1&cid={$this->contact['id']}", TRUE, NULL, FALSE));

    // Manually take a screenshot since the last page doesn't automatically
    // take a screenshot.
    $this->htmlOutput();
  }

}
