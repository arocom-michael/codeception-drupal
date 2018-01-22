<?php
declare(strict_types=1);

namespace Codeception\Module\Context;

use PHPUnit\Framework\AssertionFailedError;

/**
 * Provides the raw functionality for interacting with Drupal.
 *
 * @author Michael A. Johnson Lucas <johnson@arocom.de>
 */
// Work based on this composer package: drupal/drupal-extension
// @author Jonathan Hedstrom <jhedstrom@gmail.com>
// @author Melissa Anderson https://github.com/eliza411
// @author Pieter Frenssen https://github.com/pfrenssen
class RawDrupalContext extends BaseContext
{

    /**
     * Logs the current user out.
     * @Given I logout
     *
     * @TODO Check if the step is necessary
     */
    public function logout()
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
    public function loggedIn(): bool
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
}
