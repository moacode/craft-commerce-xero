<?php

namespace thejoshsmith\xero\factories;

use thejoshsmith\xero\models\XeroClient as XeroClientModel;
use thejoshsmith\xero\records\Connection;
use thejoshsmith\xero\models\OrganisationSettings;

use XeroPHP\Application as XeroApplication;

use Craft;
use yii\base\Exception;

/**
 * Factory class for building a configured Xero Client
 * 
 * @author Josh Smith <by@joshthe.dev>
 * @since 1.0.0
 */
class XeroClient {

	/**
     * Creates an authenticated Xero client
     *
     * @param integer $siteId Defaults to the current site ID
     * @param string  $status Defaults to the connection enabled status
     *
     * @author Josh Smith <by@joshthe.dev>
     * @since  1.0.0
     *
     * @throws Exception
     * @return void
     */
    public static function build(
        int $siteId = null,
        array $where = []
    ): XeroClientModel {

        if (empty($siteId)) {
            $siteId = Craft::$app->getSites()->getCurrentSite()->id;
        }

        // Fetch the connection record, eager loading required access info
        $connection = Connection::find()
            ->where(
                array_merge(['siteId' => $siteId], $where)
            )->with(['credential', 'resourceOwner', 'tenant'])
            ->orderBy('dateCreated DESC')
            ->one();

        if (empty($connection)) {
            throw new Exception('No connection record found');
        }

        $tenant = $connection->tenant;
        $credential = $connection->credential;
        $resourceOwner = $connection->resourceOwner;

        $application = new XeroApplication(
            $credential->accessToken,
            $tenant->tenantId
        );

        return new XeroClientModel(
            $application,
            $connection,
            $credential,
            $resourceOwner,
            $tenant
        );
    }
}