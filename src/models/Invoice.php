<?php
/**
 * Xero plugin for Craft CMS 3.x
 *
 * Xero - Craft Commerce 2 plugin
 *
 * @link      https://www.mylesderham.dev/
 * @copyright Copyright (c) 2019 Myles Derham
 */

namespace thejoshsmith\xero\models;

use craft\base\Model;

use Craft;
use yii\base\Exception;
use craft\commerce\records\Order;
use thejoshsmith\xero\records\Invoice;

class Invoice extends Model
{
    // Public Properties
    // =========================================================================

    public $id;
    public $orderId;
    public $invoiceId;
    public $uid;
    public $dateCreated;
    public $dateUpdated;


    // Public Methods
    // =========================================================================

    public function rules()
    {
        return [
            [['id', 'orderId'], 'number', 'integerOnly' => true],
        ];
    }

    public function getOrder()
    {
        $query = Order::find();
        $query->where(['id' => $this->orderId]);
        return $query->one();
    }

    /**
     * Saves an invoice.
     *
     * @param Invoice $model         The cart to be saved.
     * @param bool    $runValidation should we validate this cart before saving.
     *
     * @throws Exception if the cart does not exist.
     *
     * @return bool Whether the cart was saved successfully.
     */
    public function save(Invoice $model, bool $runValidation = true): bool
    {
        if ($model->id) {
            $record = Invoice::findOne($model->id);

            if (!$record) {
                throw new Exception(
                    Craft::t(
                        'app', 'No Xero invoice record exists with the ID “{id}”',
                        ['id' => $model->id]
                    )
                );
            }
        } else {
            $record = new Invoice();
        }

        if ($runValidation && !$model->validate()) {
            Craft::info('Xero invoice record not saved due to validation error.', __METHOD__);

            return false;
        }

        $record->orderId = $model->orderId;
        $record->invoiceId = $model->invoiceId;

        // Save it!
        $record->save(false);

        // Now that we have a record ID, save it on the model
        $model->id = $record->id;

        return true;
    }
}
