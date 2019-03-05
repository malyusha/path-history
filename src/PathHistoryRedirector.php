<?php

namespace Malyusha\PathHistory;

use Malyusha\PathHistory\Contracts\PathHistoryContract;

class PathHistoryRedirector implements \Spatie\MissingPageRedirector\Redirector\Redirector
{
    public function getRedirectsFor(\Symfony\Component\HttpFoundation\Request $request): array
    {
        $pattern = trim($request->getPathInfo(), '/');
        $pattern = $pattern == '' ? '/' : $pattern;

        $path = app(PathHistoryContract::class)->getRedirectForLink($pattern);

        if ($path === null) {
            return [];
        }

        return [$pattern => $path];
    }
}
