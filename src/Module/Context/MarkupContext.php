<?php
declare(strict_types=1);

namespace Codeception\Module\Context;

use Codeception\Module\WebDriver;

/**
 * Extensions to the Mink Extension.
 *
 * @author Michael A. Johnson Lucas <johnson@arocom.de>
 */
// Work based on this composer package: drupal/drupal-extension
// @author Jonathan Hedstrom <jhedstrom@gmail.com>
// @author Melissa Anderson https://github.com/eliza411
// @author Pieter Frenssen https://github.com/pfrenssen
class MarkupContext extends BaseContext
{

    /**
     * Checks if a button with id|name|title|alt|value exists in a region
     *
     * @Then I should see the button :button in the :region
     * @Then I should see the button :button in the :region region
     * @Then I should see the :button button in the :region
     * @Then I should see the :button button in the :region region
     *
     * @param string $button
     *   The id|name|title|alt|value of the button
     * @param string $region
     *   The region in which the button should be found
     *
     * @throws \Codeception\Exception\ParseException
     */
    public function assertRegionButton(string $button, string $region)
    {
        $regionSelector = $this->getRegionSelector($region);
        $attributes = $this->getAttributeAndValueFromArgument($button);
        $attributeSelector = $this->getAttributeSelectorFromArgument($button);

        $this->webDriver->performOn(
            $regionSelector,
            function (WebDriver $webDriver) use (
                $attributes,
                $attributeSelector
            ) {
                $buttonSelectors = [
                    'button',
                    '[role=button]',
                    'input[type=submit]',
                ];

                foreach ($buttonSelectors as $buttonSelector) {
                    $amount = $webDriver->_findElements($buttonSelector.$attributeSelector);
                    if (\count($amount) > 0) {
                        $webDriver->seeElement($buttonSelector, $attributes);
                    }
                }
            }
        );
    }

    /**
     * @Then I see the :tag element in the :region
     * @Then I should see the :tag element in the :region region
     *
     * @param string $tag
     * @param string $region
     *
     * @throws \Exception
     */
    public function assertRegionElement(string $tag, string $region)
    {
        $regionSelector = $this->getRegionSelector($region);
        $this->webDriver->performOn(
            $regionSelector,
            function (WebDriver $webDriver) use ($tag) {
                $webDriver->seeElement($tag);
            }
        );
    }

    /**
     * @Then I do not see the :tag element in the :region
     * @Then I should not see the :tag element in the :region region
     *
     * @param string $tag
     * @param string $region
     *
     * @throws \Exception
     */
    public function assertNotRegionElement(string $tag, string $region)
    {
        $regionSelector = $this->getRegionSelector($region);
        $this->webDriver->performOn(
            $regionSelector,
            function (WebDriver $webDriver) use ($tag) {
                $webDriver->dontSeeElement($tag);
            }
        );
    }

    /**
     * @Then I do not see :text in the :tag element in the :region
     * @Then I should not see :text in the :tag element in the :region region
     *
     * @param string $text
     * @param string $tag
     * @param string $region
     *
     * @throws \Exception
     */
    public function assertNotRegionElementText(
        string $text,
        string $tag,
        string $region
    ) {
        $regionSelector = $this->getRegionSelector($region);
        $this->webDriver->performOn(
            $regionSelector,
            function (WebDriver $webDriver) use ($text, $tag) {
                $webDriver->dontSee($text, $tag);
            }
        );
    }

    /**
     * @Then I see the :tag element with the :attribute attribute set to :value in the :region
     * @Then I should see the :tag element with the :attribute attribute set to :value in the :region region
     *
     * @param string $tag
     * @param string $attribute
     * @param string $value
     * @param string $region
     *
     * @throws \Exception
     *
     * @TODO Refactor: to many parameters
     */
    public function assertRegionElementAttribute(
        string $tag,
        string $attribute,
        string $value,
        string $region
    ) {
        $regionSelector = $this->getRegionSelector($region);
        $this->webDriver->performOn(
            $regionSelector,
            function (WebDriver $webDriver) use ($tag, $attribute, $value) {
                $attributeValue = $webDriver->grabAttributeFrom($tag, $attribute);
                $webDriver->assertEquals($value, $attributeValue);
            }
        );
    }

    /**
     * @Then I see :text in the :tag element with the :attribute attribute set to :value in the :region
     * @Then I should see :text in the :tag element with the :attribute attribute set to :value in the :region region
     *
     * @param string $text
     * @param string $tag
     * @param string $attribute
     * @param string $value
     * @param string $region
     *
     * @throws \Exception
     */
    public function assertRegionElementTextAttribute(
        string $text,
        string $tag,
        string $attribute,
        string $value,
        string $region
    ) {
        $regionSelector = $this->getRegionSelector($region);
        $this->webDriver->performOn(
            $regionSelector,
            function (WebDriver $webDriver) use (
                $text,
                $tag,
                $attribute,
                $value
            ) {
                $attributeValue = $webDriver->grabAttributeFrom($tag, $attribute);
                $webDriver->assertEquals($value, $attributeValue);

                $attributeSelector = $this->getAttributeSelectorFromArgument("{$attribute}|{$value}");
                $webDriver->see($text, "{$tag}{$attributeSelector}");
            }
        );
    }

    /**
     * Expected CSS from Google Chrome: * { font-size: "3.6rem"; }
     * Actual CSS from Selenium: * { font-size: "36px"; }
     *
     * @Then I see :text in the :tag element with the :property CSS property set to :value in the :region
     * @Then I should see :text in the :tag element with the :property CSS property set to :value in the :region region
     *
     * @param string $text
     * @param string $tag
     * @param string $property
     * @param string $value
     * @param string $region
     *
     * @throws \Codeception\Exception\ElementNotFound
     * @throws \Exception
     */
    public function assertRegionElementTextCss(
        string $text,
        string $tag,
        string $property,
        string $value,
        string $region
    ) {
        $regionSelector = $this->getRegionSelector($region);
        $this->webDriver->performOn(
            $regionSelector,
            function (WebDriver $webDriver) use (
                $text,
                $tag,
                $property,
                $value
            ) {
                $cssValue = $this->getCssValue($tag, $property);
                $webDriver->assertEquals($value, $cssValue);

                $webDriver->see($text, $tag);
            }
        );
    }
}
