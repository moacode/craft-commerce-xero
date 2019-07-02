<?php

namespace mediabeastnz\xero\migrations;

use Craft;
use craft\db\Migration;

/**
 * m190702_081348_AddInXeroColumnToCommerceOrders migration.
 */
class m190702_081348_AddInXeroColumnToCommerceOrders extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        $this->addColumn('{{%commerce_orders}}', 'xeroInvoiceId', $this->string());
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        // echo "m190702_081348_AddInXeroColumnToCommerceOrders cannot be reverted.\n";
        $this->dropColumn('{{%commerce_orders}}', 'xeroInvoiceId');
        return false;
    }
}
