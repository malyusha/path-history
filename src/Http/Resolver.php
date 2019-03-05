<?php
/**
 * This file is a part of Laravel Path History package.
 * Email:       mii18@yandex.ru
 * Developer:   Igor Malyuk <https://github.com/malyusha>
 */

namespace Malyusha\PathHistory\Http;

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
     * @throws \Malyusha\PathHistory\Exceptions\PathHistoryException
     */
    public function __invoke($path)
    {
        $map = (array) config('path_history.controllers', []);
        if (count($map) === 0) {
            // We won't find any controller responsible for path if controllers map is empty.
            throw new NotFoundHttpException;
        }

        $pathInstance = app(PathHistoryContract::class)->getByLink($path, array_keys($map));

        if ($pathInstance === null || ($related = $pathInstance->related) === null) {
            // We failed to find neither instance or related model for path instance. So we'll send 404.
            throw new NotFoundHttpException;
        }

        // Got prefix for model
        $prefix = static::findPrefixForPathInstance($pathInstance);
        if ($prefix !== '' && ! starts_with(request()->path(), $prefix.'/')) {
            // If prefix was found, but request path doesn't start with such prefix we'll know, that we can't process
            // this request because there can be a collision between two paths for different prefixes.
            throw new NotFoundHttpException;
        }

        if (array_key_exists($class = $related->getMorphClass(), $map)) {
            // Call action if we find controller for given entity.
            return $this->proxyControllerCall(app($map[$class]), $related);
        }

        throw new NotFoundHttpException;
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

        throw new PathHistoryException(sprintf('Resolved controller %s must contain `show` method either be invokable'));
    }

    /**
     * Looks up for prefixes presented in configuration for given path history instance relation.
     *
     * @param \Malyusha\PathHistory\Contracts\PathHistoryContract $pathHistory
     *
     * @return string
     */
    protected static function findPrefixForPathInstance(PathHistoryContract $pathHistory): string
    {
        // Retrieve loaded relation
        $related = $pathHistory->related;
        // Retrieve morph type for relation
        $morphType = $related->getMorphClass();

        foreach ((array) config('path_history.prefixes', []) as $prefix) {
            // Look up for type in prefixes map
            if (in_array($morphType, $prefix['types'])) {
                // If we got here it means that related model of path is responsible for returned prefix
                return $prefix['path'];
            }
        }

        return '';
    }
}
