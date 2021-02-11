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
use XeroPHP\Application as XeroApplication;
use thejoshsmith\xero\records\InvoiceRecord;
use XeroPHP\Models\Accounting\Invoice\LineItem;
use XeroPHP\Remote\Exception\ForbiddenException;
use thejoshsmith\xero\helpers\Xero as XeroHelper;
use thejoshsmith\xero\records\Connection;
use XeroPHP\Remote\Exception\NotAvailableException;
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
            $application = $this->_client->getApplication();
            $credential = $this->_client->getCredential();

            // Immediately refresh the access token if it's expired
            if ($credential->isExpired()) {
                $credential->refreshAccessToken();
            }

        } catch (Exception $e) {
            throw new Exception('Something went wrong establishing a Xero connection, check there\'s an active connection.');
        }

        return $application;
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
            $contact = $this->getApplication()->load('Accounting\\Contact')->where(
                '
                Name=="' . $user->getName() . '" OR
                EmailAddress=="' . $user->getName() . '"
            '
            )->first();
            if (empty($contact) && !isset($contact)) {
                $contact = new Contact($this->connection);
                $contact->setName($user->getName())
                    ->setFirstName($user->firstName)
                    ->setLastName($user->lastName)
                    ->setEmailAddress($user->email);

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
            $lineItem = new LineItem($this->connection);
            $lineItem->setAccountCode($this->_client->getOrgSettings()->accountSales);
            $lineItem->setDescription($orderItem->description);
            $lineItem->setQuantity($orderItem->qty);
            if ($orderItem->discount > 0) {
                $discountPercentage = (($orderItem->discount / $orderItem->subtotal) * -100);
                $lineItem->setDiscountRate(Xero::$plugin->withDecimals($this->decimals, $discountPercentage));
            }
            if ($orderItem->salePrice > 0) {
                $lineItem->setUnitAmount(Xero::$plugin->withDecimals($this->decimals, $orderItem->salePrice));
            } else {
                $lineItem->setUnitAmount(Xero::$plugin->withDecimals($this->decimals, $orderItem->price));
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
                $lineItem = new LineItem($this->connection);
                $lineItem->setAccountCode($this->_client->getOrgSettings()->accountShipping);
                $lineItem->setDescription($adjustment->name);
                $lineItem->setQuantity(1);
                $lineItem->setUnitAmount(Xero::$plugin->withDecimals($this->decimals, $order->getTotalShippingCost()));
                $invoice->addLineItem($lineItem);
            } elseif ($adjustment->type == 'discount' ) {
                $lineItem = new LineItem($this->connection);
                $lineItem->setAccountCode($this->_client->getOrgSettings()->accountDiscount);
                $lineItem->setDescription($adjustment->name);
                $lineItem->setQuantity(1);
                $lineItem->setUnitAmount(Xero::$plugin->withDecimals($this->decimals, $adjustment->amount));
                $invoice->addLineItem($lineItem);
            } elseif ($adjustment->type !== 'tax') {
                $lineItem = new LineItem($this->connection);
                $lineItem->setAccountCode($this->_client->getOrgSettings()->accountAdditionalFees);
                $lineItem->setDescription($adjustment->name);
                $lineItem->setQuantity(1);
                $lineItem->setUnitAmount(Xero::$plugin->withDecimals($this->decimals, $adjustment->amount));
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
            $orderTotal = Xero::$plugin->withDecimals($this->decimals, $order->getTotalPrice());
            if ($invoice->Total > $orderTotal) {

                // caclulate how much rounding to adjust
                $roundingAdjustment = $orderTotal - $invoice->Total;
                $roundingAdjustment = Xero::$plugin->withDecimals($this->decimals, $roundingAdjustment);

                // add rounding to invoice
                $lineItem = new LineItem($this->connection);
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
                ->setAmount(Xero::$plugin->withDecimals($this->decimals, $order->getTotalPaid()))
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
        $cacheKey = $this->_client->getCacheKey('xero-account-by-code');

        try {
            $cache = Craft::$app->getCache();
            $application = $this->getApplication();

            $account = XeroHelper::unserialize(Account::class, $cache->get($cacheKey), $application);
            if (empty($account)) {
                $account = $this->getConnection()->load(Account::class)->where('Code=="' . $code . '"')->first();
                $cache->set($cacheKey, XeroHelper::serialize($account), self::CACHE_DURATION);
            }
        } catch(Exception $e) {
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
        $session = Craft::$app->getSession();

        switch($exceptionType) {
        case NotFoundException::class:
            $session->setError('The resource you requested in Xero could not be found.');
            break;

        case ForbiddenException::class:
            // revoke connection
            Plugin::getInstance()
                ->getXeroConnections()
                ->setDisconnected($this->_client->getConnection());
            $session->setError('You don\'t have access to this organisation. Please re-authenticate to resume access.');
            break;

        case NotAvailableException::class:
        case OrganisationOfflineException::class:
            $session->setError('Xero is currently offline. Please try again shortly.');
            break;

        case RateLimitExceededException::class:
            $session->setError('You have exceeded the Xero API rate limit.');
            break;

        default:
            $session->setError('Something went wrong fetching data from Xero, please try again');
            break;
        }

        Craft::error(
            $e->getMessage(),
            'xero-api'
        );
    }
}
