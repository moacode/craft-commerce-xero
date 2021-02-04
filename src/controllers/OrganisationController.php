<?php
/**
 * Organisation Controller
 *
 * Handles routing of tenant requests
 *
 * PHP version 7.4
 *
 * @category  Controllers
 * @package   CraftCommerceXero
 * @author    Josh Smith <hey@joshthe.dev>
 * @copyright 2021 Josh Smith
 * @license   Proprietary https://github.com/thejoshsmith/craft-commerce-xero/blob/master/LICENSE.md
 * @version   GIT: $Id$
 * @link      https://joshthe.dev
 * @since     1.0.0
 */

namespace thejoshsmith\xero\controllers;

use thejoshsmith\xero\controllers\BaseController;

use Throwable;
use yii\web\HttpException;
use yii\web\Response;

/**
 * Organisation Controller
 */
class OrganisationController extends BaseController
{
    // Public Methods
    // =========================================================================

    /**
     * @throws HttpException
     */
    public function init()
    {
        $this->requirePermission('commerce-Organisation');
        parent::init();
    }

    /**
     * Index of tenants
     *
     * @return Response
     * @throws Throwable
     */
    public function actionIndex(): Response
    {
        return $this->renderTemplate('xero/organisation/_index');
    }
}
