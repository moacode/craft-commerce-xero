<?php
/**
 * Xero plugin for Craft CMS 3.x
 *
 * Xero - Craft Commerce 2 plugin
 *
 * @link      https://www.mylesderham.dev/
 * @copyright Copyright (c) 2019 Myles Derham
 */

namespace mediabeastnz\xero\models;

use mediabeastnz\xero\Xero;

use Craft;
use craft\base\Model;

/**
 * @author    Myles Derham
 * @package   Xero
 */
class Settings extends Model
{
    // Public Properties
    // =========================================================================

    /**
     * @var string
     */
    public $consumerKey;

    /**
     * @var string
     */
    public $consumerSecret;

    /**
     * @var string
     */
    public $privateKeyPath;

    /**
     * @var string
     */
    public $callbackUrl;

    /**
     * @var int
     */
    public $accountSales;

    /**
     * @var int
     */
    public $accountShipping;

    /**
     * @var int
     */
    public $accountDiscounts;

    /**
     * @var int
     */
    public $accountReceivable;

    /**
     * @var boolean
     */
    public $updateInventory;
    

    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [[
                'consumerKey', 
                'consumerSecret', 
                'privateKeyPath', 
                'callbackUrl'
            ], 'string'],
            [[
                'consumerKey', 
                'consumerSecret', 
                'privateKeyPath', 
                'callbackUrl', 
                'accountSales',
                'accountShipping', 
                'accountReceivable',
            ], 'required']
        ];
    }
}
