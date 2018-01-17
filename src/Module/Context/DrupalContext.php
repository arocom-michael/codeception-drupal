<?php
declare(strict_types=1);

namespace Codeception\Module\Context;

use PHPUnit_Framework_AssertionFailedError;

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

    // @TODO

    /**
     * @Given I am an anonymous user
     * @Given I am not logged in
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
     */
    public function assertAuthenticatedByRole(string $role)
    {
        // Check if a user with this role is already logged in.
        if (!$this->loggedInWithRole($role)) {
            // Create user (and project)
            $user = (object)[
              'name' => $this->getRandom()->name(8),
              'pass' => $this->getRandom()->name(16),
              'role' => $role,
            ];
            $user->mail = "{$user->name}@example.com";

            $this->userCreate($user);

            $roles = explode(',', $role);
            $roles = array_map('trim', $roles);
            foreach ($roles as $role) {
                if (!in_array(
                  strtolower($role),
                  ['authenticated', 'authenticated user']
                )) {
                    // Only add roles other than 'authenticated user'.
                    $this->getDriver()->userAddRole($user, $role);
                }
            }

            // Login.
            $this->login($user);
        }
    }

    /**
     * Creates and authenticates a user with the given role(s) and given
     * fields.
     * | field_user_name     | John  |
     * | field_user_surname  | Smith |
     * | ...                 | ...   |
     *
     * @Given I am logged in as a user with the :role role(s) and I have the
     *   following fields:
     */
    public function assertAuthenticatedByRoleWithGivenFields(
      $role,
      TableNode $fields
    ) {
        // Check if a user with this role is already logged in.
        if (!$this->loggedInWithRole($role)) {
            // Create user (and project)
            $user = (object)[
              'name' => $this->getRandom()->name(8),
              'pass' => $this->getRandom()->name(16),
              'role' => $role,
            ];
            $user->mail = "{$user->name}@example.com";

            // Assign fields to user before creation.
            foreach ($fields->getRowsHash() as $field => $value) {
                $user->{$field} = $value;
            }

            $this->userCreate($user);

            $roles = explode(',', $role);
            $roles = array_map('trim', $roles);
            foreach ($roles as $role) {
                if (!in_array(
                  strtolower($role),
                  ['authenticated', 'authenticated user']
                )) {
                    // Only add roles other than 'authenticated user'.
                    $this->getDriver()->userAddRole($user, $role);
                }
            }

            // Login.
            $this->login($user);
        }
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
        $manager = $this->getUserManager();

        // Change internal current user.
        $manager->setCurrentUser($manager->getUser($name));

        // Login.
        $this->login($manager->getUser($name));
    }

    /**
     * @Given I am logged in as a user with the :permissions permission
     * @Given I am logged in as a user with the :permissions permissions
     *
     * @param string $permissions
     *
     * @return void
     */
    public function assertLoggedInWithPermissions(string $permissions)
    {
        // Create a temporary role with given permissions.
        $permissions = array_map('trim', explode(',', $permissions));
        $role = $this->getDriver()->roleCreate($permissions);

        // Create user.
        $user = (object)[
          'name' => $this->getRandom()->name(8),
          'pass' => $this->getRandom()->name(16),
          'role' => $role,
        ];
        $user->mail = "{$user->name}@example.com";
        $this->userCreate($user);

        // Assign the temporary role with given permissions.
        $this->getDriver()->userAddRole($user, $role);
        $this->roles[] = $role;

        // Login.
        $this->login($user);
    }

    /**
     * Retrieve a table row containing specified text from a given element.
     *
     * @param \Behat\Mink\Element\Element
     * @param string
     *   The text to search for in the table row.
     *
     * @return \Behat\Mink\Element\NodeElement
     *
     * @throws \Exception
     */
    public function getTableRow(Element $element, $search)
    {
        $rows = $element->findAll('css', 'tr');
        if (empty($rows)) {
            throw new \Exception(
              sprintf(
                'No rows found on the page %s',
                $this->getSession()->getCurrentUrl()
              )
            );
        }
        foreach ($rows as $row) {
            if (strpos($row->getText(), $search) !== false) {
                return $row;
            }
        }
        throw new \Exception(
          sprintf(
            'Failed to find a row containing "%s" on the page %s',
            $search,
            $this->getSession()->getCurrentUrl()
          )
        );
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
     * @throws \Exception
     */
    public function assertTextInTableRow(string $text, string $rowText)
    {
        $row = $this->getTableRow($this->getSession()->getPage(), $rowText);
        if (strpos($row->getText(), $text) === false) {
            throw new \Exception(
              sprintf(
                'Found a row containing "%s", but it did not contain the text "%s".',
                $rowText,
                $text
              )
            );
        }
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
     * @throws \Exception
     */
    public function assertTextNotInTableRow(string $text, string $rowText)
    {
        $row = $this->getTableRow($this->getSession()->getPage(), $rowText);
        if (strpos($row->getText(), $text) !== false) {
            throw new \Exception(
              sprintf(
                'Found a row containing "%s", but it contained the text "%s".',
                $rowText,
                $text
              )
            );
        }
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
     * @throws \Exception
     */
    public function assertClickInTableRow(string $link, string $rowText)
    {
        $page = $this->getSession()->getPage();
        if ($link_element = $this->getTableRow($page, $rowText)
          ->findLink($link)) {
            // Click the link and return.
            $link_element->click();

            return;
        }
        throw new \Exception(
          sprintf(
            'Found a row containing "%s", but no "%s" link on the page %s',
            $rowText,
            $link,
            $this->getSession()->getCurrentUrl()
          )
        );
    }

    /**
     * @Given the cache has been cleared
     */
    public function assertCacheClear()
    {
        $this->getDriver()->clearCache();
    }

    /**
     * @Given I run cron
     */
    public function assertCron()
    {
        $this->getDriver()->runCron();
    }

    /**
     * Creates content of the given type.
     *
     * @Given I am viewing a :type content with the title :title
     * @Given I am viewing an :type content with the title :title
     * @Given a :type content with the title :title
     * @Given an :type content with the title :title
     */
    public function createNode(string $type, string $title)
    {
        // @todo make this easily extensible.
        $node = (object)[
          'title' => $title,
          'type' => $type,
        ];
        $saved = $this->nodeCreate($node);
        // Set internal page on the new node.
        $this->getSession()->visit($this->locatePath('/node/' . $saved->nid));
    }

    /**
     * Creates content authored by the current user.
     *
     * @Given I am viewing my :type with the title :title
     * @Given I am viewing my :type content with the title :title
     *
     * @param string $type
     * @param string $title
     *
     * @throws \Exception
     */
    public function createMyNode(string $type, string $title)
    {
        if ($this->getUserManager()->currentUserIsAnonymous()) {
            throw new \Exception(
              sprintf(
                'There is no current logged in user to create a node for.'
              )
            );
        }

        $node = (object)[
          'title' => $title,
          'type' => $type,
          'body' => $this->getRandom()->name(255),
          'uid' => $this->getUserManager()->getCurrentUser()->uid,
        ];
        $saved = $this->nodeCreate($node);

        // Set internal page on the new node.
        $this->getSession()->visit($this->locatePath('/node/' . $saved->nid));
    }

    /**
     * Creates content of the given type, provided in the form:
     * | title     | My node        |
     * | Field One | My field value |
     * | author    | Joe Editor     |
     * | status    | 1              |
     * | ...       | ...            |
     *
     * @Given I am viewing a :type content
     * @Given I am viewing an :type content
     */
    public function assertViewingNode(string $type, TableNode $fields)
    {
        $node = (object)[
          'type' => $type,
        ];
        foreach ($fields->getRowsHash() as $field => $value) {
            $node->{$field} = $value;
        }

        $saved = $this->nodeCreate($node);

        // Set internal browser on the node.
        $this->getSession()->visit($this->locatePath('/node/' . $saved->nid));
    }

    /**
     * Asserts that a given content type is editable.
     * drush ev 'foreach(array_keys(node_type_get_types()) as $type) { echo $type.PHP_EOL; }'
     * @example Then I should be able to edit an "article"
     *
     * @Then I should be able to edit a :type
     * @Then I should be able to edit an :type
     */
    public function assertEditNodeOfType(string $type)
    {
        $node = (object)[
          'type' => $type,
          'title' => "Test $type",
        ];
        $saved = $this->nodeCreate($node);

        // Set internal browser on the node edit page.
        $this->getSession()
          ->visit($this->locatePath('/node/' . $saved->nid . '/edit'));

        // Test status.
        $this->assertSession()->statusCodeEquals('200');
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
     */
    public function createUsers(TableNode $usersTable)
    {
        foreach ($usersTable->getHash() as $userHash) {

            // Split out roles to process after user is created.
            $roles = [];
            if (isset($userHash['roles'])) {
                $roles = explode(',', $userHash['roles']);
                $roles = array_filter(array_map('trim', $roles));
                unset($userHash['roles']);
            }

            $user = (object)$userHash;
            // Set a password.
            if (!isset($user->pass)) {
                $user->pass = $this->getRandom()->name();
            }
            $this->userCreate($user);

            // Assign roles.
            foreach ($roles as $role) {
                $this->getDriver()->userAddRole($user, $role);
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
        } catch (PHPUnit_Framework_AssertionFailedError $exception) {
            // This test may fail if the driver did not load any site yet.
        }

        $this->webDriver->amOnPage('/user/login');
        try {
            $this->webDriver->dontSeeElement(DrupalExtension::SELECTORS_LOGIN_FORM_SELECTOR);

            return true;
        } catch (PHPUnit_Framework_AssertionFailedError $exception) {
            // This test may fail if the driver did not load any site yet.
        }

        $this->webDriver->amOnPage('/');
        try {
            $this->webDriver->seeLink(DrupalExtension::TEXT_LOGO_OUT);

            return true;
        } catch (PHPUnit_Framework_AssertionFailedError $exception) {
            // This test may fail if the driver did not load any site yet.
        }

        return false;
    }

    private function drushAddUser(string $user, string $email, string $password)
    {

    }

    private function drushDeleteUser(string $user)
    {

    }

    private function drushLoginLink(string $user)
    {

    }

    private function drushRemoveRole(string $role, string $user)
    {

    }

    private function drushAddRole(string $role, string $user)
    {

    }

    private function drushGetTypes(): array
    {
        return [];
    }
}
