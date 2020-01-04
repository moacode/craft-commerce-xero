<?php

return [
    '*' => [
        "consumerKey" => "",
        "consumerSecret" => "",
        "privateKeyPath" => "", // path to private key e.g. certs/privatekey.pem
        "caBundlePath" => "", // path to ca bundle e.g. certs/ca-bundle.crt
        "callbackUrl" => "", // site URL e.g. google.com
        "accountSales" => 0, // int
        "accountShipping" => 0, // int
        "accountDiscounts" => 0, // int
        "accountAdditionalFees" => 0, // int
        "accountReceivable" => 0, // int
        "accountRounding" => 0, // int
        "updateInventory" => "",
        "createPayments" => "",
    ],
    // add other environments here e.g. dev, staging, production
];