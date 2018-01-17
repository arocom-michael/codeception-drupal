<?php
declare(strict_types=1);

namespace Codeception\Module\Context;

use Behat\Gherkin\Node\TableNode;
use Codeception\Exception\ModuleConfigException;

/**
 * Steps based on the Mink Extension.
 *
 * @author Michael A. Johnson Lucas <johnson@arocom.de>
 */
// Work based on this composer package: drupal/drupal-extension
// @author Jonathan Hedstrom <jhedstrom@gmail.com>
// @author Melissa Anderson https://github.com/eliza411
// @author Pieter Frenssen https://github.com/pfrenssen
class MinkContext extends BaseContext
{

    /**
     * Opens specified page
     * Example: Given I am on "http://batman.com"
     * Example: And I am on "/articles/isBatmanBruceWayne"
     * Example: When I go to "/articles/isBatmanBruceWayne"
     *
     * @Given I am on :url
     * @Given I go to :url
     *
     * @param string $url
     */
    public function visit(string $url)
    {
        $this->visitPath($url);
    }

    /**
     * Reloads current page
     * Example: When I reload the page
     * Example: And I reload the page
     *
     * @Given I reload the page
     */
    public function reload()
    {
        $this->webDriver->reloadPage();
    }

    /**
     * Moves backward one page in history
     * Example: When I move backward one page
     *
     * @When I move backward one page
     */
    public function back()
    {
        $this->webDriver->moveBack();
    }

    /**
     * Moves forward one page in history
     * Example: And I move forward one page
     *
     * @When I move forward one page
     */
    public function forward()
    {
        $this->webDriver->moveForward();
    }

    /**
     * Presses button with specified id|name|title|alt|value
     * Example: When I press "Log In"
     * Example: And I press "Log In"
     *
     * @When I press :logIn
     *
     * @param string $buttonAttribute
     *
     * @TODO
     */
    public function pressButton(string $buttonAttribute)
    {
        $this->webDriver->click($buttonAttribute);
    }

    /**
     * Clicks link with specified id|title|alt|text
     * Example: When I follow "Log In"
     * Example: And I follow "Log In"
     *
     * @When I follow :linkAttribute
     *
     * @param string $linkAttribute
     *
     * @TODO
     */
    public function clickLink(string $linkAttribute)
    {
        $this->webDriver->click($linkAttribute);
    }

    /**
     * Fills in form field with specified id|name|label|value
     * Example: When I fill in "username" with: "bwayne"
     * Example: And I fill in "bwayne" for "username"
     *
     * @When I fill in :field with :value
     * @When I fill in :field for :value
     *
     * @param string $field
     * @param string $value
     */
    public function fillField(string $field, string $value)
    {
        $this->webDriver->fillField($field, $value);
    }

    /**
     * Fills in form fields with provided table
     * Example: When I fill in the following"
     *              | username | bruceWayne |
     *              | password | iLoveBats123 |
     * Example: And I fill in the following"
     *              | username | bruceWayne |
     *              | password | iLoveBats123 |
     *
     * @When I fill in the following
     *
     * @param \Behat\Gherkin\Node\TableNode $fields
     */
    public function fillFields(TableNode $fields)
    {
        foreach ($fields->getRowsHash() as $field => $value) {
            $this->webDriver->fillField($field, $value);
        }
    }

    /**
     * Selects option in select field with specified id|name|label|value
     * Example: When I select "Bats" from "user_fears"
     * Example: And I select "Bats" from "user_fears"
     *
     * @When I select :option from :select
     *
     * @param string $select
     * @param string $option
     */
    public function selectOption(string $option, string $select)
    {
        $this->webDriver->selectOption($select, $option);
    }

    /**
     * Selects additional option in select field with specified
     * id|name|label|value Example: When I additionally select "Deceased" from
     * "parents_alive_status" Example: And I additionally select "Deceased"
     * from "parents_alive_status"
     *
     * @When I additionally select :option from :select
     *
     * @param string $select
     * @param string $option
     */
    public function additionallySelectOption(string $option, string $select)
    {
        $this->webDriver->selectOption($select, $option);
    }

    /**
     * Checks checkbox with specified id|name|label|value
     * Example: When I check "Pearl Necklace"
     * Example: And I check "Pearl Necklace"
     *
     * @When I check :option
     *
     * @param string $option
     */
    public function checkOption(string $option)
    {
        $this->webDriver->checkOption($option);
    }

    /**
     * Unchecks checkbox with specified id|name|label|value
     * Example: When I uncheck "Broadway Plays"
     * Example: And I uncheck "Broadway Plays"
     *
     * @When I uncheck :option
     *
     * @param string $option
     */
    public function uncheckOption(string $option)
    {
        $this->webDriver->uncheckOption($option);
    }

    /**
     * Attaches file to field with specified id|name|label|value
     * Example: When I attach "bwayne_profile.png" to "profileImageUpload"
     * Example: And I attach "bwayne_profile.png" to "profileImageUpload"
     *
     * @When I attach the file :path to :field
     *
     * @param string $field
     * @param string $path
     *
     * @throws \InvalidArgumentException
     *
     * @TODO Test order of parameters based on PHP annotation
     */
    public function attachFileToField(string $path, string $field)
    {
        $this->webDriver->attachFile($field, $path);
    }

    /**
     * Checks, that current page PATH is equal to specified
     * Example: Then I should be on "/"
     * Example: And I should be on "/bats"
     * Example: And I should be on "http://google.com"
     *
     * @Then I should be on :page
     *
     * @param string $page
     */
    public function assertPageAddress(string $page)
    {
        $this->webDriver->seeCurrentUrlEquals($page);
    }

    /**
     * Checks, that current page is the homepage
     * Example: Then I should be on the homepage
     * Example: And I should be on the homepage
     *
     * @Then I should be on the homepage
     */
    public function assertHomepage()
    {
        $this->webDriver->seeCurrentUrlEquals('/');
    }

    /**
     * Checks, that current page PATH matches regular expression
     * Example: Then the url should match "superman is dead"
     * Example: Then the uri should match "log in"
     * Example: And the url should match "log in"
     *
     * @Then the url should match :pattern
     *
     * @param string $pattern
     */
    public function assertUrlContains(string $pattern)
    {
        $this->webDriver->seeInCurrentUrl($pattern);
    }

    /**
     * Checks, that current page response status is equal to specified
     * Example: Then the response status code should be 200
     * Example: And the response status code should be 400
     *
     * @Then the response status code should be :code
     *
     * @param string $code
     *
     * @throws \Codeception\Exception\ModuleConfigException
     */
    public function assertResponseStatus(string $code)
    {
        $url = $this->webDriver->_getUrl();
        $expectedStatusCode = (int)$code;
        $actualStatusCode = $this->getStatusCode($url);
        $this->webDriver->assertEquals($expectedStatusCode, $actualStatusCode);
    }

    /**
     * Checks, that page contains specified text
     * Example: Then I should see "Who is the Batman?"
     * Example: And I should see "Who is the Batman?"
     *
     * @Then I should see :text
     *
     * @param string $text
     */
    public function assertPageContainsText(string $text)
    {
        $this->webDriver->see($text);
    }

    /**
     * Checks, that page doesn't contain specified text
     * Example: Then I should not see "Batman is Bruce Wayne"
     * Example: And I should not see "Batman is Bruce Wayne"
     *
     * @Then I should not see :text
     *
     * @param string $text
     */
    public function assertPageNotContainsText(string $text)
    {
        $this->webDriver->dontSee($text);
    }

    /**
     * Checks, that page contains text matching specified pattern
     * Example: Then I should see text matching "Batman, the vigilante"
     *
     * @Then I should see text matching :pattern
     *
     * @param string $pattern
     */
    public function assertPageMatchesText(string $pattern)
    {
        $this->webDriver->debug($pattern);
        $message = sprintf(
            'The pattern %s was not found anywhere in the text of the current page.',
            $pattern
        );
        $source = $this->webDriver->grabTextFrom('body');
        $this->webDriver->assertRegExp($pattern, $source, $message);
    }

    /**
     * Checks, that page doesn't contain text matching specified pattern
     * Example: And I should not see "Bruce Wayne, the vigilante"
     *
     * @Then I should not see text matching :pattern
     *
     * @param string $pattern
     *
     * @TODO Test logic
     */
    public function assertPageNotMatchesText(string $pattern)
    {
        $this->webDriver->debug($pattern);
        $message = sprintf(
            'The pattern %s was not found anywhere in the text of the current page.',
            $pattern
        );
        $source = $this->webDriver->grabTextFrom('body');
        $this->webDriver->assertNotRegExp($pattern, $source, $message);
    }

    /**
     * Checks, that HTML response contains specified string
     * Example: Then the response should contain "Batman is the hero Gotham
     * deserves." Example: And the response should contain "Batman is the hero
     * Gotham deserves."
     *
     * @Then the response should contain :text
     *
     * @param string $text
     */
    public function assertResponseContains(string $text)
    {
        $this->webDriver->seeInPageSource($text);
    }

    /**
     * Checks, that HTML response doesn't contain specified string
     * Example: Then the response should not contain "Bruce Wayne is a
     * billionaire, play-boy, vigilante." Example: And the response should not
     * contain "Bruce Wayne is a billionaire, play-boy, vigilante."
     *
     * @Then the response should not contain :text
     *
     * @param string $text
     */
    public function assertResponseNotContains(string $text)
    {
        $this->webDriver->dontSeeInPageSource($text);
    }

    /**
     * Checks, that element with specified CSS contains specified text
     * Example: Then I should see "Batman" in the "heroes_list" element
     * Example: And I should see "Batman" in the "heroes_list" element
     *
     * @Then I should see :text in the :element element
     *
     * @param string $element
     * @param string $text
     */
    public function assertElementContainsText(string $text, string $element)
    {
        $this->webDriver->see($text, $element);
    }

    /**
     * Checks, that element with specified CSS doesn't contain specified text
     * Example: Then I should not see "Bruce Wayne" in the "heroes_alter_egos"
     * element Example: And I should not see "Bruce Wayne" in the
     * "heroes_alter_egos" element
     *
     * @Then I should not see :text in the :element element
     *
     * @param string $element
     * @param string $text
     */
    public function assertElementNotContainsText(string $text, string $element)
    {
        $this->webDriver->dontSee($text, $element);
    }

    /**
     * Checks, that element with specified CSS contains specified HTML
     * Example: Then the "body" element should contain "style=\"color:black;\""
     * Example: And the "body" element should contain "style=\"color:black;\""
     *
     * @Then the :element element should contain :value
     *
     * @param string $element
     * @param string $value
     *
     * @TODO Test logic
     */
    public function assertElementContains(string $element, string $value)
    {
        $innerHtml = $this->webDriver->grabAttributeFrom($element, 'innerHTML');
        $this->webDriver->assertContains($value, $innerHtml);
    }

    /**
     * Checks, that element with specified CSS doesn't contain specified HTML
     * Example: Then the "body" element should not contain
     * "style=\"color:black;\"" Example: And the "body" element should not
     * contain "style=\"color:black;\""
     *
     * @Then the :element element should not contain :value
     *
     * @param string $element
     * @param string $value
     *
     * @TODO Test logic
     */
    public function assertElementNotContains(string $element, string $value)
    {
        $innerHtml = $this->webDriver->grabAttributeFrom($element, 'innerHTML');
        $this->webDriver->assertNotContains($value, $innerHtml);
    }

    /**
     * Checks, that element with specified CSS exists on page
     * Example: Then I should see a "body" element
     * Example: And I should see a "body" element
     *
     * @Then I should see a :element element
     * @Then I should see an :element element
     *
     * @param string $element
     */
    public function assertElementOnPage(string $element)
    {
        $this->webDriver->seeElementInDOM($element);
    }

    /**
     * Checks, that element with specified CSS doesn't exist on page
     * Example: Then I should not see a "canvas" element
     * Example: And I should not see a "canvas" element
     *
     * @Then I should not see a :element element
     * @Then I should not see an :element element
     *
     * @param string $element
     */
    public function assertElementNotOnPage(string $element)
    {
        $this->webDriver->dontSeeElementInDOM($element);
    }

    /**
     * Checks, that form field with specified id|name|label|value has specified
     * value Example: Then the "username" field should contain "bwayne"
     * Example: And the "username" field should contain "bwayne"
     *
     * @Then the :field field should contain :value
     *
     * @param string $field
     * @param string $value
     */
    public function assertFieldContains(string $field, string $value)
    {
        $this->webDriver->seeInField($field, $value);
    }

    /**
     * Checks, that form field with specified id|name|label|value doesn't have
     * specified value Example: Then the "username" field should not contain
     * "batman" Example: And the "username" field should not contain "batman"
     *
     * @Then the :field field should not contain :value
     *
     * @param string $field
     * @param string $value
     */
    public function assertFieldNotContains(string $field, string $value)
    {
        $this->webDriver->dontSeeInField($field, $value);
    }

    /**
     * Checks, that (?P<num>\d+) CSS elements exist on the page
     * Example: Then I should see 5 "div" elements
     * Example: And I should see 5 "div" elements
     *
     * @Then I should see :number :element element
     * @Then I should see :number :element elements
     *
     * @param string $number
     * @param string $element
     */
    public function assertNumElements(string $number, string $element)
    {
        $expectedCount = (int)$number;
        $this->webDriver->seeNumberOfElements($element, $expectedCount);
    }

    /**
     * Checks, that checkbox with specified id|name|label|value is checked
     * Example: Then the "remember_me" checkbox should be checked
     * Example: And the "remember_me" checkbox is checked
     *
     * @Then the :checkbox checkbox should be checked
     * @Then the :checkbox checkbox is checked
     *
     * @param string $checkbox
     */
    public function assertCheckboxChecked(string $checkbox)
    {
        $this->webDriver->seeCheckboxIsChecked($checkbox);
    }

    /**
     * Checks, that checkbox with specified id|name|label|value is unchecked
     * Example: Then the "newsletter" checkbox should be unchecked
     * Example: Then the "newsletter" checkbox should not be checked
     * Example: And the "newsletter" checkbox is unchecked
     *
     * @Then the :checkbox checkbox should be unchecked
     * @Then the :checkbox checkbox should not be checked
     * @Then the :checkbox checkbox is unchecked
     *
     * @param string $checkbox
     */
    public function assertCheckboxNotChecked(string $checkbox)
    {
        $this->webDriver->dontSeeCheckboxIsChecked($checkbox);
    }

    /**
     * Prints current URL to console.
     * Example: Then print current URL
     * Example: And print current URL
     *
     * @Then print current URL
     */
    public function printCurrentUrl()
    {
        $url = '';
        try {
            $url = $this->webDriver->_getUrl();
        } catch (ModuleConfigException $e) {
        }
        echo $url;
    }

    /**
     * Prints last response to console
     * Example: Then print last response
     * Example: And print last response
     *
     * @Then print last response
     *
     * @TODO Test logic
     */
    public function printLastResponse()
    {
        $url = '';
        try {
            $url = $this->webDriver->_getUrl();
        } catch (ModuleConfigException $e) {
        }
        $message = $url.PHP_EOL.PHP_EOL;
        $message .= mb_substr($this->getBody($url), 0, 200).PHP_EOL.PHP_EOL;
        echo $message;
    }
}
