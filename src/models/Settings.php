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
    public $caBundlePath;

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
     * @var int
     */
    public $accountRounding;

    /**
     * @var boolean
     */
    public $updateInventory;

    /**
     * @var boolean
     */
    public $createPayments;
    

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
                'caBundlePath',
                'callbackUrl'
            ], 'string'],
            [[
                'consumerKey', 
                'consumerSecret', 
                'privateKeyPath',
                'caBundlePath',
                'callbackUrl', 
                'accountSales',
                'accountShipping',
                'accountRounding',
            //    'accountDiscounts', 
                'accountReceivable',
            ], 'required']
        ];
    }
}
