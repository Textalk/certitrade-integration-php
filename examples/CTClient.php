<?php
/**
 * CTClient, a client implementing the CertiTrade API library in PHP
 *
 * This client demonstrates the nessecary functions to quickly get a shop up
 * and running against the CertiTrade Merchant API library.
 *
 * @copyright Copyright (c) 2013-2014, CertiTrade AB
 * @package CertiTrade Merchant API library
 */

require_once './CTServer.php';

// these needs to be set
$merchantId = '';
$apiKey = '';

// Create a new CTServer instance given a Merchant id, an API key and an optional testing flag.
try {
    $ct_server = new CertiTrade\CTServer($merchantId, $apiKey, true); // Third arg = 'true' gets you a test server, remove for production
} catch (Exception $e) {
    echo $e->getMessage();
    exit();
}



/////
// Example: Create a card payment
/////

$amount = '1000';
$currency = 'SEK';
$callbackUrl = 'https://domain.tld/callback.php';
$returnUrl = 'https://domain.tld/return.php';
$reference = 'a reference';
$description = 'this is a test';
$language = 'sv';

print("Creating payment: \n");
$response = $ct_server->create_card_payment(array('amount' => $amount,
                                                  'currency' => $currency,
                                                  'return_url' => $returnUrl,
                                                  'callback_url' => $callbackUrl,
                                                  'reference' => $reference,
                                                  'description' => $description,
                                                  'language' => $language));

switch (true) {
    // A single resource, like a Payment
    case $response instanceof CertiTrade\Resource:
        print($response);
        print("Payment state : " . $response->state . "\n"); 
        print("Payment id    : " . $response->id . "\n");
        print("PayWin link   : " . $response->getLink("paywin") . "\n");
        break;
    // A collection of resources, like a collection of Payments
    case $response instanceof CertiTrade\Collection:
        print("Response : " . $response . "\n");
        break;
    // An instance of APIProblem, something went wrong.
    case $response instanceof CertiTrade\APIProblem:
        print("HTTP Status : " . $response->getHttpStatus() . "\n");
        print("Title       : " . $response->getTitle() . "\n");
        print("Detail      : " . $response->getDetail() . "\n");
        print("Description : " . $response->getDescribedBy() . "\n");
        break;
    default:
        exit("ERROR: Unhandled PSP response case");
        break;
}
