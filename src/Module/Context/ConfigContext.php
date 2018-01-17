<?php
declare(strict_types=1);

namespace Codeception\Module\Context;

use Behat\Gherkin\Node\TableNode;
use Codeception\Exception\ParseException;
use Codeception\Module\DrupalDrush;
use Symfony\Component\Process\InputStream;
use Symfony\Component\Process\Process;
use Symfony\Component\Yaml\Yaml;

/**
 * Provides pre-built step definitions for interacting with Drupal config.
 *
 * @author Michael A. Johnson Lucas <johnson@arocom.de>
 */
// Work based on this composer package: drupal/drupal-extension
// @author Jonathan Hedstrom <jhedstrom@gmail.com>
// @author Melissa Anderson https://github.com/eliza411
// @author Pieter Frenssen https://github.com/pfrenssen
class ConfigContext extends BaseContext
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

    /** @var DrupalDrush */
    protected $drupalDrush;

    /** @var string */
    protected $drushAlias;

    /**
     * Keep track of any config that was changed so they can easily be reverted.
     *
     * @var array<string, array<string, mixed>>
     */
    protected $drupalConfiguration ;

    const DRUSH = 'drush';

    /**
     * The constructor should not be overwritten,
     *   if you want to load more dependencies.
     */
    public function _initialize()
    {
        $this->drupalDrush = $this->loadDependency('Drush');
        $this->drushAlias = $this->_getConfig('DrushAlias') ?: self::DRUSH;
        $this->processOutput = '';
        $this->processErrorOutput = '';
        $this->drupalConfiguration = [];
    }

    /**
     * Revert any changed config.
     * DrupalDrush::getDrush('config-set', ['system.site', 'page.front', 'node])
     * Command: drush config-set system.site page.front node
     *
     * @Given I want to revert any changed config
     *
     * @return void
     * @throws \Codeception\Exception\ParseException
     */
    public function cleanConfig()
    {
        foreach ($this->drupalConfiguration as $configName => $keyValue) {
            /** @var $keyValue array<string, mixed> */
            foreach ($keyValue as $key => $value) {
                $drushArguments = [$configName, $key, $value];
                $process = $this->drupalDrush->getDrush('config-set', $drushArguments, [], $this->drushAlias);
                $output = $this->getRunProcessOutput($process);
                $this->webDriver->debug($output);

                $messages = [
                    'message' => "Do you want to update {$key} key in {$configName} config? (y/n):",
                    'confirmation' => "Yes, I want to update {$key} key in {$configName} config",
                    'negation' => "No, I don't want to update {$key} key in {$configName} config",
                ];
                $this->updateKeyInConfig($process, $output, $messages);
            }
        }
        $this->drupalConfiguration = [];
    }

    /**
     * Sets complex configuration.
     * @example Given I set the configuration item :name with the following keys"
     *  | key | value |
     *  | foo | bar   |
     *
     * @param string $configName
     *   The name of the configuration object.
     * @param TableNode $configurationTable
     *   The table listing configuration keys and values.
     *
     * @Given I set the configuration item :name with the following keys
     *
     * @throws \Codeception\Exception\ParseException
     */
    public function setComplexConfig(string $configName, TableNode $configurationTable)
    {
        foreach ($configurationTable->getRowsHash() as $keyColumn => $valueColumn) {
            $this->setConfig($configName, $keyColumn, $valueColumn);
        }
    }

    /**
     * Sets basic configuration item.
     *
     * @param string $name
     *   The name of the configuration object.
     * @param string $key
     *   Identifier to store value in configuration.
     * @param mixed $value
     *   Value to associate with identifier.
     *
     * @Given I set the configuration item :name with key :key to :value
     *
     * @throws \Codeception\Exception\ParseException
     */
    public function setBasicConfig(string $name, string $key, $value)
    {
        $this->setConfig($name, $key, $value);
    }

    /**
     * Sets a value in a configuration object, as a string.
     * It must be a single value, multiple values are not allowed by drush
     * config-set. If your settings is not a string, please consider using:
     *
     * @example drush config-set system.site page.front --format=yaml
     *     --value=true
     *
     * @param string $configName
     *   The name of the configuration object.
     * @param string $key
     *   Identifier to store value in configuration.
     * @param mixed $value
     *   Value to associate with identifier.
     *
     * @throws \Codeception\Exception\ParseException
     */
    private function setConfig(string $configName, string $key, $value)
    {
        $drushArguments = [$configName, $key];
        $process = $this->drupalDrush->getDrush('config-get', $drushArguments, [], $this->drushAlias);
        $output = $this->getRunProcessOutput($process);
        $this->webDriver->debug($output);

        $this->webDriver->assertNotContains("No matching key found in {$configName} config.", $output);
        $this->webDriver->assertNotContains('No config value specified.', $output);

        // Get YAML from drush config-get <config-name> <key>
        $yaml = explode('[Drush]', $output)[0];

        // Backup single configuration based on the output of drush config-get.
        $backup = $this->getSingularity($yaml);
        $this->drupalConfiguration[$backup['configName']][$backup['configKey']] = $backup['configValue'];
        $this->webDriver->debug($this->drupalConfiguration);

        $drushArguments[] = $value;
        $process = $this->drupalDrush->getDrush('config-set', $drushArguments, [], $this->drushAlias);
        $output = $this->getRunProcessOutput($process);
        $this->webDriver->debug($output);

        $messages = [
            'message' => "Do you want to update {$key} key in {$configName} config? (y/n):",
            'confirmation' => "Yes, I want to update {$key} key in {$configName} config",
            'negation' => "No, I don't want to update {$key} key in {$configName} config",
        ];
        $this->updateKeyInConfig($process, $output, $messages);
    }

    /**
     * @param \Symfony\Component\Process\Process $process
     *
     * @return string
     */
    private function getRunProcessOutput(Process $process): string
    {
        $process->start();
        $output = '';
        $errorOutput = '';
        foreach ($process as $type => $data) {
            if ($process::OUT === $type) {
                $output .= $data.PHP_EOL;
            } else {
                $errorOutput .= $data.PHP_EOL;
            }
        }
        $process->stop();

        $this->processOutput = $output;
        $this->processErrorOutput = $errorOutput;

        return "{$output}[Drush]{$errorOutput}";
    }

    /**
     * Check if the YAML key is not nested.
     *
     * @param string $input Parsed YAML file,
     *   using Symfony\Component\Yaml\Yaml::parse($input).
     *
     * @return array <string, string>
     *
     * @throws \Codeception\Exception\ParseException
     */
    private function getSingularity(string $input): array
    {
        $yaml = Yaml::parse($input);
        if (\count($yaml) > 1) {
            throw new ParseException('Your key contains multiple values');
        }
        if (\count($yaml) === 0) {
            throw new ParseException('Your key is empty');
        }
        $key = array_keys($yaml)[0];
        $configValue = array_values($yaml)[0];
        if (\is_array($configValue)) {
            throw new ParseException('Your value contains nested elements');
        }
        list($configName, $configKey) = explode(':', $key);

        return [
            'configName' => $configName,
            'configKey' => $configKey,
            'configValue' => $configValue,
        ];
    }

    /**
     * If the drush config-set commands needs a confirmation like:
     *   Do you want to update {$key} key in {$configName} config? (y/n):
     *
     * @param \Symfony\Component\Process\Process $process
     * @param string $output Console output
     * @param array<string, string> $messages Messages within an associative array
     *   ['message' => '...', 'confirmation' => '...', 'negation' => '...']
     *
     * @return void
     *
     * @throws \Codeception\Exception\ParseException
     */
    private function updateKeyInConfig(Process $process, string $output, array $messages)
    {
        $allowedMessages = ['message', 'confirmation', 'negation'];
        $actualMessages = array_keys($messages);
        $format = 'The messages parameter should be like ';
        $format .= '["%s" => "...", "%s" => "...", "%s" => "..."]';
        $errorMessage = vsprintf($format, $allowedMessages);

        // Compare the two arrays, to check if they contain the same keys.
        if (array_diff($allowedMessages, $actualMessages) !== array_diff($actualMessages, $allowedMessages)) {
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
}
