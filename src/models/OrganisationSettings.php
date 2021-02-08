<?php

namespace thejoshsmith\xero\models;

use craft\base\Model;
use thejoshsmith\xero\records\Connection;

class OrganisationSettings extends Model
{
    public $connectionId;
    public $createPayments;
    public $updateInventory;
    public $accountSales;
    public $accountReceivable;
    public $accountShipping;
    public $accountRounding;
    public $accountDiscounts;
    public $accountAdditionalFees;

    /**
     * Returns a new model from the passed connection object
     *
     * @param Connection $connection A connection object
     *
     * @return self
     */
    public static function fromConnection(Connection $connection): self
    {
        $settings = json_decode($connection->settings);

        $organisationSettings = new static();

        $organisationSettings->connectionId = $connection->id;
        $organisationSettings->createPayments = $settings->createPayments ?? null;
        $organisationSettings->updateInventory = $settings->updateInventory ?? null;
        $organisationSettings->accountSales = $settings->accountSales ?? null;
        $organisationSettings->accountReceivable = $settings->accountReceivable ?? null;
        $organisationSettings->accountShipping = $settings->accountShipping ?? null;
        $organisationSettings->accountRounding = $settings->accountRounding ?? null;
        $organisationSettings->accountDiscounts = $settings->accountDiscounts ?? null;
        $organisationSettings->accountAdditionalFees = $settings->accountAdditionalFees ?? null;

        return $organisationSettings;
    }

    public function rules()
    {
        parent::rules();

        return [
            [['connectionId', 'accountSales', 'accountReceivable', 'accountShipping', 'accountRounding'], 'required'],
            [['connectionId', 'accountSales', 'accountReceivable', 'accountShipping', 'accountRounding'], 'integer'],
            [['createPayments', 'updateInventory'], 'boolean']
        ];
    }

    /**
     * Returns an array of settings values to be serialized
     *
     * @return array
     */
    public function getSettings(): array
    {
        return [
            'createPayments' => $this->createPayments,
            'updateInventory' => $this->updateInventory,
            'accountSales' => $this->accountSales,
            'accountReceivable' => $this->accountReceivable,
            'accountShipping' => $this->accountShipping,
            'accountRounding' => $this->accountRounding,
            'accountDiscounts' => $this->accountDiscounts,
            'accountAdditionalFees' => $this->accountAdditionalFees,
        ];
    }
}
