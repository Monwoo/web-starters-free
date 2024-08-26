<?php

/**
 * 🌖🌖 Copyright Monwoo 2024 🌖🌖, build by Miguel Monwoo
 * service@monwoo.com
 */

declare(strict_types=1);

namespace App\Tests\Step\E2E;

use Codeception\Util\HttpCode;
use Codeception\Util\Locator;
use MWS\MoonManagerBundle\Entity\MwsUser;

class UserSteps extends \App\Tests\AcceptanceTester
{
  // public static $loginURL = ''; // TODO : use app Router routes ?
  public static $userMenuSelector = '#dropdownNavbarLink';
  public static $userLoginSubMenuSelector = 'a[href="/mws/fr/mws_user/login"]';
  public static $userLogoutSubMenuSelector = '#dropdownNavbarLink//TODO';
  public static $loggedOutIndicatorSelector = '#dropdownNavbarLink//TODO';

  public static $loginUsernameField = '#username';
  public static $loginPasswordField = '#password';
  public static $formSubmitButton = '[type="submit"]';

  public static $userDefaultInit = null;
  public static $defaultInputs = [
    "listUserSubmenuSelector" => 'a[href="/mws/fr/mws_user/list"]',
    "newUserSelector" => 'a[href="/mws/fr/mws_user/add"]',
    'UsernameInput' => '#mws_user_admin_username',
    'PasswordInput' => '#mws_user_admin_newPassword_first',
    'PasswordConfirmInput' => '#mws_user_admin_newPassword_second',
    'EmailInput' => '#mws_user_admin_email',
    'PhoneInput' => '#mws_user_admin_phone',
    'descriptionInput' => '#mws_user_admin_description',
    'RolesInput' => '#mws_user_admin_roles',
    'TeamMembersInput' => '#mws_user_admin_teamMembers',
    'TeamOwnersInput' => '#mws_user_admin_teamOwners',
    // 'ClientEventSelector' => 'mws_user_admin_mwsClientEvents', // TODO + others, or not editable from profil ? only for super admin ?
    'Submit' => '#user_commercial_submit'
  ];
  public static $userAdminInit = null;
  public static $adminInputs = null;

  public static function initVars()
  {
    // mws-nav-bar
    self::$userDefaultInit = [
      ...UserSteps::$defaultInputs,
      'roles' => [MwsUser::ROLE_ADMIN], // needed for RAW user creation
      "username" => 'e2e-user@test.localhost',
      "userpass" => 'password',
      "firstname" => 'UDefault',
      "lastname" => 'E2eUserDefault',
      "phone" => '0600000000',
    ];

    // TODO : work on multi users / multi roles users ?
    self::$adminInputs = self::$defaultInputs;
    self::$userAdminInit = self::$userDefaultInit;
  }


  public function ensureUser($config)
  {
    $I = $this;
    $username = $config['username'] ?? null;

    if ($username ?? false) {
      $I->comment("🇫🇷🇫🇷 Être connecté avec [$username]");
      // $navHeight = $I->executeJS("return parseInt($('.mws-nav-bar').outerHeight()) || 0;");
      // $userMenuLocator = Locator::contains(
      //   self::$userMenuSelector,
      //   $username // not working...
      // );
      $userMenuLocator = self::$userMenuSelector . " .mws-user-connected";
      if (!$I->testIfPresent($userMenuLocator)) {
        $I->connectTestUser($config, $userMenuLocator);
      } else {
        $I->comment("✅ 🔓 User already connected as : $username");
        // ensure logout
        // Force login
        // $I->click($navBarUsersSubMenu);
        // $I->waitHumanDelay(); // TODO : add interactionDelay ? only need to wait for js to scroll ...
      }
    } else {
      $I->comment("🇫🇷🇫🇷 Ne pas être connecté");
      // TODO : assert not connected...
    }
  }
  // 🇫🇷🇫🇷 Se connecter
  public function connectTestUser(
    $config,
    $userMenuLocator
  ) {
    $username = $config['username'] ?? null;
    $userPassword = $config['userpass'] ?? null;

    $I = $this;
    $I->comment("⟳ 🔐 Will try to connect test user : $username");
    // $I->dump("will connect user", $username, $userPassword); // TODO :  need dump for advenced debugs

    // // TIPS : étude d'icones : ⟳✅❌🚪🔐🔓🔔🔕
    $I->amOnPage('/');
    $I->waitHumanDelay();
    $I->click(self::$userMenuSelector);
    $I->waitHumanDelay();
    $I->click(self::$userLoginSubMenuSelector);
    $I->waitHumanDelay();

    $I->fillField(self::$loginUsernameField, $username);
    $I->fillField(self::$loginPasswordField, $userPassword);
    $I->scrollTo(self::$formSubmitButton);
    $I->click(self::$formSubmitButton);
    $I->waitHumanDelay();

    // $urlGenerator = $I->grabService('router.default');
    // $loginRedirectUrl = $urlGenerator->generate('mws_user_login', [
    //   '_locale' => 'fr'
    // ]);
    // $logoutRedirectUrl = $urlGenerator->generate('mws_user_logout', [
    //   '_locale' => 'fr'
    // ]);

    if ($I->testIfPresent($userMenuLocator)) {
      // $I->seeResponseCodeIs(HttpCode::OK);
      // https://codeception.com/docs/03-AcceptanceTests
      // Each failed assertion will be shown in the test results, but it won’t stop the test.
      // $I->canSeeInCurrentUrl($loginRedirectUrl);
      // $I->seeInCurrentUrl($loginRedirectUrl);
      $I->comment("✅ 🔓 Did connect test user : $username");
    } else {
      // Try to force user creation from DB, will work on local tests only 
      // or with db config to target other db from test device
      $I->comment("❌ 🔐 Fail to connect test user : $username");
      $I->comment("Missing : " . json_encode($userMenuLocator));
      $I->comment("⟳ 🔐 Will try to create and connect test user : $username");
      // $I->comment("⟳ 🔐 Will try to create and connect test user : $username / $userPassword");

      // if (isset($this->kernel)) {
      //     return $this->kernel->getContainer()->get('doctrine');
      //     $conn = $this->getEntityManager()
      //     ->getConnection();
      // } // or use : https://codeception.com/docs/modules/Doctrine2

      /** @var \Doctrine\Bundle\DoctrineBundle\Registry */
      $registry = $I->grabService('doctrine'); // Get EntityManager
      $em = $registry->getManager();
      $connection = $registry->getConnection();

      $userInits = array_filter($config, function ($key) {
        return false !== array_search($key, [
          "created_at",
          'username',
          // 'firstname',
          // 'lastname',
          // 'phone', // TODO : more property for minimum raw account creation or allow all ?
          'roles'
        ]);
      }, ARRAY_FILTER_USE_KEY);
      // TODO : test config to update/create user from admin panel instead ?
      // slower but fully usable manually with admin account...
      $I->debug($userInits);

      // $passwordHasher = $I->grabService('container')->passwordHasher(); // Private service for our app...
      // $passwordHasher = $I->grabService('security')->passwordHasher(); // Private service for our app...
      $passwordHasher = $I->grabService('security.password_hasher'); // Get EntityManager
      $hashedPassword = $passwordHasher->hashPassword(
        new MwsUser(), // null, // 'App\Entity\User', // $user,
        $userPassword
      );

      // Create our default test user in database if it don't exist :
      // $sql = "
      //     INSERT INTO user (email, roles, firstname, lastname, phone, is_active, created_at, password)
      //     VALUES ('$userEmail', '[]', 'E2eDefaultPrenom', 'TestLocalhost', '0600000000', 1, date('now'),
      //     '$hashedPassword')
      // ";
      // https://www.doctrine-project.org/projects/doctrine-dbal/en/latest/reference/data-retrieval-and-manipulation.html#insert
      // $stmt = $connection->prepare($sql);
      // $status = $stmt->execute();
      // $I->debug("New test user SQL query status : ", !!$status);
      $userData = array_merge([
        "created_at" => date("Y-m-d H:i:s"),        
        "password" => $hashedPassword,
      ], $userInits, [
        "username" => $username,
        "roles" => json_encode($userInits["roles"] ?? []),
        "updated_at" => date("Y-m-d H:i:s"),
      ]);

      $resp = $connection->insert('mws_user', $userData);
      $connection->prepare('COMMIT;')->execute();
      // https://stackoverflow.com/questions/8707486/doctrine2-how-to-improve-flush-efficiency
      $em->flush();
      // $em->clear();
      $I->debug("[" . get_class($connection) . "] New user inserted : ", $resp, $userData);
      // $I->debug("New test user SQL query response : ", $stmt->fetchAll());

      // Now, login should work :
      $I->fillField(self::$loginUsernameField, $username);
      $I->fillField(self::$loginPasswordField, $userPassword);
      $I->scrollTo(self::$formSubmitButton);
      $I->click(self::$formSubmitButton);
      $I->waitHumanDelay();

      $I->seeElement($userMenuLocator);
      // $I->seeResponseCodeIs(HttpCode::OK);
      // https://codeception.com/docs/03-AcceptanceTests
      // Each failed assertion will be shown in the test results, but it won’t stop the test.
      // $I->canSeeInCurrentUrl($loginRedirectUrl);
      // $I->seeInCurrentUrl($loginRedirectUrl);
      $I->comment("✅ 🔓 Did connect created test user : $username");
    }
  }

  // 🇫🇷🇫🇷 Se dé-connecter
  public function disconnectTestUser(
    $username = "e2e-default@test.localhost"
  ) {
    $I = $this;
    $I->amOnPage('/');
    $canLogout = $I->testIfPresent(self::$userLogoutSubMenuSelector);
    if ($canLogout) {
      $I->click(self::$userMenuSelector);
      $I->waitHumanDelay();
      $canLogout = $I->testIfPresent(self::$userLogoutSubMenuSelector);
      if ($canLogout) {
        $I->click(self::$userLogoutSubMenuSelector);
        // $I->comment("✅ 🔐 Did disconnect test user.");
        $I->comment("✅ 🔐 Did disconnect test user : $username");
      } else {
        $I->comment("❌ 🔓 Wrong private page ? Missing logout link in user menu.");
      }
    } else {
      $I->seeElement(self::$loggedOutIndicatorSelector);
      $I->comment("✅ 🔓 Already logged out. No need to disconnect test user : $username");
    }
    // $I->dump("Did logout user", $userEmail);
    $I->dump("Did logout user");
  }
}

UserSteps::initVars();
