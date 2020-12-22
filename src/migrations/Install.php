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

use thejoshsmith\xero\Xero;

use Craft;
use craft\config\DbConfig;
use craft\db\Migration;

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
        if (!$this->_tableExists('{{%xero_invoices}}')) {
            $this->createTable('{{%xero_invoices}}', [
                'id' => $this->primaryKey(),
                'uid' => $this->uid(),
                'orderId' => $this->integer()->notNull(),
                'invoiceId' => $this->string()->notNull()->defaultValue(''),
                'dateCreated' => $this->dateTime()->notNull(),
                'dateUpdated' => $this->dateTime()->notNull(),
            ]);
        }
    }

    public function addForeignKeys()
    {
        $this->addForeignKey(null, '{{%xero_invoices}}', ['orderId'], '{{%commerce_orders}}', ['id'], 'CASCADE', null);
    }

    public function dropTables()
    {
        $this->dropTableIfExists('{{%xero_invoices}}');
    }

    // Private Methods
    // =========================================================================

    /**
     * Returns if the table exists.
     *
     * @param string $tableName
     * @param Migration|null $migration
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
