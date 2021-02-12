<?php
/**
 * Xero plugin for Craft CMS 3.x
 *
 * Xero - Craft Commerce 2 plugin
 *
 * @link      https://www.mylesderham.dev/
 * @copyright Copyright (c) 2019 Myles Derham
 */

namespace thejoshsmith\xero\migrations;

use thejoshsmith\xero\records\AccountCode;
use thejoshsmith\xero\records\Connection;
use thejoshsmith\xero\records\Credential;
use thejoshsmith\xero\records\Invoice;
use thejoshsmith\xero\records\ResourceOwner;
use thejoshsmith\xero\records\Tenant;

use Craft;
use craft\config\DbConfig;
use craft\db\Migration;
use craft\db\Table;

use craft\commerce\db\Table as CommerceTable;

class Install extends Migration
{
    // Public Methods
    // =========================================================================

    public function safeUp()
    {
        $this->createTables();
        $this->addForeignKeys();
    }

    public function safeDown()
    {
        $this->dropTables();
    }

    public function createTables()
    {
        if (!$this->_tableExists(Invoice::tableName())) {
            $this->createTable(
                Invoice::tableName(), [
                'id' => $this->primaryKey(),
                'uid' => $this->uid(),
                'orderId' => $this->integer()->notNull(),
                'invoiceId' => $this->string()->notNull()->defaultValue(''),
                'dateCreated' => $this->dateTime()->notNull(),
                'dateUpdated' => $this->dateTime()->notNull(),
                ]
            );
        }

        if (!$this->_tableExists(Tenant::tableName())) {
            $this->createTable(
                Tenant::tableName(), [
                    'id' => $this->primaryKey(),
                    'tenantId' => $this->string(40)->notNull(),
                    'tenantType' => $this->string(255)->notNull(),
                    'tenantName' => $this->string(255)->notNull(),
                    'dateCreated' => $this->dateTime()->notNull(),
                    'dateUpdated' => $this->dateTime()->notNull(),
                    'uid' => $this->uid(),
                ]
            );
        }

        if (!$this->_tableExists(Credential::tableName())) {
            $this->createTable(
                Credential::tableName(), [
                    'id' => $this->primaryKey(),
                    'accessToken' => $this->text()->notNull(),
                    'refreshToken' => $this->text(),
                    'expires' => $this->timestamp()->notNull(),
                    'scope' => $this->text()->notNull(),
                    'dateCreated' => $this->dateTime()->notNull(),
                    'dateUpdated' => $this->dateTime()->notNull(),
                    'uid' => $this->uid(),
                ]
            );
        }

        if (!$this->_tableExists(ResourceOwner::tableName())) {
            $this->createTable(
                ResourceOwner::tableName(), [
                    'id' => $this->primaryKey(),
                    'xeroUserId' => $this->string(40)->notNull(),
                    'preferredUsername' => $this->string(255)->notNull(),
                    'email' => $this->string(255)->notNull(),
                    'givenName' => $this->string(255)->notNull(),
                    'familyName' => $this->string(255)->notNull(),
                    'dateCreated' => $this->dateTime()->notNull(),
                    'dateUpdated' => $this->dateTime()->notNull(),
                    'uid' => $this->uid(),
                ]
            );
        }

        if (!$this->_tableExists((Connection::tableName()))) {
            $this->createTable(
                Connection::tableName(), [
                'id' => $this->primaryKey(),
                'connectionId' => $this->string(40)->notNull(),
                'credentialId' => $this->integer()->notNull(),
                'resourceOwnerId' => $this->integer()->notNull(),
                'tenantId' => $this->integer()->notNull(),
                'userId' => $this->integer()->notNull(),
                'siteId' => $this->integer()->notNull(),
                'settings' => $this->longText()->notNull(),
                'selected' => $this->boolean()->notNull()->defaultValue(false),
                'enabled' => $this->boolean()->notNull()->defaultValue(false),
                'status' => $this->enum('status', ['connected', 'disconnected', 'expired'])
                    ->notNull()
                    ->defaultValue('connected'),
                'dateCreated' => $this->dateTime()->notNull(),
                'dateUpdated' => $this->dateTime()->notNull(),
                'uid' => $this->uid(),
                ]
            );
        }
    }

    public function addForeignKeys()
    {
        $schema = Craft::$app->db->schema;
        $this->addForeignKey('FK_'.$schema->getRawTableName(Invoice::tableName()).'_'.$schema->getRawTableName(CommerceTable::ORDERS), Invoice::tableName(), ['orderId'], CommerceTable::ORDERS, ['id'], 'CASCADE');
        $this->addForeignKey('FK_'.$schema->getRawTableName(Connection::tableName()).'_'.$schema->getRawTableName(Credential::tableName()), Connection::tableName(), ['credentialId'], Credential::tableName(), ['id']);
        $this->addForeignKey('FK_'.$schema->getRawTableName(Connection::tableName()).'_'.$schema->getRawTableName(ResourceOwner::tableName()), Connection::tableName(), ['resourceOwnerId'], ResourceOwner::tableName(), ['id']);
        $this->addForeignKey('FK_'.$schema->getRawTableName(Connection::tableName()).'_'.$schema->getRawTableName(Tenant::tableName()), Connection::tableName(), ['tenantId'], Tenant::tableName(), ['id']);
        $this->addForeignKey('FK_'.$schema->getRawTableName(Connection::tableName()).'_'.$schema->getRawTableName(Table::USERS), Connection::tableName(), ['userId'], Table::USERS, ['id']);
        $this->addForeignKey('FK_'.$schema->getRawTableName(Connection::tableName()).'_'.$schema->getRawTableName(Table::SITES), Connection::tableName(), ['siteId'], Table::SITES, ['id']);
    }

    public function dropTables()
    {
        $this->dropTableIfExists(Connection::tableName());
        $this->dropTableIfExists(Invoice::tableName());
        $this->dropTableIfExists(Tenant::tableName());
        $this->dropTableIfExists(Credential::tableName());
        $this->dropTableIfExists(ResourceOwner::tableName());
    }

    // Private Methods
    // =========================================================================

    /**
     * Returns if the table exists.
     *
     * @param  string         $tableName
     * @param  Migration|null $migration
     * @return bool If the table exists.
     */
    private function _tableExists(string $tableName): bool
    {
        $schema = $this->db->getSchema();
        $schema->refresh();

        $rawTableName = $schema->getRawTableName($tableName);
        $table = $schema->getTableSchema($rawTableName);

        return (bool)$table;
    }

}
