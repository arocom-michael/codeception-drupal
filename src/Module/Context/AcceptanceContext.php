<?php
declare(strict_types=1);

namespace Codeception\Module\Context;

use Behat\Gherkin\Node\TableNode;
use Codeception\Util\HttpCode;

/**
 * Step definitions developed for Drupal ^8.0.
 *
 * @author Michael A. Johnson Lucas <johnson@arocom.de>
 */
class AcceptanceContext extends BaseContext
{

    /**
     * Visit certain url.
     *
     * @param string $url
     * The url string without path base. Example: '/user/login'.* The url string
     *   without path base. Example: '/user/login'.
     *
     * @When I visit the URL :url
     *
     * @return void
     */
    public function visitTheUrl(string $url)
    {
        $this->webDriver->amOnPage($url);
    }

    /**
     * Unpublish latest node.
     *
     * @Given I unpublish the latest node
     *
     * @return void
     *
     * @throws \Codeception\Exception\ModuleConfigException
     */
    public function unpublishTheLatestNode()
    {
        $url = $this->webDriver->_getUrl().'/admin/content';
        $actualStatusCode = $this->getStatusCode($url);
        $this->assertEquals(HttpCode::OK, $actualStatusCode, 'Page not found');
        // Visit the content page.
        $this->webDriver->amOnPage('/admin/content');

        // Find the edit button of the last created node,
        // and get the link (the first element in the table).
        $css = '.view-content table.views-table tbody tr:first-child ul.dropbutton a';
        $this->visitLinkIfVisible($css);

        // Find and click the Unpublish link.
        $css = '#views-form-content-page-1 > table > tbody > tr:nth-child(1) > td.views-field.views-field-operations > div > div > ul > li.delete.dropbutton-action.secondary-action > a';
        $this->webDriver->seeElement($css);
        $this->webDriver->click($css);
    }

    /**
     * Edit the latest node.
     *
     * @Given I edit the latest node
     *
     * @return void
     */
    public function editTheLatestNode()
    {
        $this->webDriver->amOnPage('/admin/content');

        $css = '.view-content table.views-table tbody tr:first-child ul.dropbutton a';
        $this->visitLinkIfVisible($css);
    }

    /**
     * Visit latest node as anonymous user.
     *
     * @Given I visit the latest node as anonymous user
     *
     * @return void
     *
     * @throws \Codeception\Exception\ModuleConfigException
     */
    public function iVisitTheLatestNodeAsAnonymousUser()
    {
        $url = $this->webDriver->_getUrl().'/admin/content';
        $actualStatusCode = $this->getStatusCode($url);
        $this->assertEquals(HttpCode::OK, $actualStatusCode, 'Page not found');
        // Visit the content page.
        $this->webDriver->amOnPage('/admin/content');

        // Find the clickable node title of the last created node,
        // and get the link (the first element in the table).
        $css = '.view-content table.views-table tbody tr:first-child a';
        $link = $this->webDriver->grabAttributeFrom($css, 'href');
        $this->webDriver->amOnPage('/user/logout');
        $this->webDriver->amOnPage($link);
    }

    /**
     * Visit latest node.
     *
     * @Given I visit the latest node
     *
     * @return void
     */
    public function iVisitTheLatestNode()
    {
        $this->webDriver->amOnPage('/admin/content');

        $css = '.view-content table.views-table tbody tr:first-child a';
        $link = $this->webDriver->grabAttributeFrom($css, 'href');
        $this->visitPath($link);
    }

    /**
     * Save the current node.
     * This only works for administrators.
     *
     * @When I save and keep the current node published
     *
     * @return void
     */
    public function iSaveAndKeepTheCurrentNodePublished()
    {
        $xpath = '//*[@id="edit-actions"]/div/div/ul/li[1]/input';
        $this->webDriver->seeElement($xpath);
        $this->webDriver->click($xpath);
    }

    /**
     * Manually regenerate the sitemap.xml.
     *
     * @When I manually regenerate the sitemap.xml
     *
     * @return void
     *
     * @throws \Codeception\Exception\ModuleConfigException
     */
    public function iManuallyRegenerateTheSitemapXml()
    {
        $url = $this->webDriver->_getUrl().'/admin/config/search/simplesitemap';
        $assertStatusCode = $this->getStatusCode($url);
        $this->assertEquals(HttpCode::OK, $assertStatusCode, 'Page not found');
        $this->webDriver->amOnPage('/admin/config/search/simplesitemap');
        $css = '#edit-regenerate-submit';
        $this->webDriver->seeElement($css);
        $this->webDriver->click($css);
    }

    /**
     * @Then I should see the plain text :text
     *
     * @param string $text
     *
     * @return void
     */
    public function iShouldSeeThePlainText(string $text)
    {
        $this->webDriver->see($text);
    }

    /**
     * @Then I should see the plain text in table
     *
     * @example Given I should see the plain text in table
     *  | Sitemap: https://www.alphabet.com/de-de/sitemap.xml |
     *
     * @param \Behat\Gherkin\Node\TableNode $tableNode
     *
     * @return void
     */
    public function iShouldSeeThePlainTextInTable(TableNode $tableNode)
    {
        foreach ($tableNode->getRows() as $row) {
            $this->webDriver->see($row[0]);
        }
    }

    /**
     * @param string $selector
     *
     * @return void
     */
    private function visitLinkIfVisible(string $selector)
    {
        $this->webDriver->seeElement($selector);
        $link = $this->webDriver->grabAttributeFrom($selector, 'href');
        $this->webDriver->amOnPage($link);
    }

    /**
     * @param string $locale
     * The locale as string, example: 'nl-nl'.
     *
     * @Given I am on the frontpage of the locale :locale
     *
     * @return void
     */
    public function iAmOnTheFrontpageOfTheLocale(string $locale)
    {
        $this->webDriver->amOnPage("/{$locale}");
    }

    /**
     * Check if admin toolbar exists.
     *
     * @Then I should have an Admin-Toolbar
     *
     * @return void
     */
    public function iShouldHaveAnAdminToolbar()
    {
        $this->webDriver->seeElement('.adminimal-admin-toolbar');
    }

    /**
     * Checks if the current page contains the given error message
     *
     * @param string $message
     *   string The text to be checked
     *
     * @Then I should see the error message :message
     * @Then I should see the error message containing :message
     *
     * @return void
     */
    public function assertErrorVisible(string $message)
    {
        $this->webDriver->see($message, '#error_message_selector');
    }

    /**
     * Checks if the current page contains the given set of error messages
     *
     * @param TableNode $messages
     *   array An array of texts to be checked
     *
     * @Then I should see the following error message
     * @Then I should see the following error messages
     *
     * @return void
     */
    public function assertMultipleErrors(TableNode $messages) {
        foreach ($messages->getHash() as $key => $value) {
            $message = trim($value['error messages']);
            $this->assertErrorVisible($message);
        }
    }

    /**
     * Checks if the current page does not contain the given error message
     *
     * @param string $message
     *   string The text to be checked
     *
     * @Given I should not see the error message :message
     * @Given I should not see the error message containing :message
     *
     * @return void
     */
    public function assertNotErrorVisible(string $message)
    {
        $this->webDriver->dontSee($message, '#error_message_selector');
    }

    /**
     * Checks if the current page does not contain the given set error messages
     *
     * @param TableNode $messages
     *   array An array of texts to be checked
     *
     * @Then I should not see the following error messages
     *
     * @return void
     */
    public function assertNotMultipleErrors(TableNode $messages)
    {
        foreach ($messages->getHash() as $key => $value) {
            $message = trim($value['error messages']);
            $this->assertNotErrorVisible($message);
        }
    }

    /**
     * Checks if the current page contains the given success message
     *
     * @param $message
     *   string The text to be checked
     *
     * @Then I should see the success message :message
     * @Then I should see the success message containing :message
     *
     * @return void
     */
    public function assertSuccessMessage(string $message)
    {
        $this->webDriver->see($message, '#success_message_selector');
    }

    /**
     * Checks if the current page contains the given set of success messages
     *
     * @param TableNode $messages
     *   array An array of texts to be checked
     *
     * @Then I should see the following success messages
     *
     * @return void
     */
    public function assertMultipleSuccessMessage(TableNode $messages)
    {
        foreach ($messages->getHash() as $key => $value) {
            $message = trim($value['success messages']);
            $this->assertSuccessMessage($message);
        }
    }

    // @TODO
    /**
     * Checks if the current page does not contain the given set of success message
     *
     * @param string $message
     *   string The text to be checked
     *
     * @Given I should not see the success message :message
     * @Given I should not see the success message containing :message
     *
     * @return void
     */
    public function assertNotSuccessMessage(string $message)
    {
        $this->webDriver->dontSee($message, '#success_message_selector');
    }

    /**
     * Checks if the current page does not contain the given set of success messages
     *
     * @param TableNode $messages
     *   array An array of texts to be checked
     *
     * @Then I should not see the following success messages
     *
     * @return void
     */
    public function assertNotMultipleSuccessMessage(TableNode $messages)
    {
        foreach ($messages->getHash() as $key => $value) {
            $message = trim($value['success messages']);
            $this->assertNotSuccessMessage($message);
        }
    }

    /**
     * Checks if the current page contains the given warning message
     *
     * @param string $message
     *   string The text to be checked
     *
     * @Then I should see the warning message :message
     * @Then I should see the warning message containing :message
     *
     * @return void
     */
    public function assertWarningMessage(string $message)
    {
        $this->webDriver->see($message, '#warning_message_selector');
    }

    /**
     * Checks if the current page contains the given set of warning messages
     *
     * @param TableNode $messages
     *   array An array of texts to be checked
     *
     * @Then I should see the following warning messages
     *
     * @return void
     */
    public function assertMultipleWarningMessage(TableNode $messages)
    {
        foreach ($messages->getHash() as $key => $value) {
            $message = trim($value['warning messages']);
            $this->assertWarningMessage($message);
        }
    }

    /**
     * Checks if the current page does not contain the given set of warning message
     *
     * @param string $message
     *   string The text to be checked
     *
     * @Given I should not see the warning message :message
     * @Given I should not see the warning message containing :message
     *
     * @return void
     */
    public function assertNotWarningMessage($message)
    {
        $this->webDriver->dontSee($message, '#warning_message_selector');
    }

    /**
     * Checks if the current page does not contain the given set of warning messages
     *
     * @param TableNode $messages
     *   array An array of texts to be checked
     *
     * @Then I should not see the following warning messages
     *
     * @return void
     */
    public function assertNotMultipleWarningMessage(TableNode $messages)
    {
        foreach ($messages->getHash() as $key => $value) {
            $message = trim($value['warning messages']);
            $this->assertNotWarningMessage($message);
        }
    }

    /**
     * Checks if the current page contain the given message
     *
     * @param string $message
     *   string The message to be checked
     *
     * @Then I should see the message :message
     * @Then I should see the message containing :message
     *
     * @return void
     */
    public function assertMessage(string $message)
    {
        $this->webDriver->see($message, '#message_selector');
    }

    /**
     * Checks if the current page does not contain the given message
     *
     * @param string $message
     *   string The message to be checked
     *
     * @Then I should not see the message :message
     * @Then I should not see the message containing :message
     *
     * @return void
     */
    public function assertNotMessage(string $message)
    {
        $this->webDriver->dontSee($message, '#message_selector');
    }
}
