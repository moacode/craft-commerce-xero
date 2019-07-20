<?php
/**
 * Xero plugin for Craft CMS 3.x
 *
 * Xero - Craft Commerce 2 plugin
 *
 * @link      https://www.mylesderham.dev/
 * @copyright Copyright (c) 2019 Myles Derham
 */

namespace mediabeastnz\xero\records;

use craft\db\ActiveRecord;
use craft\commerce\elements\Order;

use yii\db\ActiveQueryInterface;

class InvoiceRecord extends ActiveRecord
{

    // Public Methods
    // =========================================================================

    public static function tableName(): string
    {
        return '{{%xero_invoices}}';
    }

    public function getOrder(): array
    {
        return Order::findAll($this->orderId);
    }
}
