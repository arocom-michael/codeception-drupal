<?php
declare(strict_types=1);

namespace Codeception\Module\Context;

use Behat\Gherkin\Node\TableNode;
use Codeception\Exception\ParseException;
use Codeception\Util\HttpCode;
use Codeception\Util\Locator;
use PHPUnit\Framework\AssertionFailedError;
use Symfony\Component\Process\InputStream;
use Symfony\Component\Process\Process;

/**
 * Step definitions developed for Drupal ^8.0.
 *
 * @author Michael A. Johnson Lucas <johnson@arocom.de>
 */
// Work based on this composer package: drupal/drupal-extension
// @author Jonathan Hedstrom <jhedstrom@gmail.com>
// @author Melissa Anderson https://github.com/eliza411
// @author Pieter Frenssen https://github.com/pfrenssen
class DrupalContext extends BaseContext
{

    /**
     * Keep track of drush output.
     *
     * @var string
     */
    protected $processOutput;

    /**
     * Keep track of drush error output.
     *
     * @var string
     */
    protected $processErrorOutput;

    /** @var \Codeception\Module\DrupalDrush */
    protected $drupalDrush;

    /** @var string */
    protected $drushAlias;

    const DRUSH = 'drush';

    /**
     * The constructor should not be overwritten,
     * to be able to load more dependencies.
     */
    public function _initialize()
    {
        $this->drupalDrush = $this->loadDependency('Drush');
        $this->drushAlias = $this->_getConfig('DrushAlias') ?: self::DRUSH;
        $this->processOutput = '';
        $this->processErrorOutput = '';
    }

    /**
     * Return the most recent drush command output.
     *
     * @return string
     *
     * @throws \RuntimeException
     */
    public function _readDrushOutput(): string
    {
        if ($this->processOutput === null) {
            throw new \RuntimeException('No drush output was found.');
        }

        return $this->processOutput;
    }

    /**
     * @Given I am an anonymous user
     * @Given I am not logged in
     *
     * @return void
     */
    public function assertAnonymousUser()
    {
        // Verify the user is logged out.
        if ($this->loggedIn()) {
            $this->logout();
        }
    }

    /**
     * Creates and authenticates a user with the given role(s).
     *
     * @example Given I am logged in as a user with the "authenticated user" role
     * @example Given I am logged in as a user with the "administrator" role
     *
     * @Given I am logged in as a user with the :role role
     * @Given I am logged in as a user with the :role roles
     * @Given I am logged in as a :role
     * @Given I am logged in as an :role
     *
     * @param string $role
     *
     * @return void
     *
     * @throws \Exception
     */
    public function assertAuthenticatedByRole(string $role)
    {
        // Check if a user with this role is already logged in.
        if (!$this->loggedIn()) {
            return;
        }
        $password = $this->generatePassword();
        $user = 'test-user';
        $this->drushAddUser($user, "{$user}@example.com", $password);
        $this->drushAddRole($role, $user);
        // Login.
        $this->drushLoginLink($user, 'de-de');
    }

    /**
     * Creates and authenticates a user with the given role(s) and given fields.
     * | name     | John             |
     * | email    | john@example.com |
     * | password | 1234567890ABCDEF |
     *
     * @Given I am logged in as a user with the :role role and I have the following fields
     *
     * @param string $role
     * @param \Behat\Gherkin\Node\TableNode $fields
     *
     * @return void
     *
     * @throws \Exception
     */
    public function assertAuthenticatedByRoleWithGivenFields(
      string $role,
      TableNode $fields
    ) {
        // Check if a user with this role is already logged in.
        if ($this->loggedIn()) {
            return;
        }
        list($user, $email, $password) = [
          'test-user',
          'test-user@example.com',
          $this->generatePassword(),
        ];
        $allowedFields = ['name', 'email', 'password'];
        // Assign fields to user before creation.
        foreach ($fields->getRowsHash() as $field => $value) {
            if (!\in_array($field, $allowedFields, true)) {
                continue;
            }
            switch ($field) {
                case 'name':
                    $user = $value;
                    break;
                case 'email':
                    $email = $value;
                    break;
                case 'password':
                    $password = $value;
                    break;
                default:
                    break;
            }
        }
        $this->drushAddUser($user, $email, $password);
        $this->drushAddRole($role, $user);
        // Login.
        $this->drushLoginLink($user, 'de-de');
    }


    /**
     * @Given I am logged in as :name
     *
     * @param string $name
     *
     * @return void
     */
    public function assertLoggedInByName(string $name)
    {
        $this->webDriver->see($name, '#toolbar-item-user');
    }

    /**
     * @Given I am logged in as a user with the :permissions permission
     * @Given I am logged in as a user with the :permissions permissions
     *
     * @param string $permissions
     *
     * @return void
     *
     * @throws \Exception
     */
    public function assertLoggedInWithPermissions(string $permissions)
    {
        // Check if a user with this role is already logged in.
        if ($this->loggedIn()) {
            return;
        }
        $role = 'test';
        // Create a new role.
        $drushArguments = [$role, \ucfirst($role)];
        $process = $this->drupalDrush->getDrush('role-create', $drushArguments, [], $this->drushAlias);
        $this->webDriver->debug($this->getRunProcessOutput($process));
        // Parse permissions.
        $drushArguments = $this->getArguments($permissions);
        $drushArguments = [implode($drushArguments, ', ')];
        // Grant specified permission(s) to the test role.
        $process = $this->drupalDrush->getDrush('role-add-perm',
          $drushArguments, [], $this->drushAlias);
        $this->webDriver->debug($this->getRunProcessOutput($process));
        // Create user.
        list($user, $email, $password) = [
          'test-user',
          'test-user@example.com',
          $this->generatePassword(),
        ];
        $this->drushAddUser($user, $email, $password);
        $this->drushAddRole($role, $user);
        // Login.
        $this->drushLoginLink($user, 'de-de');
    }

    /**
     * Find text in a table row containing given text.
     *
     * @example Then I should see the text "administrator" in the "Joe User" row
     *
     * @Then I should see :text in the :rowText row
     * @Then I should see the text :text in the :rowText row
     *
     * @param string $text
     * @param string $rowText
     *
     * @return void
     *
     * @throws \Exception
     */
    public function assertTextInTableRow(string $text, string $rowText)
    {
        // table > tr
        $tableRow = Locator::contains('tr', $rowText);
        $this->webDriver->debug($tableRow);
        $this->webDriver->see($rowText, $tableRow);
        // table > tr > td
        $tableDataCell = Locator::contains($tableRow, $text);
        $this->webDriver->debug($tableDataCell);
        $this->webDriver->see($text, $tableDataCell);
    }

    /**
     * Asset text not in a table row containing given text.
     *
     * @example And  I should not see the text "administrator" in the "Jane User" row
     *
     * @Then I should not see :text in the :rowText row
     * @Then I should not see the text :text in the :rowText row
     *
     * @param string $text
     * @param string $rowText
     *
     * @return void
     *
     * @throws \Exception
     */
    public function assertTextNotInTableRow(string $text, string $rowText)
    {
        // table > tr
        $tableRow = Locator::contains('tr', $rowText);
        $this->webDriver->debug($tableRow);
        $this->webDriver->see($rowText, $tableRow);
        // table > tr > td
        $tableDataCell = Locator::contains($tableRow, $text);
        $this->webDriver->debug($tableDataCell);
        $this->webDriver->dontSee($text, $tableDataCell);
    }

    /**
     * Attempts to find a link in a table row containing giving text. This is
     * for administrative pages such as the administer content types screen
     * found at
     * `admin/structure/types`.
     *
     * @Given I click :link in the :rowText row
     * @Then I see the :link in the :rowText row
     * @Then I should see the :link in the :rowText row
     *
     * @param string $link
     * @param string $rowText
     *
     * @return void
     *
     * @throws \Exception
     */
    public function assertClickInTableRow(string $link, string $rowText)
    {
        // table > tr
        $tableRow = Locator::contains('tr', $rowText);
        $this->webDriver->debug($tableRow);
        $this->webDriver->see($rowText, $tableRow);
        // table > tr > td
        $tableDataCell = Locator::contains($tableRow, $link);
        $this->webDriver->debug($tableDataCell);
        // Link
        $this->webDriver->click($tableDataCell);
    }

    /**
     * @Given the cache has been cleared
     *
     * @return void
     */
    public function assertCacheClear()
    {
        $this->drupalDrush->getDrush('cache-clear', [], [], $this->drushAlias);
    }

    /**
     * @Given I run cron
     *
     * @return void
     */
    public function assertCron()
    {
        $this->drupalDrush->getDrush('core-cron', [], [], $this->drushAlias);
    }


    /**
     * Asserts that a given content type is editable.
     * drush ev 'foreach(array_keys(node_type_get_types()) as $type) { echo
     * $type.PHP_EOL; }'
     *
     * @example Then I should be able to edit an "article"
     *
     * @Then I should be able to edit a :type
     * @Then I should be able to edit an :type
     *
     * @param string $type
     *
     * @return void
     *
     * @throws \Symfony\Component\Process\Exception\RuntimeException
     * @throws \Symfony\Component\Process\Exception\LogicException
     * @throws \Codeception\Exception\ModuleConfigException
     * @throws \Codeception\Exception\ParseException
     */
    public function assertEditNodeOfType(string $type)
    {
        $types = $this->drushGetTypes();
        if (!\in_array($type, $types, true)) {
            throw new ParseException('Invalid type');
        }

        $argument = '$nids = \Drupal::entityQuery("node")->condition("type", "' . $type . '")->execute();';
        $argument .= ' echo json_encode(array_values($nids)).PHP_EOL;';
        $drushArguments = [$argument];
        $process = $this->drupalDrush->getDrush('ev', $drushArguments, [], $this->drushAlias);
        $this->webDriver->debug($this->getRunProcessOutput($process));

        /** @var iterable $json */
        $json = json_decode($this->processOutput);
        $nids = [];
        foreach ($json as $nid) {
            $nids[] = $nid;
        }

        // PHP 7.1.0 uses Mersenne Twister instead of the libc rand function.
        $randomNid = array_rand($nids);

        // Set internal browser on the node edit page.
        $page = "/node/{$randomNid}/edit";
        $url = $this->webDriver->_getUrl() . $page;
        $actualStatusCode = $this->getStatusCode($url);
        $this->assertEquals(HttpCode::OK, $actualStatusCode, 'Page not found');
        // Visit the content page.
        $this->webDriver->amOnPage($page);
    }

    /**
     * Creates multiple users.
     *
     * Provide user data in the following format:
     *
     * | name     | mail         | roles        |
     * | user foo | foo@bar.com  | role1, role2 |
     *
     * @Given users
     *
     * @param \Behat\Gherkin\Node\TableNode $usersTable
     *
     * @return void
     *
     * @throws \Exception
     */
    public function createUsers(TableNode $usersTable)
    {
        $allowedRolls = $this->drushGetRoles();

        foreach ($usersTable->getRows() as $userHash) {
            // Split out roles to process after user is created.
            $roles = [];
            if (isset($userHash['roles'])) {
                $roles = explode(',', $userHash['roles']);
                $roles = array_filter(array_map('trim', $roles));
                unset($userHash['roles']);
            }

            $password = $this->generatePassword();
            $this->drushAddUser($userHash['name'], $userHash['mail'], $password);

            // Assign roles.
            foreach ($roles as $role) {
                if (!\in_array($role, $allowedRolls, true)) {
                    continue;
                }
                $this->drushAddRole($role, $userHash['name']);
            }
        }
    }

    /**
     * Logs the current user out.
     *
     * @return void
     *
     * @TODO Check if the step is necessary
     */
    private function logout()
    {
        $this->webDriver->amOnPage('/user/logout');
    }

    /**
     * Determine if the a user is already logged in.
     *
     * @return boolean
     *   Returns TRUE if a user is logged in for this session.
     *
     * @TODO Check if the step is necessary
     */
    private function loggedIn(): bool
    {
        try {
            $this->webDriver->seeElement(DrupalExtension::SELECTORS_LOGGED_IN_SELECTOR);

            return true;
        } catch (AssertionFailedError $exception) {
            // This test may fail if the driver did not load any site yet.
        }

        $this->webDriver->amOnPage('/user/login');
        try {
            $this->webDriver->dontSeeElement(DrupalExtension::SELECTORS_LOGIN_FORM_SELECTOR);

            return true;
        } catch (AssertionFailedError $exception) {
            // This test may fail if the driver did not load any site yet.
        }

        $this->webDriver->amOnPage('/');
        try {
            $this->webDriver->seeLink(DrupalExtension::TEXT_LOGO_OUT);

            return true;
        } catch (AssertionFailedError $exception) {
            // This test may fail if the driver did not load any site yet.
        }

        return false;
    }

    /**
     * @param string $user
     * @param string $email
     * @param string $password
     *
     * @Given I create :user user with :email mail and :password password
     * @Given I create :user user with :email email and :password password
     *
     * @example drush user-create newuser --mail="person@example.com" --password="letmein"
     * @throws \Symfony\Component\Process\Exception\RuntimeException
     * @throws \Symfony\Component\Process\Exception\LogicException
     */
    public function drushAddUser(string $user, string $email, string $password)
    {
        list($command, $arguments, $options) = [
          'user-create',
          $user,
          "--mail={$email}|--password={$password}",
        ];
        $drushArguments = $this->getArguments($arguments);
        $drushOptions = $this->getArguments($options);
        $process = $this->drupalDrush->getDrush($command, $drushArguments,
          $drushOptions, $this->drushAlias);
        $this->webDriver->debug($this->getRunProcessOutput($process));
    }

    /**
     * @param string $user
     *
     * @Given I delete :user user
     *
     * @example drush user-cancel username
     *
     * @return void
     *
     * @throws \Symfony\Component\Process\Exception\RuntimeException
     * @throws \Symfony\Component\Process\Exception\LogicException
     * @throws \Codeception\Exception\ParseException
     */
    public function drushDeleteUser(string $user)
    {
        list($command, $arguments, $options) = ['user-cancel', $user, ''];
        $drushArguments = $this->getArguments($arguments);
        $drushOptions = $this->getArguments($options);
        $process = $this->drupalDrush->getDrush($command, $drushArguments, $drushOptions, $this->drushAlias);
        $output = $this->getRunProcessOutput($process);
        $this->webDriver->debug($output);

        $messages = [
          'message' => 'Cancel user account?:  (y/n):',
          'confirmation' => "Yes, I want to cancel {$user} user",
          'negation' => "No, I don't want to cancel {$user} user",
        ];
        $this->interactWithCommandLine($process, $output, $messages);
    }

    /**
     * @param string $user
     *
     * @Given I get a login link for :user user with :locale locale
     *
     * @exampledrush user-login ryan
     *
     * @return void
     *
     * @throws \Symfony\Component\Process\Exception\RuntimeException
     * @throws \Symfony\Component\Process\Exception\LogicException
     */
    public function drushLoginLink(string $user, string $locale)
    {
        list($command, $arguments, $options) = ['user-login', $user, ''];
        $drushArguments = $this->getArguments($arguments);
        $drushOptions = $this->getArguments($options);
        $process = $this->drupalDrush->getDrush($command, $drushArguments, $drushOptions, $this->drushAlias);
        $this->webDriver->debug($this->getRunProcessOutput($process));
        $url = trim($this->processOutput);
        $path = parse_url($url, PHP_URL_PATH);
        $path = str_replace('/en/user/', "/{$locale}/user/", $path);
        $this->webDriver->amOnPage($path);
    }

    /**
     * @param string $role
     * @param string $user
     *
     * @Given I remove :role role from :user user
     *
     * @example drush user-remove-role administrator --name=john
     *
     * @return void
     *
     * @throws \Symfony\Component\Process\Exception\RuntimeException
     * @throws \Symfony\Component\Process\Exception\LogicException
     */
    public function drushRemoveRole(string $role, string $user)
    {
        list($command, $arguments, $options) = [
          'user-remove-role',
          $role,
          "--name={$user}",
        ];
        $drushArguments = $this->getArguments($arguments);
        $drushOptions = $this->getArguments($options);
        $process = $this->drupalDrush->getDrush($command, $drushArguments, $drushOptions, $this->drushAlias);
        $this->webDriver->debug($this->getRunProcessOutput($process));
    }

    /**
     * The anonymous and authenticated roles cannot be assigned manually.
     *
     * @param string $role
     * @param string $user
     *
     * @Given I add :role role for :user user
     *
     * @example drush user-add-role administrator --name=john
     *
     * @return void
     *
     * @throws \Symfony\Component\Process\Exception\RuntimeException
     * @throws \Symfony\Component\Process\Exception\LogicException
     */
    public function drushAddRole(string $role, string $user)
    {
        list($command, $arguments, $options) = [
          'user-add-role',
          $role,
          "--name={$user}",
        ];
        $drushArguments = $this->getArguments($arguments);
        $drushOptions = $this->getArguments($options);
        $process = $this->drupalDrush->getDrush($command, $drushArguments, $drushOptions, $this->drushAlias);
        $this->webDriver->debug($this->getRunProcessOutput($process));
    }

    /**
     * @Given I get types
     *
     * @example drush ev "echo json_encode(array_keys(node_type_get_types())).PHP_EOL;"
     * ["article","page","sidebar"]
     *
     * @return array<int, string>
     *
     * @throws \Symfony\Component\Process\Exception\RuntimeException
     * @throws \Symfony\Component\Process\Exception\LogicException
     */
    public function drushGetTypes(): array
    {
        $drush = [
          'ev',
          'echo json_encode(array_keys(node_type_get_types())).PHP_EOL;',
          '',
        ];
        list($command, $arguments, $options) = $drush;
        $drushArguments = $this->getArguments($arguments);
        $drushOptions = $this->getArguments($options);
        $process = $this->drupalDrush->getDrush($command, $drushArguments, $drushOptions, $this->drushAlias);
        $this->webDriver->debug($this->getRunProcessOutput($process));

        /** @var iterable $json */
        $json = json_decode($this->processOutput);
        $types = [];
        foreach ($json as $type) {
            $types[] = $type;
        }

        return $types;
    }

    /**
     * @Given I get roles
     *
     * @example drush role-list --format=json
     * ["anonymous", "authenticated", "administrator"]
     *
     * @return array<int, string>
     *
     * @throws \Symfony\Component\Process\Exception\RuntimeException
     * @throws \Symfony\Component\Process\Exception\LogicException
     */
    public function drushGetRoles(): array
    {
        list($command, $arguments, $options) = [
          'role-list',
          '',
          '--format=json',
        ];
        $drushArguments = $this->getArguments($arguments);
        $drushOptions = $this->getArguments($options);
        $process = $this->drupalDrush->getDrush($command, $drushArguments, $drushOptions, $this->drushAlias);
        $this->webDriver->debug($this->getRunProcessOutput($process));

        /** @var iterable $json */
        $json = json_decode($this->processOutput);
        $roles = [];
        foreach ($json as $role) {
            $roles[] = $role->rid;
        }

        return $roles;
    }

    /**
     * @param \Symfony\Component\Process\Process $process
     *
     * @return string
     *
     * @throws \Symfony\Component\Process\Exception\RuntimeException
     * @throws \Symfony\Component\Process\Exception\LogicException
     */
    private function getRunProcessOutput(Process $process): string
    {
        $process->start();
        $output = '';
        $errorOutput = '';
        foreach ($process as $type => $data) {
            if ($process::OUT === $type) {
                $output .= $data . PHP_EOL;
            } else {
                $errorOutput .= $data . PHP_EOL;
            }
        }
        $process->stop();

        $this->processOutput = $output;
        $this->processErrorOutput = $errorOutput;

        return "{$output}[Drush]{$errorOutput}";
    }

    /**
     * If the drush config-set commands needs a confirmation like:
     *   Do you want to update {$key} key in {$configName} config? (y/n):
     *
     * @param \Symfony\Component\Process\Process $process
     * @param string $output Console output
     * @param array<string, string> $messages Messages within an associative
     *     array
     *   ['message' => '...', 'confirmation' => '...', 'negation' => '...']
     *
     * @return void
     *
     * @throws \Symfony\Component\Process\Exception\RuntimeException
     * @throws \Symfony\Component\Process\Exception\LogicException
     * @throws \Codeception\Exception\ParseException
     */
    private function interactWithCommandLine(
      Process $process,
      string $output,
      array $messages
    ) {
        $allowedMessages = ['message', 'confirmation', 'negation'];
        $actualMessages = array_keys($messages);
        $format = 'The messages parameter should be like ';
        $format .= '["%s" => "...", "%s" => "...", "%s" => "..."]';
        $errorMessage = vsprintf($format, $allowedMessages);

        // Compare the two arrays, to check if they contain the same keys.
        if (array_diff($allowedMessages,
            $actualMessages) !== array_diff($actualMessages,
            $allowedMessages)) {
            throw new ParseException($errorMessage);
        }

        // If matches then confirm.
        if (mb_strpos($output, $messages['message']) === 0) {
            $this->webDriver->debug($messages['confirmation']);
            $input = new InputStream();
            $process->setInput($input);
            // Send confirmation.
            $input->write('y');
            $process->start();
            sleep(1);
            $input->close();
            $process->stop();
        } else {
            $this->webDriver->debug($messages['negation']);
        }
    }

    /**55
     * Generate a 16 character long hexadecimal password
     *
     * @return string
     */
    private function generatePassword(): string
    {
        try {
            return bin2hex(random_bytes(8));
        } catch (\Exception $e) {
            return '';
        }
    }
}
