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
 * @author    Josh Smith <hey@joshthe.dev>
 * @copyright 2021 Josh Smith
 * @license   Proprietary https://github.com/thejoshsmith/craft-commerce-xero/blob/master/LICENSE.md
 * @version   GIT: $Id$
 * @link      https://joshthe.dev
 * @since     1.0.0
 */

namespace thejoshsmith\xero\controllers;

use thejoshsmith\xero\Plugin;
use thejoshsmith\xero\controllers\BaseController;

use Craft;
use Throwable;
use yii\web\HttpException;
use yii\web\Response;

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
    }

    /**
     * Index of tenants
     *
     * @return Response
     * @throws Throwable
     */
    public function actionIndex(): Response
    {
        $xeroApiService = Plugin::getInstance()->getXeroApi();
        $xeroProvider = $xeroApiService->getProvider();

        $params = $this->request->getQueryParams();

        // Trigger the OAuth flow
        if (!isset($params['code']) ) {
            $authUrl = $xeroProvider->getAuthorizationUrl(
                [
                'scope' => $xeroApiService->getScopes()
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
        $token = $xeroProvider->getAccessToken(
            'authorization_code', [
            'code' => $params['code']
            ]
        );

        //If you added the openid/profile scopes you can access the authorizing user's identity.
        $identity = $xeroProvider->getResourceOwner($token);

        //Get the tenants that this user is authorized to access
        $tenants = $xeroProvider->getTenants($token);

        // Todo, store data in database...

        return $this->asJson(
            [
            'tenants' => $tenants,
            'identity' => $identity
            ]
        );
    }
}
