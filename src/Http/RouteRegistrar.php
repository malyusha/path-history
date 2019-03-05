<?php
/**
 * This file is a part of Laravel Path History package.
 * Email:       mii18@yandex.ru
 * Developer:   Igor Malyuk <https://github.com/malyusha>
 */

namespace Malyusha\PathHistory\Http;

class RouteRegistrar
{
    /**
     * @var \Illuminate\Routing\RouteRegistrar
     */
    protected $registrar;

    public function __construct(\Illuminate\Routing\RouteRegistrar $registrar)
    {
        $this->registrar = $registrar;
    }

    public function register(string $name = 'resolve')
    {
        $class = '\Malyusha\PathHistory\Http\Resolver';
        $this->registrar->get('{path}')->name($name)->uses($class)->where('path', '[\w\-/_]+');
    }
}