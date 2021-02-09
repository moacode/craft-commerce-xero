<?php
/**
 * Auth Controller
 *
 * Handles routing of tenant requests
 *
 * PHP version 7.4
 *
 * @category  Controllers
 * @package   CraftCommerceXero
 * @author    Josh Smith <by@joshthe.dev>
 * @copyright 2021 Josh Smith
 * @license   Proprietary https://github.com/thejoshsmith/craft-commerce-xero/blob/master/LICENSE.md
 * @version   GIT: $Id$
 * @link      https://joshthe.dev
 * @since     1.0.0
 */

namespace thejoshsmith\xero\controllers;

use thejoshsmith\xero\Plugin;
use thejoshsmith\xero\controllers\BaseController;
use thejoshsmith\xero\helpers\Xero as XeroHelper;

use Calcinai\OAuth2\Client\Provider\Exception\XeroProviderException;

use Craft;
use craft\helpers\UrlHelper;
use Throwable;
use yii\web\HttpException;
use yii\web\Response;
use yii\web\ServerErrorHttpException;

/**
 * Auth Controller
 */
class AuthController extends BaseController
{
    // Public Methods
    // =========================================================================

    /**
     * @throws HttpException
     */
    public function init()
    {
        $this->requirePermission('xero-Auth');
        parent::init();

        \Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
    }

    /**
     * Index of tenants
     *
     * @return Response
     * @throws Throwable
     */
    public function actionIndex(): Response
    {
        $xeroOAuthService = Plugin::getInstance()->getXeroOAuth();
        $xeroProvider = $xeroOAuthService->getProvider();
        $params = $this->request->getQueryParams();

        // User cancelled the flow...
        if (isset($params['error']) && $params['error'] === 'access_denied') {
            Craft::$app->getSession()->setNotice('Xero connection was cancelled');
            return $this->redirect(UrlHelper::cpUrl('xero'));
        }

        // Trigger the OAuth flow
        if (!isset($params['code']) ) {
            $authUrl = $xeroProvider->getAuthorizationUrl(
                [
                'scope' => $xeroOAuthService->getScopes()
                ]
            );

            // Store a hashed version of the provider state in session
            Craft::$app->session->set(
                'oauth2state',
                $xeroProvider->getState()
            );

            // Off we go to Xero...
            header('Location: ' . $authUrl);
            exit;
        }

        // Check given state against previously stored one to mitigate CSRF attack
        if (empty($_GET['state'])
            || ($_GET['state'] !== Craft::$app->session->get('oauth2state'))
        ) {
            Craft::$app->session->remove('oauth2state');
            exit('Invalid state');
        }

        // Try to get an access token (using the authorization code grant)
        try {

            $token = $xeroProvider->getAccessToken(
                'authorization_code', [
                'code' => $params['code']
                ]
            );

            // Decode information from the access token
            $jwt = XeroHelper::decodeJwt($token->getToken());

            // If you added the openid/profile scopes you can access the authorizing user's identity.
            $identity = $xeroProvider->getResourceOwner($token);

            // Get the tenants that this user is authorized to access
            // and filter them for this authentication event
            $tenants = $xeroProvider->getTenants(
                $token, [
                'authEventId' => $jwt->authentication_event_id
                ]
            );

            // Save the connection data
            $connections = $xeroOAuthService->saveXeroConnection($identity, $token, $tenants);

        } catch (XeroProviderException $xpe) {
            throw new ServerErrorHttpException($xpe->getMessage());
        }

        Craft::$app->getSession()->setNotice('Xero connection successfully saved');

        return $this->redirect(UrlHelper::cpUrl('xero'));
    }
}
