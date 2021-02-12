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
use League\OAuth2\Client\Token\AccessTokenInterface;
use thejoshsmith\xero\Plugin;
use thejoshsmith\xero\events\OAuthEvent;
use thejoshsmith\xero\records\Connection;
use thejoshsmith\xero\records\Credential;

/**
 * @author  Josh Smith <by@joshthe.dev>
 * @package Xero
 */
class XeroConnections extends Component
{
    /**
     * Returns whether there's any active connections for this site
     *
     * @return boolean
     */
    public function hasConnections(): bool
    {
        return $this->getNumberOfConnections() > 0;
    }

    /**
     * Returns all connections
     *
     * @param string $orderBy Order constraint
     *
     * @return array
     */
    public function getAllConnections(string $orderBy = 'tenantName'): array
    {
        return Connection::find()
            ->innerJoinWith('tenant')
            ->orderBy($orderBy)
        ->all();
    }

    /**
     * Returns the current connection object
     *
     * @return Connection|null
     */
    public function getCurrentConnection($with = []): ?Connection
    {
        return Connection::find()
            ->with($with)
            ->where(['selected' => 1])
            ->one();
    }

    /**
     * Returns the number of connections for the passed site
     *
     * @return integer
     */
    public function getNumberOfConnections(): int
    {
        return Connection::find()->count();
    }

    /**
     * Disables all connections
     *
     * @return void
     */
    public function disableAllConnections(): void
    {
        Connection::updateAll([
            'status' => Connection::STATUS_DISCONNECTED
        ]);
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
        // Return early if there's no connected tenants, there's nothing we can do.
        if (count($event->tenants) === 0) {
            return;
        }

        // Check if the user has authorised a specific tenant
        // If so, update the DB to reflect that decision
        $selectedTenant = null;
        foreach($event->tenants as $tenant) {
            if ($tenant->authEventId === $event->jwt->authentication_event_id) {
                $selectedTenant = $tenant;
            }
        }

        // If there's no matching tenant from the auth event Id,
        // just pick the first one in the array.
        //
        // This scenario could happen if the app was authorised with
        // existing organisations on a non-production environment and then
        // deployed without the connection database records.
        if (empty($selectedTenant)) {
             $selectedTenant = $event->tenants[0];
        }

        // Find the connection related to the selected tenant
        $connection = Connection::find()
            ->innerJoinWith('tenant')
            ->where([
                'xero_tenants.tenantId' => $selectedTenant->tenantId
            ])->one();

        if (empty($connection)) {
            throw new Exception(
                'Unable to set connection as selected as it could not be found.
            ');
        }

        $this->markAsSelected($connection->id);
        $this->removeOrphanedCredentials();
    }

    /**
     * Returns a connection record
     *
     * @param  int    $id Id of the connection to fetch
     *
     * @return Connection
     */
    public function getConnectionById(int $id): Connection
    {
        return Connection::find()->where(['id' => $id])->one();
    }

    /**
     * Clean up connection and credentials information
     * We keep resource owner and tenant data as they can be linked to multiple
     *
     * @param AccessTokenInterface $token      Access Token
     * @param Connection           $connection Connection
     *
     * @return void
     */
    public function cleanUpConnection(AccessTokenInterface $token, Connection $connection): void
    {
        Plugin::getInstance()
            ->getXeroOAuth()
            ->disconnect($token, $connection->connectionId);
        $connection->delete();
        $this->removeOrphanedCredentials();
    }

    /**
     * Removes orphaned credentials from the DB
     *
     * @return void
     */
    public function removeOrphanedCredentials()
    {
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
     * Sets the selected connection
     *
     * @param int $connectionId ID of the connection to mark as selected (not the Xero connection ID)
     *
     * @return Connection
     */
    public function markAsSelected(int $connectionId): Connection
    {
        Connection::updateAll([
            'selected' => 0
        ]);

        $connection = $this->getConnectionById($connectionId);

        if (empty($connection)) {
            throw new Exception('Connection not found.');
        }

        $connection->selected = 1;
        $connection->save();

        return $connection;
    }

    /**
     * Disconnects the passed connection from Xero
     *
     * @param  string $connectionId Connection ID
     * @return void
     */
    public function disconnectFromXero(int $connectionId): void
    {
        $connection = $this->getConnectionById($connectionId);
        $credential = $connection->getCredential()->one();

        if (empty($credential)) {
            throw new Exception('Credential not found.');
        }

        $accessToken = Credential::toAccessToken($credential);

        // Disconnect and clean up records
        $this->cleanUpConnection($accessToken, $connection);
    }

    /**
     * Returns the last updated or created connection record
     *
     * @return Connection
     */
    public function getLastCreatedOrUpdated(): Connection
    {
        return Connection::find()
            ->orderBy('dateUpdated DESC, dateCreated DESC')
            ->one();
    }
}
