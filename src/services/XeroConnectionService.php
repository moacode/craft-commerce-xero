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

use Craft;
use craft\base\Component;
use craft\helpers\UrlHelper;

use yii\caching\Cache;

/**
 * @author    Myles Derham
 * @package   Xero
 */
class XeroConnectionService extends Component
{
    // Public Methods
    // =========================================================================

    public function setup()
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

            try {
                
                // retrieve data from cache or API
                $org = $this->getOrganisation($connection);
                $accounts = $this->getAccounts($connection);

                // setup specific account types
                $allAccounts[] = ['label' => 'Please select an account','value' => ''];
                $assetAccounts[] = ['label' => 'Please select an account','value' => ''];
                $revenueAccounts[] = ['label' => 'Please select an account','value' => ''];
                $expensesAccounts[] = ['label' => 'Please select an account','value' => ''];
                
                foreach ($accounts as $account) {
                    // store all accounts
                    $allAccounts[] = [
                        'label' => $account['Type'] . ' - ' . $account['Code'] . ' - ' . $account['Name'],
                        'value' => $account['Code']
                    ];

                    if ($account['Type'] == 'REVENUE') {
                        $revenueAccounts[] = [
                            'label' => $account['Type'] . ' - ' . $account['Code'] . ' - ' . $account['Name'],
                            'value' => $account['Code']
                        ];
                    }
                    if ($account['Type'] == 'EXPENSE') {
                        $expensesAccounts[] = [
                            'label' => $account['Type'] . ' - ' . $account['Code'] . ' - ' . $account['Name'],
                            'value' => $account['Code']
                        ];
                    }
                    if ($account['Type'] == 'BANK' || $account['Type'] == 'CURRENT' || $account['Type'] == 'FIXED') {
                        $assetAccounts[] = [
                            'label' => $account['Type'] . ' - ' . $account['Code'] . ' - ' . $account['Name'],
                            'value' => $account['Code']
                        ];
                    }
                }

            } catch(BadRequestException $e){
                $response = [
                    'message' => $e->getMessage(),
                    'code' => $e->getCode()
                ];

                Craft::error(
                    $e->getMessage(),
                    __METHOD__
                );

                return $repsonse;
            } catch(UnauthorizedException $e){
                $response = [
                    'message' => $e->getMessage(),
                    'code' => $e->getCode()
                ];

                Craft::error(
                    $e->getMessage(),
                    __METHOD__
                );

                return $response;
            }

            Craft::info(
                Craft::t('xero','Xero API connection successful'),
                __METHOD__
            );

            return  [
                'message' => 'Connected',
                'code' => 200,
                'organisation' => $org->Name,
                'allAccounts' => $allAccounts,
                'assetAccounts' => $assetAccounts,
                'salesAccounts' => $revenueAccounts,
                'expensesAccounts' => $expensesAccounts,
            ];
        } else {
            return false;
        }
    }

    public function getAccounts(PrivateApplication $connection)
    {
        return Craft::$app->getCache()->getOrSet('xero-accounts', function () use ($connection) {
            // get all accounts
            return $connection->load('Accounting\\Account')->execute();
        }, 300);
    }

    public function getOrganisation(PrivateApplication $connection)
    {
        return Craft::$app->getCache()->getOrSet('xero-organisation', function () use ($connection) {
            // get the org name
            return $connection->load('Accounting\\Organisation')->first();
        }, 300);
    }

}
