<?php
/**
 * This file is a part of Laravel Path History package.
 * Developer:   Igor Malyuk <https://github.com/malyusha>
 */

namespace Malyusha\PathHistory\Http;

use Illuminate\Database\Eloquent\Relations\Relation;
use Malyusha\PathHistory\Config;
use Malyusha\PathHistory\Contracts\PathHistoryContract;
use Malyusha\PathHistory\Exceptions\PathHistoryException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class Resolver extends \Illuminate\Routing\Controller
{
    /**
     * Resolves controller and entity for given path.
     *
     * @param $path
     *
     * @return mixed|\Illuminate\Http\Response
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     * @throws \Malyusha\PathHistory\Exceptions\PathHistoryException
     */
    public function __invoke($path)
    {
        $paths = Config::getNormalizedPaths();
        if (count($paths) === 0) {
            // We won't find any controller responsible for path if prefixes map is empty.
            throw new NotFoundHttpException;
        }

        $pathInstance = app(PathHistoryContract::class)->getByLink($path, Config::getAvailableTypes());

        if ($pathInstance === null || ($related = $pathInstance->related) === null) {
            // We failed to find neither instance or related model for path instance. So we'll send 404.
            throw new NotFoundHttpException;
        }
        // Got prefix for model
        $prefixes = static::getMatchedPrefixes($pathInstance);

        $matchedPrefix = $paths[$this->getMatchedPrefixFromRequest($prefixes)];

        // Find morph type
        $morphType = $related->getMorphClass();
        // Get real model class as fallback
        $model = Relation::getMorphedModel($morphType);
        // Find controller from our map of model => controller
        $controller = array_key_exists($morphType, $matchedPrefix) ? $matchedPrefix[$morphType]
            : $matchedPrefix[$model];

        // Call controller action for given entity.
        return $this->proxyControllerCall(app($controller), $related);
    }

    /**
     * Returns prefix that matches start of request path.
     *
     * @param array $prefixes Array of prefixes to search in.
     *
     * @return string
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     */
    protected function getMatchedPrefixFromRequest(array $prefixes): string
    {
        $matched = '';
        foreach ($prefixes as $prefix) {
            if ($prefix !== '' && starts_with(request()->path(), $prefix.'/')) {
                // If request path starts with prefix we need this one
                $matched = $prefix;
            }
        }

        if ($matched === '') {
            // If prefix wasn't found we can't process request
            throw new NotFoundHttpException;
        }

        return $matched;
    }

    /**
     * Proxies controller action call.
     *
     * @param $controller mixed Controller object.
     * @param $entity mixed PathHistory related entity.
     *
     * @return mixed
     * @throws \Malyusha\PathHistory\Exceptions\PathHistoryException
     */
    protected function proxyControllerCall($controller, $entity)
    {
        if (method_exists($controller, 'show')) {
            return $controller->show($entity);
        }
        if (is_callable($controller)) {
            return $controller($entity);
        }

        throw new PathHistoryException(sprintf('Resolved controller %s must contain `show` method either be invokable', $controller));
    }

    /**
     * Looks up for prefixes presented in configuration for given path history instance relation.
     *
     * @param \Malyusha\PathHistory\Contracts\PathHistoryContract $pathHistory
     *
     * @return array
     * @throws \Malyusha\PathHistory\Exceptions\PathHistoryException
     */
    protected static function getMatchedPrefixes(PathHistoryContract $pathHistory): array
    {
        $matched = [];
        // Retrieve related instance
        $related = $pathHistory->related;
        $morphType = $related->getMorphClass();

        foreach (Config::getNormalizedPaths() as $prefix => $map) {
            // Look up for type in prefixes map
            if (array_key_exists($related->getMorphClass(), $map) || array_key_exists(Relation::getMorphedModel($morphType), $map)) {
                // If we got here it means that related model of path is responsible for returned prefix
                $matched[] = $prefix;
            }
        }

        return $matched;
    }
}
