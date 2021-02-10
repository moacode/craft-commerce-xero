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

use Throwable;
use yii\web\Response;
use yii\web\HttpException;
use thejoshsmith\xero\Plugin;
use yii\web\BadRequestHttpException;
use thejoshsmith\xero\records\Connection;
use thejoshsmith\xero\controllers\BaseController;
use thejoshsmith\xero\models\OrganisationSettings as OrganisationSettingsModel;
use Craft;
use yii\web\NotFoundHttpException;

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
        $this->requirePermission('xero-Organisation');
        parent::init();
    }

    /**
     * Index of tenants
     *
     * @return Response
     */
    public function actionIndex(OrganisationSettingsModel $orgSettings = null)
    {
        $pluginSettings = Plugin::getInstance()->getSettings();
        $xeroConnections = Plugin::getInstance()->getXeroConnections();

        $connection = $xeroConnections->getCurrentConnection();

        // Create a new settings model
        if (empty($orgSettings) && $connection) {
            $orgSettings
                = OrganisationSettingsModel::fromConnection($connection);
        }

        return $this->renderTemplate(
            'xero/organisation/_index', compact(
                'pluginSettings',
                'orgSettings',
                'connection'
            )
        );
    }

    /**
     * Saves organisation settings
     *
     * @return void
     */
    public function actionSaveSettings()
    {
        $this->requirePostRequest();

        $data = $this->request->getBodyParams();

        // Connection ID is a required parameter
        if ( empty($data['connectionId']) ) {
            $this->setFailFlash(Plugin::t('Couldn\'t find the organisation\'s connection.'));
            return null;
        }

        $xeroConnections = Plugin::getInstance()->getXeroConnections();
        $connection = $xeroConnections->getCurrentConnection();

        $orgSettings = new OrganisationSettingsModel();
        $orgSettings->attributes = $data;

        if (! $orgSettings->validate()) {
            $this->setFailFlash(Plugin::t('Couldn’t save organisation settings.'));

            Craft::$app
                ->getUrlManager()
                ->setRouteParams(['orgSettings' => $orgSettings]);

            return null;
        }

        $connection->id = $data['connectionId'];
        $connection->enabled = $data['enabled'] ?? false;
        $connection->settings = $orgSettings->attributes;
        $connection->save();

        $this->setSuccessFlash(Plugin::t('Organisation Settings saved.'));
        return null;
    }
}
