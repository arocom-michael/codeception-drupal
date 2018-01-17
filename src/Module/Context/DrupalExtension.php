<?php
declare(strict_types=1);

namespace Codeception\Module\Context;

/**
 * Interface DrupalExtension
 *
 * @see https://github.com/forumone/behat-testing/blob/master/tests/behat/behat.remote.yml
 *   Behat configuration as a YAML file.
 *
 * @author Michael A. Johnson Lucas <johnson@arocom.de>
 */
interface DrupalExtension
{

    const TEXT_LOGO_OUT = 'Log out';

    const TEXT_LOG_IN = 'Log in';

    const TEXT_PASSWORD_FIELD = 'Password';

    const TEXT_USERNAME_FIELD = 'Username';

    const SELECTORS_LOGGED_IN_SELECTOR = 'body.logged-in';

    const SELECTORS_LOGIN_FORM_SELECTOR = 'form#user-login';
}
