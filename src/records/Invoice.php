<?php
/**
 * Xero plugin for Craft CMS 3.x
 *
 * Xero - Craft Commerce 2 plugin
 *
 * @copyright 2019 Myles Derham
 * @link      https://www.mylesderham.dev/
 */

namespace thejoshsmith\xero\records;

use craft\db\ActiveRecord;
use craft\commerce\elements\Order;

class Invoice extends ActiveRecord
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
