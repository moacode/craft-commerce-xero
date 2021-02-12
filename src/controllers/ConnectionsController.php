<?php
/**
 * Connections Controller
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

use thejoshsmith\xero\Plugin;
use thejoshsmith\xero\controllers\BaseController;

use Craft;

use yii\web\Response;
use yii\web\BadRequestHttpException;

/**
 * Connections Controller
 */
class ConnectionsController extends BaseController
{
    /**
     * Updates a connection via a PATCH requets
     *
     * @return JSON
     */
    public function actionUpdate()
    {
        $this->_requirePatchRequest();

        $connectionId = $this->request->getBodyParam('connectionId');

        if (empty($connectionId) || !is_numeric($connectionId)) {
            throw new BadRequestHttpException('Connection ID is required.');
        }

        $connection = Plugin::getInstance()
            ->getXeroConnections()
            ->markAsSelected($connectionId);

        return $this->asJson(['success' => true, 'data' => $connection]);
    }

    /**
     * Disconnects a connection
     *
     * @return JSON
     */
    public function actionDisconnect()
    {
        $this->requirePostRequest();
        $xeroConnections = Plugin::getInstance()->getXeroConnections();

        $connectionId = $this->request->getBodyParam('connectionId');

        if (empty($connectionId) || !is_numeric($connectionId)) {
            throw new BadRequestHttpException('Connection ID is required.');
        }

        $xeroConnections->disconnectFromXero($connectionId);

        // Attempt to mark another connection as the now selected one
        $connection = $xeroConnections->getLastCreatedOrUpdated();

        if ($connection) {
            $xeroConnections->markAsSelected($connection->id);
        }

        return $this->asJson([
            'success' => true
        ]);
    }

    private function _requirePatchRequest()
    {
        if (!$this->request->getIsPatch()) {
            throw new BadRequestHttpException('Patch request required');
        }
    }
}
