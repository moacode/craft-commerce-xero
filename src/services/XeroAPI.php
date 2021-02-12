<?php
/**
 * Xero plugin for Craft CMS 3.x
 *
 * Xero - Craft Commerce 2 plugin
 *
 * @copyright 2019 Myles Derham
 * @link      https://www.mylesderham.dev/
 */

namespace thejoshsmith\xero\services;

use Craft;
use Throwable;

use yii\base\Exception;

use craft\base\Component;
use thejoshsmith\xero\Plugin;
use craft\commerce\elements\Order;
use XeroPHP\Models\Accounting\Account;
use XeroPHP\Models\Accounting\Contact;

use XeroPHP\Models\Accounting\Invoice;
use XeroPHP\Models\Accounting\Payment;
use thejoshsmith\xero\models\XeroClient;
use thejoshsmith\xero\records\Connection;
use XeroPHP\Application as XeroApplication;
use XeroPHP\Models\Accounting\LineItem;
use XeroPHP\Remote\Exception\ForbiddenException;
use thejoshsmith\xero\helpers\Xero as XeroHelper;
use XeroPHP\Remote\Exception\BadRequestException;
use XeroPHP\Remote\Exception\NotAvailableException;
use thejoshsmith\xero\records\Invoice as InvoiceRecord;
use XeroPHP\Remote\Exception\RateLimitExceededException;
use XeroPHP\Remote\Exception\OrganisationOfflineException;

/**
 * @author  Myles Derham
 * @author  Josh Smith <by@joshthe.dev>
 * @package Xero
 */
class XeroAPI extends Component
{
    const CACHE_DURATION = 3600; // 1 hour

    /**
     * Defines the number of decimals to use
     *
     * @var integer
     */
    private $decimals = 2;

    /**
     * The Xero client model
     *
     * @var XeroClient
     */
    private $_client;

    /**
     * Stores whether the access token has been refreshed
     *
     * @var boolean
     */
    private $_hasRefreshed = false;

    // Public Methods
    // =========================================================================

    /**
     * Service Constructor
     *
     * @param XeroClient $xeroClient Xero Client model
     * @param array      $config     Component configuration
     */
    public function __construct(XeroClient $xeroClient, array $config = [])
    {
        $this->_client = $xeroClient;
        parent::__construct($config);
    }

    /**
     * Returns whether there's an active connection
     *
     * @author Josh Smith <by@joshthe.dev>
     * @since  1.0.0
     *
     * @return Connection
     */
    public function isConnected(): bool
    {
        $connection = $this->_client->getConnection();

        return !empty($connection)
            && $connection->enabled
            && $connection->status !== Connection::STATUS_DISCONNECTED;
    }

    /**
     * Returns the Xero client application
     *
     * @author Josh Smith <by@joshthe.dev>
     * @since  1.0.0
     *
     * @throws Exception
     * @return XeroApplication
     */
    public function getApplication(): XeroApplication
    {
        try {
            // Immediately refresh the access token
            if (!$this->_hasRefreshed || $this->_client->hasAccessTokenExpired()) {
                $this->_client->refreshAccessToken();
                $this->_hasRefreshed = true;
            }

        } catch (Exception $e) {
            throw new Exception('Something went wrong establishing a Xero connection, check there\'s an active connection.');
        }

        return $this->_client->getApplication();
    }

    public function sendOrder(Order $order)
    {
        if ($order) {
            // find or create the Contact
            $contact = $this->findOrCreateContact($order);
            if ($contact) {
                // create the Invoice
                $invoice = $this->createInvoice($contact, $order);
                // only continue to payment if a payment has been made and payments are enabled
                if ($invoice && $order->isPaid && $this->_client->getOrgSettings()->createPayments) {
                    // before we can make the payment we need to get the Account
                    $account = $this->getAccountByCode($this->_client->getOrgSettings()->accountReceivable);
                    if ($account) {
                        $payment = $this->createPayment($invoice, $account, $order);

                    }
                    return true;
                }
            }
        }
        return false;
    }

    public function findOrCreateContact(Order $order)
    {
        try {
            // this can return either fullname or their username (email hopefully)
            $user = $order->getUser();

            // Define contact details
            // Note: It's possible for customers to _only_ have
            // an email address, so we need to cater for that scenario
            $contactEmail = $user ? $user->email : $order->getEmail();
            $contactName = $user ? $user->getName() : $order->getEmail();
            $contactFirstName = $user->firstName ?? null;
            $contactLastName = $user->lastName ?? null;

            $contact = $this->getApplication()->load(Contact::class)->where(
                '
                Name=="' . $contactName . '" OR
                EmailAddress=="' . $contactEmail . '"
            '
            )->first();

            if (empty($contact) && !isset($contact)) {
                $contact = new Contact($this->getApplication());
                $contact->setName($contactName)
                    ->setFirstName($contactFirstName)
                    ->setLastName($contactLastName)
                    ->setEmailAddress($contactEmail);

                // TODO: add hook (before_save_contact)

                $contact->save();
            }
            return $contact;
        } catch(Throwable $e) {
            $this->_handleException($e);
        }
        return false;
    }

    public function createInvoice(Contact $contact, Order $order)
    {
        $invoice = new Invoice($this->getApplication());
        // get line items
        foreach ($order->getLineItems() as $orderItem) {
            $lineItem = new LineItem($this->getApplication());
            $lineItem->setAccountCode($this->_client->getOrgSettings()->accountSales);
            $lineItem->setDescription($orderItem->description);
            $lineItem->setQuantity($orderItem->qty);
            if ($orderItem->discount > 0) {
                $discountPercentage = (($orderItem->discount / $orderItem->subtotal) * -100);
                $lineItem->setDiscountRate(Plugin::getInstance()->withDecimals($this->decimals, $discountPercentage));
            }
            if ($orderItem->salePrice > 0) {
                $lineItem->setUnitAmount(Plugin::getInstance()->withDecimals($this->decimals, $orderItem->salePrice));
            } else {
                $lineItem->setUnitAmount(Plugin::getInstance()->withDecimals($this->decimals, $orderItem->price));
            }

            // TODO: check for line item adjustments


            // check if product codes should be used and sent (inventory updates)
            if ($this->_client->getOrgSettings()->updateInventory) {
                $lineItem->setItemCode($orderItem->sku);
            }

            $invoice->addLineItem($lineItem);
        }

        // get all adjustments (discounts,shipping etc)
        $adjustments = $order->getOrderAdjustments();
        foreach ($adjustments as $adjustment) {
            // shipping adjustments
            if ($adjustment->type == 'shipping') {
                $lineItem = new LineItem($this->getApplication());
                $lineItem->setAccountCode($this->_client->getOrgSettings()->accountShipping);
                $lineItem->setDescription($adjustment->name);
                $lineItem->setQuantity(1);
                $lineItem->setUnitAmount(Plugin::getInstance()->withDecimals($this->decimals, $order->getTotalShippingCost()));
                $invoice->addLineItem($lineItem);
            } elseif ($adjustment->type == 'discount' ) {
                $lineItem = new LineItem($this->getApplication());
                $lineItem->setAccountCode($this->_client->getOrgSettings()->accountDiscount);
                $lineItem->setDescription($adjustment->name);
                $lineItem->setQuantity(1);
                $lineItem->setUnitAmount(Plugin::getInstance()->withDecimals($this->decimals, $adjustment->amount));
                $invoice->addLineItem($lineItem);
            } elseif ($adjustment->type !== 'tax') {
                $lineItem = new LineItem($this->getApplication());
                $lineItem->setAccountCode($this->_client->getOrgSettings()->accountAdditionalFees);
                $lineItem->setDescription($adjustment->name);
                $lineItem->setQuantity(1);
                $lineItem->setUnitAmount(Plugin::getInstance()->withDecimals($this->decimals, $adjustment->amount));
                $invoice->addLineItem($lineItem);
            }
        }

        // setup invoice
        $invoice->setStatus('AUTHORISED')
            ->setType('ACCREC')
            ->setContact($contact)
            ->setLineAmountType("Exclusive") // TODO: this should be optional (Inclusive/Exclusive)
            ->setCurrencyCode($order->getPaymentCurrency())
            ->setInvoiceNumber($order->reference)
            ->setSentToContact(true)
            ->setDueDate(new \DateTime('NOW'));

        // TODO: add hook (before_invoice_save)

        try {
            // save the invoice
            $invoice->save();

            // Would $orderTotal ever be more than $invoice->Total?
            // If so, what should happen with rounding?
            $orderTotal = Plugin::getInstance()->withDecimals($this->decimals, $order->getTotalPrice());
            if ($invoice->Total > $orderTotal) {

                // caclulate how much rounding to adjust
                $roundingAdjustment = $orderTotal - $invoice->Total;
                $roundingAdjustment = Plugin::getInstance()->withDecimals($this->decimals, $roundingAdjustment);

                // add rounding to invoice
                $lineItem = new LineItem($this->getApplication());
                $lineItem->setAccountCode($this->_client->getOrgSettings()->accountRounding);
                $lineItem->setDescription("Rounding adjustment: Order Total: $".$orderTotal);
                $lineItem->setQuantity(1);
                $lineItem->setUnitAmount($roundingAdjustment);
                $invoice->addLineItem($lineItem);

                // update the invoice with new rounding adjustment
                $invoice->save();
            }

            $invoiceRecord = new InvoiceRecord();
            $invoiceRecord->orderId = $order->id;
            $invoiceRecord->invoiceId = $invoice->InvoiceID;
            $invoiceRecord->save();

            // TODO: add hook (after_invoice_save)

            return $invoice;

        } catch(Throwable $e) {
            $this->_handleException($e);
        }

        return false;

    }

    public function createPayment(Invoice $invoice, Account $account, Order $order)
    {
        try {
            // create the payment
            $payment = new Payment($this->getApplication());
            $payment->setInvoice($invoice)
                ->setAccount($account)
                ->setReference($order->getLastTransaction()->reference)
                ->setAmount(Plugin::getInstance()->withDecimals($this->decimals, $order->getTotalPaid()))
                ->setDate($order->datePaid);
            $payment->save();
            return $payment;
        } catch(Throwable $e) {
            $this->_handleException($e);
        }
        return false;
    }

    public function getAccounts()
    {
        $cacheKey = $this->_client->getCacheKey('xero-accounts');

        try {
            $cache = Craft::$app->getCache();
            $application = $this->getApplication();

            $accounts = XeroHelper::unserialize(Account::class, $cache->get($cacheKey), $application);
            if (empty($accounts)) {
                $accounts = $application->load(Account::class)->execute();
                $cache->set($cacheKey, XeroHelper::serialize($accounts), self::CACHE_DURATION);
            }

        } catch(Throwable $e) {
            $this->_handleException($e);
        }

        return $accounts ?? [];
    }

    public function getAccountByCode($code)
    {
        try {
            $account = $this->getApplication()->load(Account::class)->where('Code=="' . $code . '"')->first();
        } catch(Throwable $e) {
            $this->_handleException($e);;
        }

        return $account ?? null;
    }

    public function getInvoiceFromOrder(Order $order)
    {
        $invoice = InvoiceRecord::find()->where(['orderId' => $order->id])->one();
        if ($invoice && isset($invoice->invoiceId)) {
            return true;
        }
        return false;
    }

    /**
     * Handles Xero API Exceptions
     *
     * @param Throwable $e Exception/Error object
     *
     * @return void
     */
    private function _handleException(Throwable $e): void
    {
        $exceptionType = get_class($e);

        switch($exceptionType) {
        case NotFoundException::class:
            throw new Exception('The resource you requested in Xero could not be found.');
            break;

        case BadRequestException::class:
            throw new Exception($e->getMessage());
            break;

        case ForbiddenException::class:
            // revoke connection
            Plugin::getInstance()
                ->getXeroConnections()
                ->setDisconnected($this->_client->getConnection());
            throw new Exception('You don\'t have access to this organisation. Please re-authenticate to resume access.');
            break;

        case NotAvailableException::class:
        case OrganisationOfflineException::class:
            throw new Exception('Xero is currently offline. Please try again shortly.');
            break;

        case RateLimitExceededException::class:
            throw new Exception('You have exceeded the Xero API rate limit.');
            break;

        default:
            throw new Exception('Something went wrong fetching data from Xero, please try again');
            break;
        }

        Craft::error(
            $e->getMessage(),
            'xero-api'
        );
    }
}
