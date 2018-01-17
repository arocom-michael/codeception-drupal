<?php
declare(strict_types=1);

namespace Codeception\Module\Context;

use Codeception\Module\DrupalDrush;
use Symfony\Component\Process\Process;

/**
 * Provides step definitions for interacting directly with Drush commands.
 *
 * @author Michael A. Johnson Lucas <johnson@arocom.de>
 */
// Work based on this composer package: drupal/drupal-extension
// @author Jonathan Hedstrom <jhedstrom@gmail.com>
// @author Melissa Anderson https://github.com/eliza411
// @author Pieter Frenssen https://github.com/pfrenssen
class DrushContext extends BaseContext
{

    /**
     * Keep track of drush output.
     *
     * @var string
     */
    protected $processOutput;

    /** @var DrupalDrush */
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
    }

    /**
     * Return the most recent drush command output.
     *
     * @return string
     * @throws \RuntimeException
     */
    public function readDrushOutput(): string
    {
        if ($this->processOutput === null) {
            throw new \RuntimeException('No drush output was found.');
        }

        return $this->processOutput;
    }

    /**
     * Run a plain drush command without arguments and options
     * Example: When I run drush "cache-rebuild"
     * Example: When I run drush "cr"
     *
     * @Given I run drush :command
     * @Given I run drush :command command
     *
     * @param string $command
     */
    public function assertDrushCommand(string $command)
    {
        $process = $this->drupalDrush->getDrush($command, [], [], $this->drushAlias);
        $this->webDriver->debug($this->getRunProcessOutput($process));
    }

    /**
     * Run a plain drush command followed by arguments but no options
     * Example: When I run drush "pm-info" "webform_node"
     * Example: When I run drush "pmi" "webform_node"
     *
     * @Given I run drush :command :arguments
     * @Given I run drush :command command :arguments arguments
     *
     * @param string $command
     * @param string $arguments
     */
    public function assertDrushCommandWithArgument(
        string $command,
        string $arguments
    ) {
        $drushArguments = $this->getArguments($arguments);
        $process = $this->drupalDrush->getDrush($command, $drushArguments, [], $this->drushAlias);
        $this->webDriver->debug($this->getRunProcessOutput($process));
    }

    /**
     * Run a plain drush command followed by arguments and options
     * Example:I run drush "pmi" "webform_node" "--format=json"
     * Example: When I run drush "pmi" command "webform_node" arguments "--format=json" options
     *
     * @Given I run drush :command :arguments :options
     * @Given I run drush :command command :arguments arguments :options options
     *
     * @param string $command
     * @param string $arguments
     * @param string $options
     */
    public function assertDrushCommandWithArgumentAndOptions(
        string $command,
        string $arguments,
        string $options
    ) {
        $drushArguments = $this->getArguments($arguments);
        $drushOptions = $this->getArguments($options);
        $process = $this->drupalDrush->getDrush($command, $drushArguments, $drushOptions, $this->drushAlias);
        $this->webDriver->debug($this->getRunProcessOutput($process));
    }

    /**
     * Assert that certain text exist within the output.
     * Example: Then drush output should contain "Cache rebuild complete."
     *
     * @Then drush output should contain :output
     *
     * @param string $output
     */
    public function assertDrushOutput(string $output)
    {
        $this->webDriver->assertContains($output, $this->processOutput);
    }

    /**
     * Assert that certain pattern exist within the output.
     * Example: Then drush output should match "/\b(Title|Date|Status)\b/"
     *
     * @Then drush output should match :regex
     *
     * @param string $regex
     */
    public function assertDrushOutputMatches(string $regex)
    {
        $this->webDriver->assertRegExp($regex, $this->processOutput);
    }

    /**
     * Assert that certain text doesn't exist within the output.
     * Example: Then drush output should not contain "Cache rebuild complete."
     *
     * @Then drush output should not contain :output
     *
     * @param string $output
     */
    public function drushOutputShouldNotContain(string $output)
    {
        $this->webDriver->assertNotContains($output, $this->processOutput);
    }

    /**
     * @param \Symfony\Component\Process\Process $process
     *
     * @return string
     */
    private function getRunProcessOutput(Process $process): string
    {
        $process->run();

        $output = $process->getOutput().PHP_EOL;
        $errorOutput = $process->getErrorOutput().PHP_EOL;
        $this->processOutput = "{$output}[Drush]{$errorOutput}";

        return $this->processOutput;
    }
}
