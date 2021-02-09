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


use thejoshsmith\xero\Plugin;
use thejoshsmith\xero\records\Connection;

use Craft;
use craft\base\Component;
use craft\db\ActiveQuery;

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
        return $this->getSiteConnectionsQuery($siteId)->where(
            [
            'status' => Connection::STATUS_ENABLED
            ]
        )->one();
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
            'status' => Connection::STATUS_DISABLED
            ], [
            'siteId' => $siteId
            ]
        );
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
