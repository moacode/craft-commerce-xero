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
 * @author    Josh Smith <by@joshthe.dev>
 * @copyright 2021 Josh Smith
 * @license   Proprietary https://github.com/thejoshsmith/craft-commerce-xero/blob/master/LICENSE.md
 * @version   GIT: $Id$
 * @link      https://joshthe.dev
 * @since     1.0.0
 */

namespace thejoshsmith\xero\controllers;

use yii\web\Response;
use yii\web\HttpException;
use thejoshsmith\xero\Plugin;
use thejoshsmith\xero\records\Connection;
use thejoshsmith\xero\controllers\BaseController;
use thejoshsmith\xero\models\OrganisationSettings as OrganisationSettingsModel;
use Craft;
use yii\web\NotFoundHttpException;

/**
 * Connections Controller
 */
class ConnectionsController extends BaseController
{
    // Public Methods
    // =========================================================================

    /**
     * @throws HttpException
     */
    public function init()
    {
        $this->requirePermission('xero-Connections');
        parent::init();
    }

    /**
     * Index of tenants
     *
     * @return Response
     */
    public function actionIndex()
    {
        $pluginSettings = Plugin::getInstance()->getSettings();
        $xeroConnections = Plugin::getInstance()->getXeroConnections();
        $connections = $xeroConnections->getAllConnections();

        return $this->renderTemplate(
            'xero/connections/_index', compact(
                'pluginSettings',
                'connections'
            )
        );
    }
}
