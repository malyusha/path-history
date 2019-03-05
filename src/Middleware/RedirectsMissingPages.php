<?php
/**
 * This file is a part of Laravel Path History package.
 * Developer:   Igor Malyuk <https://github.com/malyusha>
 */

namespace Malyusha\PathHistory\Middleware;

/**
 * Class RedirectsMissingPages
 *
 * @package Malyusha\PathHistory\Middleware
 */
class RedirectsMissingPages extends \Spatie\MissingPageRedirector\RedirectsMissingPages
{
    protected function shouldRedirect($response): bool
    {
        if (! starts_with(request()->getUri(), config('app.url'))) {
            return false;
        }

        return parent::shouldRedirect($response);
    }
}