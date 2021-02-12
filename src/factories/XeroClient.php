<?php

namespace thejoshsmith\xero\factories;

use thejoshsmith\xero\Plugin;
use thejoshsmith\xero\models\XeroClient as XeroClientModel;
use thejoshsmith\xero\records\Connection;

use XeroPHP\Application as XeroApplication;

use Craft;
use yii\base\Exception;

/**
 * Factory class for building a configured Xero Client
 *
 * @author Josh Smith <by@joshthe.dev>
 * @since  1.0.0
 */
class XeroClient
{
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

        // Fetch the connection record, eager loading required access info
        $connection = Plugin::getInstance()
            ->getXeroConnections()
            ->getCurrentConnection(['credential', 'resourceOwner', 'tenant']);

        if (empty($connection)) {
            throw new Exception('No connection record found');
        }

        $tenant = $connection->tenant;
        $credential = $connection->credential;
        $resourceOwner = $connection->resourceOwner;

        $application = self::buildApplication($credential, $tenant);

        return new XeroClientModel(
            $application,
            $connection,
            $credential,
            $resourceOwner,
            $tenant
        );
    }

    public static function buildApplication($credential, $tenant)
    {
        return new XeroApplication(
            $credential->accessToken,
            $tenant->tenantId
        );
    }
}
