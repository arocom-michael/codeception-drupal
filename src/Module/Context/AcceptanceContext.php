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
}
