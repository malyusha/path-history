<?php
/**
 * This file is a part of Laravel Path History package.
 * Developer:   Igor Malyuk <https://github.com/malyusha>
 */

namespace Malyusha\PathHistory\Middleware;

use Closure;
use Malyusha\PathHistory\Contracts\PathHistoryContract;

class RedirectsFromOld
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request $request
     * @param  \Closure $next
     *
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $url = app(PathHistoryContract::class)->getRedirectForLink($request->path());

        if ($url !== null) {
            return redirect($url, config('path_history.redirect_status'));
        }

        return $next($request);
    }
}
