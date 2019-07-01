<?php
/**
 * Xero plugin for Craft CMS 3.x
 *
 * Xero - Craft Commerce 2 plugin
 *
 * @link      https://www.mylesderham.dev/
 * @copyright Copyright (c) 2019 Myles Derham
 */

namespace mediabeastnz\xero\services;

use mediabeastnz\xero\Xero;

use XeroPHP\Application\PrivateApplication;
use XeroPHP\Remote\Exception\BadRequestException;
use XeroPHP\Remote\Exception\UnauthorizedException;
use XeroPHP\Models\Accounting\Contact;
use XeroPHP\Models\Accounting\Invoice;
use XeroPHP\Models\Accounting\Invoice\LineItem;

use Craft;
use craft\commerce\elements\Order;
use craft\base\Component;
use yii\caching\Cache;

/**
 * @author    Myles Derham
 * @package   Xero
 */
class XeroAPIService extends Component
{

    private $connection;

    // Public Methods
    // =========================================================================

    public function init()
    {
        // TODO: throw an error is this fails
        // - this should prevent all requests (connection required)
        $this->connection = $this->getConnection();
    }

    public function getConnection()
    {
        // get the settings before checking connection
        $consumerKey = Xero::$plugin->getSettings()->consumerKey;
        $consumerSecret = Xero::$plugin->getSettings()->consumerSecret;
        $privateKeyPath = Xero::$plugin->getSettings()->privateKeyPath;
        $callbackUrl = Xero::$plugin->getSettings()->callbackUrl;

        // make sure consumer info is defined
        if (isset($consumerKey) && isset($consumerSecret) && isset($privateKeyPath)) {
            
            // check for private key
            if (!is_readable('file://'.CRAFT_BASE_PATH.'/'.$privateKeyPath)) {
                return [
                    'message' => 'Private key can\'t be found.',
                    'code' => 404
                ];
            }

            // setup the request configuration
            $config = [
                'oauth' => [
                    'callback' => $callbackUrl,
                    'consumer_key' => $consumerKey,
                    'consumer_secret' => $consumerSecret,
                    'rsa_private_key' => 'file://'.CRAFT_BASE_PATH.'/'.$privateKeyPath,
                ],
                'curl' => array(
                    CURLOPT_CAINFO => CRAFT_BASE_PATH .'/xero/certificates/ca-bundle.crt',
                ),
            ];

            $connection = new PrivateApplication($config);
            return $connection;
        }

        return false;
    }

    public function findOrCreateContact()
    {
        $contact = $this->connection->load('Accounting\\Contact')->where("Name", '24 Locks')->first();
        if (empty($contact) && !isset($contact)) {
            $contact = new Contact($this->connection);
            $contact->setName("PLato Creative")
                    ->setFirstName("John")
                    ->setLastName("Doe")
                    ->setEmailAddress("john.doe@email.com");
            try {
                $contact->save();
            } catch(Exception $e) {
                $response = [
                    'message' => $e->getMessage(),
                    'code' => $e->getCode()
                ];

                Craft::error(
                    $e->getMessage(),
                    __METHOD__
                );
            }
        }
        return $contact;
    }


    public function createInvoice(Contact $contact, Order $order)
    {
        $invoice = new Invoice($this->connection);
        // get line items
        // get discounts
        // get shipping
        // get other

        foreach ($order->getLineItems() as $orderItem) {
            $lineItem = new LineItem($this->connection);
            $lineItem->setAccountCode(200)
                     ->setDescription($orderItem->snapshot['product']['title'])
                     ->setQuantity($orderItem->qty)
                     ->setUnitAmount($orderItem->salePrice);
            $invoice->addLineItem($lineItem);
        }

        $invoice->setStatus('DRAFT')
                ->setType('ACCREC')
                ->setContact($contact)
                ->setLineAmountType('Inclusive')
                ->setInvoiceNumber($order->reference)
                ->setDueDate(new \DateTime('NOW'));
        try {
            $invoice->save();
        } catch(Exception $e) {
            $response = [
                'message' => $e->getMessage(),
                'code' => $e->getCode()
            ];

            Craft::error(
                $e->getMessage(),
                __METHOD__
            );
        }

    }

}
