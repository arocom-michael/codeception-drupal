<?php
declare(strict_types=1);

namespace Codeception\Module\Context;

use Codeception\Exception\ElementNotFound;
use Codeception\Exception\ModuleException;
use Codeception\Exception\ParseException;
use Codeception\Lib\ModuleContainer;
use Codeception\Module;
use Codeception\Module\WebDriver;

/**
 * Parent class with WebDriver and PSR-7 as dependencies.
 * These two are instantiated through dependecy injection.
 *
 * Example: \Codeception\Module\Context\MinkContext
 * YAML file: <suite>.suite.yml
 * modules:
 *   enabled:
 *     - \Codeception\Module\Context\MinkContext
 *   config:
 *     \Codeception\Module\Context\MinkContext:
 *       DI: 'WebDriver'
 *       PSR-7: '\GuzzleHttp\Client'
 *
 * All extra dependency injections must be included within the
 *   public method name _initialize() whenever extending this parent class,
 *   otherwise you will have a stdClass defined within your field or property.
 *
 * @author Michael A. Johnson Lucas <johnson@arocom.de>
 */
class BaseContext extends Module
{

    /** @var WebDriver */
    protected $webDriver;

    /** @var \GuzzleHttp\Client */
    protected $psr7;

    public function __construct(
        ModuleContainer $moduleContainer,
        $config = null
    ) {
        parent::__construct($moduleContainer, $config);
        $this->webDriver = $this->loadDependency('DI');
        $this->psr7 = $this->loadDependency('PSR-7');
    }

    /**
     * Pauses test execution in debug mode.
     * To proceed test press "ENTER" in console.
     * codecept run <suite> <test.feature> [-vv|-vvv|--debug]
     *
     * @When I take a break
     * @When I smoke a cigarette
     */
    public function pauseExecution()
    {
        $this->webDriver->pauseExecution();
    }

    /**
     * @param string $key
     *
     * @return object
     */
    protected function loadDependency(string $key)
    {
        $container = new \stdClass();
        $containerClass = $this->_getConfig($key);
        if ($containerClass === null) {
            return $container;
        }
        $modules = ['DI', 'Drush'];
        if (\in_array($key, $modules, true)) {
            try {
                $container = $this->getModule($containerClass);
            } catch (ModuleException $e) {
            }

            return $container;
        }
        if (class_exists($containerClass)) {
            $container = new $containerClass;

            return $container;
        }

        return $container;
    }

    /**
     * @param string $url
     * @param string|null $method
     *
     * @return int
     */
    protected function getStatusCode(string $url, $method = 'GET'): int
    {
        $response = $this->psr7->request($method, $url);

        return $response->getStatusCode();
    }


    /**
     * @param string $url
     * @param string|null $method
     *
     * @return string
     */
    protected function getBody(string $url, $method = 'GET'): string
    {
        $response = $this->psr7->request($method, $url);

        return $response->getBody()->getContents();
    }

    /**
     * Returns fixed step argument (with \\" replaced back to ")
     *
     * @param string $argument
     *
     * @return string
     */
    protected function fixStepArgument(string $argument): string
    {
        return str_replace('\\"', '"', $argument);
    }

    /**
     * Used whenever I am searching for a element with a specific attribute and
     * value Example: id|name
     *
     * @param string $argument
     *
     * @return array<string, string>
     *
     * @throws \Codeception\Exception\ParseException
     */
    protected function getAttributeAndValueFromArgument(string $argument): array
    {
        $attributeSelector = $this->getAttributeSelector($argument);

        return [$attributeSelector['attribute'] => $attributeSelector['value']];
    }

    /**
     * Used whenever I am searching for a element with a specific attribute and
     * value Example: id|name
     *
     * @param string $argument
     *
     * @return string "[id=name]"
     *
     * @throws \Codeception\Exception\ParseException
     */
    protected function getAttributeSelectorFromArgument(
        string $argument
    ): string {
        $attributeSelector = $this->getAttributeSelector($argument);

        return "[{$attributeSelector['attribute']}={$attributeSelector['value']}]";
    }

    /**
     * @param string $argument
     *
     * @return array<string, string>
     * @throws \Codeception\Exception\ParseException
     */
    private function getAttributeSelector(string $argument): array
    {
        $temporalArray = explode('|', $argument);

        $message = 'Your locator is needs two arguments like "attribute|value"';
        if (\count($temporalArray) !== 2) {
            throw new ParseException($message);
        }

        $emptyArguments = array_filter($temporalArray, function ($values) {
            return empty($values);
        });
        $message = 'Your locator is empty and has to be like "attribute|value"';
        if (\count($emptyArguments) > 0) {
            throw new ParseException($message);
        }

        list($attribute, $value) = $temporalArray;
        // Add quotes if the text contains (a|) space(|s)
        if ((bool)mb_strpos($value, ' ') === true) {
            $value = "'".$value."'";
        }

        return [
            'attribute' => $attribute,
            'value' => $value,
        ];
    }

    /**
     * Retrieve an attribute selector based on a Drupal theme's region
     * Example: [class~-region-header]
     *
     * @param string $region
     *
     * @return string
     */
    protected function getRegionSelector(string $region): string
    {
        return "[class~=region-{$region}]";
    }

    /**
     * Retrieve the CSS property value from a selector
     *
     * @param string $selector
     * @param string $property
     *
     * @return string
     *
     * @throws \Codeception\Exception\ElementNotFound
     */
    protected function getCssValue(string $selector, string $property): string
    {
        $elements = $this->webDriver->_findElements($selector);
        if (\count($elements) === 0) {
            $message = "Element not found using {$selector} as a selector.";
            throw new ElementNotFound($message);
        }
        /** @var \Facebook\WebDriver\Remote\RemoteWebElement $element */
        $element = $elements[0];

        return $element->getCSSValue($property);
    }

    /**
     * Visits provided url or page
     *
     * @param string $url
     *
     * @return void
     */
    protected function visitPath(string $url)
    {
        $parsedUrl = parse_url($url);
        if (array_key_exists('scheme', $parsedUrl)) {
            $this->webDriver->amOnUrl($url);

            return;
        }
        $this->webDriver->amOnPage($url);
    }

    /**
     * Explode a string to retrieve all the arguments.
     * Pipe is the delimiter.
     * Example: "1|2"
     *
     * @param string $input
     *
     * @return array<int, <string>
     */
    protected function getArguments(string $input): array
    {
        $arguments = explode('|', $input);
        $arguments = array_filter($arguments, function ($value) {
            return !empty($value);
        });

        return $arguments;
    }
}
