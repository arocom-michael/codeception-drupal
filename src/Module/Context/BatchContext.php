<?php
declare(strict_types=1);

namespace Codeception\Module\Context;

use Codeception\Exception\ElementNotFound;
use Codeception\Util\HttpCode;
use Codeception\Util\Locator;

/**
 * Batch step based on the Batch API.
 *
 * @author Michael A. Johnson Lucas <johnson@arocom.de>
 */
// Work based on this composer package: drupal/drupal-extension
// @author Jonathan Hedstrom <jhedstrom@gmail.com>
// @author Melissa Anderson https://github.com/eliza411
// @author Pieter Frenssen https://github.com/pfrenssen
class BatchContext extends BaseContext
{

    /**
     * Wait for the Batch API to finish.
     *
     * Wait until the id="updateprogress" element is gone,
     * or timeout after 3 minutes (180 s).
     *
     * @Given I wait for the batch job to finish
     *
     * @return void
     *
     * @throws \Exception
     */
    public function waitForTheBatchJobToFinish()
    {
        $this->webDriver->waitForElementNotVisible('#updateprogress', 180);
    }
}
