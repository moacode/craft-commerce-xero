<?php
/**
 * Xero plugin for Craft CMS 3.x
 *
 * Xero - Craft Commerce 2 plugin
 *
 * @copyright 2019 Josh Smith
 * @link      https://joshthe.dev/
 */

namespace thejoshsmith\xero\services;


use Craft;
use craft\base\Component;

use craft\db\ActiveQuery;
use thejoshsmith\xero\Plugin;
use thejoshsmith\xero\records\Tenant;
use thejoshsmith\xero\events\OAuthEvent;
use thejoshsmith\xero\records\Connection;
use thejoshsmith\xero\records\Credential;
use thejoshsmith\xero\records\ResourceOwner;

/**
 * @author  Josh Smith <by@joshthe.dev>
 * @package Xero
 */
class XeroConnections extends Component
{
    /**
     * Returns whether there's any active connections for this site
     *
     * @param int $siteId Site ID
     *
     * @return boolean
     */
    public function hasConnections(int $siteId = null): bool
    {
        return $this->getNumberOfConnections($siteId) > 0;
    }

    /**
     * Returns all connections
     *
     * @param integer $siteId Site ID
     *
     * @return array
     */
    public function getAllConnections(): array
    {
        return Connection::find()->all();
    }

    /**
     * Returns the current connection object
     *
     * @param integer $siteId Site ID
     *
     * @return Connection|null
     */
    public function getCurrentConnection(int $siteId = null): ?Connection
    {
        return $this->getSiteConnectionsQuery($siteId)->one();
    }

    /**
     * Returns the number of connections for the passed site
     *
     * @param integer $siteId Site ID
     *
     * @return integer
     */
    public function getNumberOfConnections(int $siteId = null): int
    {
        return $this->getSiteConnectionsQuery($siteId)->count();
    }

    /**
     * Disables all connections for a particular site
     *
     * @param integer $siteId Site ID
     *
     * @return void
     */
    public function disableAllConnections(int $siteId = null): void
    {
        if (empty($siteId)) {
            $siteId = Craft::$app->getSites()->getCurrentSite()->id;
        }

        Connection::updateAll(
            [
            'status' => Connection::STATUS_DISCONNECTED
            ], [
            'siteId' => $siteId
            ]
        );
    }

    /**
     * Handles the after save OAuth event
     * Used to disconnect other connected tenants when a new tenant is connected
     *
     * @param OAuthEvent $event An OAuth event
     *
     * @return void
     */
    public function handleAfterSaveOAuthEvent(OAuthEvent $event): void
    {
        // No new tenants have been authorised, so carry on
        if (count($event->tenants) === 0) {
            return;
        }

        $tenantConnectionIds = array_column($event->tenants, 'id');
        $currentTenantConnections = Connection::find()->where(
            [
            'NOT IN', 'connectionId', $tenantConnectionIds
            ]
        )->all() ?? [];

        // Disconnect all currently connected tenants and remove records
        foreach ($currentTenantConnections as $connection) {
            Plugin::getInstance()
                ->getXeroOAuth()
                ->disconnect($event->token, $connection->connectionId);

            $this->cleanUpConnection($connection);
        }
    }

    /**
     * Clean up connection and credentials information
     * We keep resource owner and tenant data as they can be linked to multiple
     *
     * @param Connection $connection Connection
     *
     * @return void
     */
    public function cleanUpConnection(Connection $connection): void
    {
        $connection->delete();

        // Remove orphaned credentials
        $credentials = Credential::find()
            ->leftJoin(Connection::tableName(), Connection::tableName().'.[[credentialId]] = '.Credential::tableName().'.[[id]]')
            ->where(['IS', Connection::tableName().'.[[id]]', new \yii\db\Expression('null')])
            ->all() ?? [];

        if (!empty($credentials)) {
            $credentialIds = array_column($credentials, 'id');
            Credential::deleteAll(['IN', 'id', $credentialIds]);
        }
    }

    /**
     * Sets the passed connection as disconnected
     *
     * @param Connection $connection Connection object
     *
     * @return Connection
     */
    public function setDisconnected(Connection $connection): Connection
    {
        $connection->status = Connection::STATUS_DISCONNECTED;
        $connection->save();

        return $connection;
    }

    /**
     * Returns a connection query filtered for the current site
     *
     * @param integer $siteId Site ID
     *
     * @return ActiveQuery
     */
    protected function getSiteConnectionsQuery(int $siteId = null): ActiveQuery
    {
        if (empty($siteId)) {
            $siteId = Craft::$app->getSites()->getCurrentSite()->id;
        }

        return Connection::find()->where(['siteId' => $siteId]);
    }
}
