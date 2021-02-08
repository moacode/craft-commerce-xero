<?php

namespace thejoshsmith\xero\services;

use thejoshsmith\xero\records\Connection;
use thejoshsmith\xero\traits\XeroOAuth as XeroOAuthTrait;
use thejoshsmith\xero\traits\XeroOAuthStorage as XeroOAuthStorageTrait;

use XeroPHP\Application as XeroApplication;

use Craft;
use craft\base\Component;
use yii\base\Exception;

/**
 * Service for handling the OAuth authentication with Xero
 *
 * @author Josh Smith <by@joshthe.dev>
 * @since  1.0.0
 */
class XeroOAuth extends Component
{
    /**
     * Use the XeroOAuth trait to handle
     * Xero authentication and OAuth requests
     */
    use XeroOAuthTrait;

    /**
     * Use the XeroOAuthStorage trait to handle
     * the persistance and retrieval of tokens to/from the DB
     */
    use XeroOAuthStorageTrait;

    /**
     * Creates an authenticated Xero client
     *
     * @param integer $siteId  Defaults to the current site ID
     * @param string  $status  Defaults to the connection enabled status
     * @param bool    $refresh Whether to refresh the access token immediately
     *
     * @author Josh Smith <by@joshthe.dev>
     * @since  1.0.0
     *
     * @throws Exception
     * @return void
     */
    public static function createClient(
        int $siteId = null,
        string $status = Connection::STATUS_ENABLED,
        bool $refresh = true
    ): XeroApplication {

        if (empty($siteId)) {
            $siteId = Craft::$app->getSites()->getCurrentSite()->id;
        }

        // Fetch the connection record, eager loading required access info
        $connection = Connection::find()->where(
            [
            'siteId' => $siteId,
            'status' => $status
            ]
        )->with(['credential', 'tenant'])->one();

        if (empty($connection)) {
            throw new Exception('No connection record found');
        }

        $tenant = $connection->tenant;
        $credential = $connection->credential;

        // Immediately refresh the access token if it's expired
        if ($credential->isExpired() && $refresh ) {
            $credential->refreshAccessToken();
        }

        return new XeroApplication(
            $credential->accessToken,
            $tenant->tenantId
        );
    }
}
