<?php

use PHPUnit\Framework\TestCase;
use PHPUnit\DbUnit\TestCaseTrait;
use Behat\Mink\Element\DocumentElement;
use Behat\Mink\Element\NodeElement;

require_once 'vendor/autoload.php';
require_once 'classes/DB.php';
require_once 'classes/User.php';
/**
 * Class contains all functional tests for this web application.
 *
 */
class FunctionalTests extends TestCase {
  /* Change this to suite your server setup */
  protected $baseUrl = "http://localhost/imt2291/uke4_lab_losningsforslag/index.php";
  protected $session;
  protected $user, $userData;

  /**
   * Initiates the testing session, this is done before each test.
   * Starts a new session.
   */
  protected function setup() {
    $driver = new \Behat\Mink\Driver\GoutteDriver();
    $this->session = new \Behat\Mink\Session($driver);
    $this->session->start();
    $db = DB::getDBConnection();
    $familyName = md5(date('l jS \of F Y h:i:s A'));  // Create random 32 character string
    $givenName = md5(date('l jS h:i:s A \of F Y '));  // Create random 32 character string
    $this->userData['givenName'] = $givenName;
    $this->userData['familyName'] = $familyName;
    $this->userData['uname'] = $givenName.'@'.$familyName.'.test';
    $this->userData['pwd'] = 'MittHemmeligePassord';

    $this->user = new User($db);
  }

  /**
   * Check that we get the right initial page (not logged in)
   */
  public function testInitialPage() {
    $this->session->visit($this->baseUrl);
    $page = $this->session->getPage();

    $this->assertInstanceOf(
                           NodeElement::Class,
                           $page->find('css', 'p'),
                           'Should have a p tag in this page'
                         );
    $this->assertEquals('Ikke logget inn', $page->find('css', 'p')->getText(),
                        'Wrong text in paragraph');
  }

  /**
   * Get default page, submit the login form, check that we get the
   * logged in page, then reload the page and check that we are still
   * logged in.
   */
  public function testCanLogIn() {
    // Create user for test
    $userid = $this->user->addUser($this->userData)['id'];

    // Go to login page
    $this->session->visit($this->baseUrl);
    $page = $this->session->getPage();

    // Logging in
    $form = $page->find('css', '#login');
    $this->assertInstanceOf(NodeElement::Class, $form, 'Unable to locate login form');

    $page->find('css', '#loginUname')->setValue($this->userData['uname']);  // Fill inn username
    $page->find('css', '#loginPwd')->setValue($this->userData['pwd']);      // Fill in password
    $form->submit();

    $page = $this->session->getPage();

    // Check that we are logged in
    $this->assertInstanceOf(
                           NodeElement::Class,
                           $page->find('css', '#logout'),
                           'Unable to locate the logout form, means we are not logged in'
                         );

    // Reload the page
    $this->session->visit($this->baseUrl);
    $page = $this->session->getPage();

    // Check that we are still logged in
    $this->assertInstanceOf(
                           NodeElement::Class,
                           $page->find('css', '#logout'),
                           'Logout form missing, Logged out after reload'
                         );

    // Remove user when test is done
    $this->user->deleteUser($userid);
  }

  /**
   * Test that we can log out, also that we stay logged out when
   * page is reloaded.
   */
  public function testCanLogOut() {
    // Create user for test
    $userid = $this->user->addUser($this->userData)['id'];

    $this->session->visit($this->baseUrl);
    $page = $this->session->getPage();

    // Logg in
    $form = $page->find('css', '#login');
    $this->assertInstanceOf(NodeElement::Class, $form, 'Unable to locate login form');
    $page->find('css', '#loginUname')->setValue($this->userData['uname']);  // Fill inn username
    $page->find('css', '#loginPwd')->setValue($this->userData['pwd']);      // Fill in password
    $form->submit();

    $page = $this->session->getPage();

    // Logg out
    $form = $page->find('css', '#logout');
    $this->assertInstanceOf(NodeElement::Class, $form, 'Unable to locate logout form');
    $form->submit();

    // Check that we are logged out
    $this->assertInstanceOf(
                           NodeElement::Class,
                           $page->find('css', '#login'),
                           'Missing login form, means we are still logged in'
                         );

    // Relaod the page, should still be logged out
    $this->session->visit($this->baseUrl);
    $page = $this->session->getPage();

    $this->assertInstanceOf(
                           NodeElement::Class,
                           $page->find('css', '#login'),
                           'Login form missing after reload, means we are not truly logged out'
                         );

    // Remove user when test is done
    $this->user->deleteUser($userid);
  }
}
