<?php

namespace thejoshsmith\xero\services;

use thejoshsmith\xero\traits\XeroOAuth as XeroOAuthTrait;
use thejoshsmith\xero\traits\XeroOAuthStorage as XeroOAuthStorageTrait;

use craft\base\Component;

/**
 * Service for handling the OAuth authentication with Xero
 *
 * @author Josh Smith <by@joshthe.dev>
 * @since  1.0.0
 */
class XeroOAuth extends Component
{
    /**
     * Use the XeroOAuth trait to handle
     * Xero authentication and OAuth requests
     */
    use XeroOAuthTrait;

    /**
     * Use the XeroOAuthStorage trait to handle
     * the persistance and retrieval of tokens to/from the DB
     */
    use XeroOAuthStorageTrait;
}
