<?php
/**
 * Xero plugin for Craft CMS 3.x
 *
 * Xero - Craft Commerce 2 plugin
 *
 * @link      https://www.mylesderham.dev/
 * @copyright Copyright (c) 2019 Myles Derham
 */

namespace thejoshsmith\xero\controllers;

use craft\web\Controller;

class BaseController extends Controller
{
    /**
     * Initialises the base controller
     *
     * @return void
     */
    public function init()
    {
        $this->requirePermission('accessPlugin-xero');
        parent::init();
    }
}
