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
        $this->requirePermission('commerce-Organisation');
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
        $connections = $xeroConnections->getAllConnections();

        // Create a new settings model
        if (empty($orgSettings)) {
            $orgSettings
                = OrganisationSettingsModel::fromConnection($connection);
        }

        return $this->renderTemplate(
            'xero/organisation/_index', compact(
                'pluginSettings',
                'orgSettings',
                'connection',
                'connections'
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

        $orgSettings = new OrganisationSettingsModel();
        $orgSettings->attributes = $data;

        if (! $orgSettings->validate()) {
            $this->setFailFlash(Plugin::t('Couldn’t save organisation settings.'));

            Craft::$app->getUrlManager()->setRouteParams(
                [
                'orgSettings' => $orgSettings
                ]
            );

            return null;
        }

        $connectionRecord = Connection::find()
            ->where(['id' => $orgSettings->connectionId])
            ->one();

        if (empty($connectionRecord)) {
            throw new NotFoundHttpException('Unable to find connection');
        }

        // Assign settings to be serialized and save it.
        $connectionRecord->settings = $orgSettings->getSettings();
        $connectionRecord->save();

        $this->setSuccessFlash(Plugin::t('Organisation Settings saved.'));
        $this->redirectToPostedUrl();
    }
}
