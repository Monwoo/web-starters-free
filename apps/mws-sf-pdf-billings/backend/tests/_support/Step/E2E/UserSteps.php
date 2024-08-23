<?php

/**
 * 🌖🌖 Copyright Monwoo 2024 🌖🌖, build by Miguel Monwoo
 * service@monwoo.com
 */

declare(strict_types=1);

namespace App\Tests\Step\E2E;

use Codeception\Util\Locator;
use MWS\MoonManagerBundle\Entity\MwsUser;

class UserSteps extends \App\Tests\AcceptanceTester
{
  public static $userMenuSelector = '#dropdownNavbarLink';
  public static $userLogoutSubMenuSelector = '#dropdownNavbarLink//TODO';
  public static $loggedOutIndicatorSelector = '#dropdownNavbarLink//TODO';
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
      "userpass" => 'plaintextPassword',
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
      // $navHeight = $I->executeJS("return parseInt($('#mainNavbar').outerHeight()) || 0;");
      $userMenuSelector = Locator::contains(
        self::$userMenuSelector,
        $username
      );
      if (!$I->testIfPresent($userMenuSelector)) {
        $listUserSubmenuSelector = $config['listUserSubmenuSelector'] ?? null;

        // $I->scrollTo(HomePage::$navBarUsersMenu, 0, -$navHeight);
        $I->click($listUserSubmenuSelector);
        $I->waitHumanDelay();
      } else {
        // ensure logout
        // Force login
        // $I->click($navBarUsersSubMenu);
        // $I->waitHumanDelay(); // TODO : add interactionDelay ? only need to wait for js to scroll ...
      }
    } else {
      $I->comment("🇫🇷🇫🇷 Ne pas être connecté");
    }
  }
  // 🇫🇷🇫🇷 Se connecter
  public function connectTestUser(
    $config
  ) {
    // $I = $this->I;
    // $I->comment("⟳ 🔐 Will try to connect test user : $userEmail");
    // $I->dump("will connect user", $userEmail, $userPassword);

    // // TIPS : étude d'icones : ⟳✅❌🚪🔐🔓🔔🔕
    // $I->amOnPage(self::$URL);
    // $I->fillField(self::$emailField, $userEmail);
    // $I->fillField(self::$passwordField, $userPassword);
    // $I->scrollTo(self::$formSubmitButton);
    // $I->waitHumanDelay();
    // $I->click(self::$formSubmitButton);
    // $I->waitHumanDelay();

    // // $email = $I->grabValueFrom(self::$emailField);
    // // $nav = $I->grabTextFrom(self::$navBar);
    // $haveNavigation = $I->testIfPresent(self::$navBar);

    // if ($haveNavigation) {
    //   // $I->seeResponseCodeIs(HttpCode::OK);
    //   $I->comment("✅ 🔓 Did connect test user : $userEmail");
    //   // Juste après la connexion, nous voulons optimiser le temps de participants,
    //   // orientation direct vers le plannig :

    //   $urlGenerator = $I->grabService('router.default');
    //   $loginRedirectUrl = $urlGenerator->generate('app_login');
    //   // https://codeception.com/docs/03-AcceptanceTests
    //   // Each failed assertion will be shown in the test results, but it won’t stop the test.
    //   // $I->canSeeInCurrentUrl($planningUrl);
    //   $I->seeInCurrentUrl($loginRedirectUrl);
    // } else {
    //   $I->comment("❌ 🔐 Fail to connect test user : $userEmail");
    //   $I->comment("⟳ 🔐 Will try to create and connect test user : $userEmail");

    //   // if (isset($this->kernel)) {
    //   //     return $this->kernel->getContainer()->get('doctrine');
    //   //     $conn = $this->getEntityManager()
    //   //     ->getConnection();
    //   // } // or use : https://codeception.com/docs/modules/Doctrine2

    //   /** @var \Doctrine\Bundle\DoctrineBundle\Registry */
    //   $registry = $I->grabService('doctrine'); // Get EntityManager
    //   $em = $registry->getManager();
    //   $connection = $registry->getConnection();

    //   // $userInits["email"] = $userInits["userId"]; // TODO : should be able to have other custom id than emal...
    //   $userInits = array_filter($userInits, function ($key) {
    //     return false !== array_search($key, [
    //       'firstname',
    //       'lastname',
    //       'phone',
    //       'roles' // TODO : more property to keey for raw account creation ?
    //     ]);
    //   }, ARRAY_FILTER_USE_KEY);
    //   $I->debug($userInits);

    //   // $passwordHasher = $I->grabService('container')->passwordHasher(); // Private service for our app...
    //   // $passwordHasher = $I->grabService('security')->passwordHasher(); // Private service for our app...
    //   $passwordHasher = $I->grabService('security.password_hasher'); // Get EntityManager
    //   $hashedPassword = $passwordHasher->hashPassword(
    //     new \App\Entity\User(), // null, // 'App\Entity\User', // $user,
    //     $userPassword
    //   );

    //   // Create our default test user in database if it don't exist :
    //   // $sql = "
    //   //     INSERT INTO user (email, roles, firstname, lastname, phone, is_active, created_at, password)
    //   //     VALUES ('$userEmail', '[]', 'E2eDefaultPrenom', 'TestLocalhost', '0600000000', 1, date('now'),
    //   //     '$hashedPassword')
    //   // ";
    //   // https://www.doctrine-project.org/projects/doctrine-dbal/en/latest/reference/data-retrieval-and-manipulation.html#insert
    //   // $stmt = $connection->prepare($sql);
    //   // $status = $stmt->execute();
    //   // $I->debug("New test user SQL query status : ", !!$status);
    //   $userData = array_merge([
    //     "email" => $userEmail,
    //     "created_at" => date("Y-m-d H:i:s"),
    //     "password" => $hashedPassword,
    //     "is_active" => true,
    //   ], $userInits, [
    //     "roles" => json_encode($userInits["roles"] ?? []),
    //   ]);

    //   $resp = $connection->insert('user', $userData);
    //   $connection->prepare('COMMIT;')->execute();
    //   // https://stackoverflow.com/questions/8707486/doctrine2-how-to-improve-flush-efficiency
    //   $em->flush();
    //   // $em->clear();
    //   $I->debug("[" . get_class($connection) . "] New user inserted : ", $resp, $userData);
    //   // $I->debug("New test user SQL query response : ", $stmt->fetchAll());

    //   // Now, login should work :
    //   $I->fillField(self::$emailField, $userEmail);
    //   $I->fillField(self::$passwordField, $userPassword);
    //   $I->click(self::$formSubmitButton);
    //   // $I->waitHumanDelay(3);
    //   $I->seeElement(self::$navBar);
    // }
  }

  // 🇫🇷🇫🇷 Se dé-connecter
  public function disconnectTestUser(
    $username = "e2e-default@test.localhost"
  )
  {
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
