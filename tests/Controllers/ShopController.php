<?php
/**
 * This file is a part of Laravel Path History package.
 * Developer:   Igor Malyuk <https://github.com/malyusha>
 */

namespace Malyusha\PathHistory\Tests\Controllers;

class ShopController extends \Illuminate\Routing\Controller
{
    public function show(): string
    {
        return 'shop_controller_result';
    }
}