<?php
/**
 * CTServer, library implementing the CertiTrade API in PHP
 *
 * This library contains the nessecary functions to quickly get a shop up
 * and running against the CertiTrade Merchant API.
 *
 * @author Carl Bagge (carl@certitrade.net / carl@bagge.co)
 * @copyright Copyright (c) 2013-2014, CertiTrade AB
 * @link http://www.certitrade.net/docs/CertiTrade_Merchant_API.pdf The CertiTrade Merchant API documentation
 */

/**
 * Require Guzzle.phar
 *
 * This library uses Guzzle as a RESTful HTTP Client.
 *
 * @link http://docs.guzzlephp.org/en/latest/ The Guzzle homepage
 */

namespace CertiTrade;

require_once 'Resource.php';
require_once 'Collection.php';
require_once 'APIProblem.php';

use Guzzle\Http\Client;

/**
 * CTServer
 */
class CTServer {
    /**
     * The unique merchant id
     * @internal
     * @var string Merchant identity number
     */
    protected $_merchant_id;
    /**
     * The secret API key.
     * @internal
     * @var string The key needed to sign requests to the API.
     */
    protected $_api_key;
    /**
     * The base of the given url (for example: https://merchantapi.tld in https://merchantapi.tld/api-ws)
     * @internal
     * @var string base url
     */
    protected $_base_url;
    /**
     * The resource part of the given url (for example: /api-ws in https://merchantapi.tld/api-ws)
     * @internal
     * @var string resource url
     */
    protected $_resource_url;

    /**
     * Constructor for CTServer. Verifies and sets the parameters for connection to the CertiTrade Server.
     * @param int $merchant_id API user id.
     * @param string $api_key API key.
     * @param string $url CTPSP url
     */
    function __construct($merchant_id, $api_key, $testing = false) {
        if (!isset($merchant_id) || !is_numeric($merchant_id)) {
            throw new \Exception("Bad merchant ID - check MerchantCredentials.php. \n");
        }
        if (!isset($api_key) || !ctype_alnum($api_key)) {
            throw new \Exception("Bad API key - check MerchantCredentials.php. \n");
        }

        $this->_merchant_id = $merchant_id;
        $this->_api_key = $api_key;
        $this->_resource_url = "/ctpsp/ws/2.0";

        if ($testing) {
            $this->_base_url = "https://apitest.certitrade.net";
        }
        else {
            $this->_base_url = "https://api.certitrade.net";
        }
    }

    ////
    // Payment methods
    ////

    /**
     * Creates a new Card Payment.
     *
     * The resulting response body contains details and a link to a Payment Window where
     * the customer needs to be sent next.
     *
     * @param  int $amount amount to be withdrawn from the card in the currency's lowest unit.
     * @param  string $currency the currency code according to ISO 4217 (for example SEK, EUR, USD).
     * @param  string $return_url the url the customer should be sent to after completed payment.
     * @param  string $callback_url the url the webservice should call (POST) for feedback.
     * @param  string $reference (OPTIONAL) purchase reFerence that the merchant can set and use.
     * @param  string $language (OPTIONAL) language code (ISO 639-1), for use with the Payment Window.
     * @param  string $description (OPTIONAL) a more detailed description of the purchase. Also shown in the Payment Window.
     * @param  array  $customer (OPTIONAL) A structure containing Customer Information. See the API docs for more information.
     * @param  array  $products (OPTIONAL) A structure containing detailed information about the purchase. See the API docs for more information.
     * @return Resource|Collection|APIProblem
     */
    //public function create_card_payment($amount = null, $currency, $return_url, $callback_url, $reference = null, $language = null, $description = null, $customer = array(), $products = array()) {
    public function create_card_payment($payment_data) {
        $payment_data['method'] = "CARD";

        $api_resource = "payment";
        $api_verb = "POST";

        $response = $this->call_certiTrade($api_verb, $api_resource, $payment_data, false);

        return $response;
    }

    /**
     * Creates a new Payment Account.
     * Payment accounts are required to make recurring transactions.
     *
     * @return Resource|Collection|APIProblem
     */
    public function create_payment_account() {
        $account_data = array(
            "type"    => "RECURRING");

        $api_resource = "payment_account";
        $api_verb = "POST";

        $response = $this->call_certiTrade($api_verb, $api_resource, $account_data, false);

        return $response;
    }

    /**
     * This creates a Payment and initializes a Payment Account.
     *
     * The initialization entails sending the customer to a Payment Window where
     * they enter thier card details. The link to the Payment Window and details about the Payment can be found in the response body.
     *
     * @param  int $amount amount to be withdrawn from the card in the currency's lowest unit.
     * @param  string $currency the currency code according to ISO 4217 (for example SEK, EUR, USD).
     * @param  string $return_url the url the customer should be sent to after completed payment.
     * @param  string $callback_url the url the webservice should call for feedback.
     * @param  string $payment_account a Payment Account (ISO 639-1) reference (given in the response body of create_payment_account()).
     * @param  string $reference (OPTIONAL) purchase reference that the merchant can set and use.
     * @param  string $language (OPTIONAL) language code, for use with the Payment Window.
     * @param  string $description (OPTIONAL) a more detailed description of the purchase. Also shown in the Payment Window.
     * @param  array  $customer (OPTIONAL) A structure containing Customer Information. See the API docs for more information.
     * @param  array  $products (OPTIONAL) A structure containing detailed information about the purchase. See the API docs for more information.
     * @return Resource|Collection|APIProblem
     */
    public function initialize_recurring($payment_data) {

        $payment_data['initialize'] = 1;
        $payment_data['method'] = "RECURRING";

        $api_resource = "payment";
        $api_verb = "POST";

        $response = $this->call_certiTrade($api_verb, $api_resource, $payment_data, false);

        return $response;
    }

    /**
     * Debit an initialized Payment Account.
     *
     * To do this you first need to have created a Payment Account (create_payment_account()) and then
     * initialized it (initialize_recurring()).
     *
     * @param  int $amount amount to be withdrawn from the card.
     * @param  string $currency the currency code according to ISO 4217 (for example SEK, EUR, USD).
     * @param  string $payment_account a reference to an initialized Payment Account (given in the response body of initialize_recurring()).
     * @param  string $reference (OPTIONAL) purchase reference that the merchant can use.
     * @param  string $language (OPTIONAL) language code (ISO 639-1), for use with the Payment Window.
     * @param  string $description (OPTIONAL) a more detailed description of the purchase. Also shown in the Payment Window.
     * @param  array  $customer (OPTIONAL) A structure containing Customer Information. See the API docs for more information.
     * @param  array  $products (OPTIONAL) A structure containing detailed information about the purchase. See the API docs for more information.
     * @return Resource|Collection|APIProblem
     */
    public function debit_recurring($amount = null, $currency, $payment_account, $reference = null, $language = null, $description = null, $customer = array(), $products = array()) {
        $data = array(
            "currency"        => $currency,
            "method"          => "RECURRING",
            "payment_account" => $payment_account);

        if (isset($amount)) {
            $data['amount'] = $amount;
        }
        if (isset($reference)) {
            $data['reference'] = $reference;
        }
        if (isset($language)) {
            $data['language'] = $language;
        }
        if (isset($description)) {
            $data['description'] = $description;
        }
        if (!empty($customer)) {
            $data['customer'] = $customer;
        }
        if (!empty($products)) {
            $data['products'] = $products;
        }

        $api_resource = "payment";
        $api_verb = "POST";

        $response = $this->call_certiTrade($api_verb, $api_resource, $data, false);

        return $response;
    }

    /**
     * Approve a Payment for capture.
     *
     * This is used by Merchants who use manual capture.
     *
     * @param  string $payment_id a Payment reference number
     * @return Resource|Collection|APIProblem
     */
    public function approve_for_capture($payment_id) {
        $data = array("state" => "READY_FOR_CAPTURE");

        $api_resource = "payment/" . $payment_id;
        $api_verb = "PUT";

        $response = $this->call_certiTrade($api_verb, $api_resource, $data, false);

        return $response;
    }

    /**
     * Cancels an uncaptured Payment
     *
     * Cancels a Payment that has been approved for capture (state: READY_FOR_CAPUTRE) or is waiting for approval
     * for capture (state: WAITING_FOR_APPROVAL).
     *
     * @param  string $payment_id a Payment reference number
     * @return Resource|Collection|APIProblem
     */
    public function cancel_payment($payment_id) {
        $data = array("state" => "CANCELLED");

        $api_resource = "payment/" . $payment_id;
        $api_verb = "PUT";

        $response = $this->call_certiTrade($api_verb, $api_resource, $data, false);

        return $response;
    }

    /**
     * Refund a given amount on Payment
     *
     * The refunded amount can be smaller than the total amout debited in this Payment.
     *
     * @param  string $payment_id a Payment reference number
     * @param  string $amount amount to be withdrawn from the card in the currency's lowest unit.
     * @return Resource|Collection|APIProblem
     */
    public function refund_payment($payment_id, $amount) {
        $data = array("amount" => $amount);

        $api_resource = "payment/" . $payment_id . "/refund";
        $api_verb = "POST";

        $response = $this->call_certiTrade($api_verb, $api_resource, $data, false);

        return $response;
    }

    /**
     * Cancels an uncaptured Refund
     *
     * Cancels a Refund that has been approved for capture (state: READY_FOR_CAPUTRE) or is waiting for approval
     * for capture (state: WAITING_FOR_APPROVAL).
     *
     * @param  string $refund_id a Refund reference number
     * @return Resource|Collection|APIProblem
     */
    public function cancel_refund($payment_id, $refund_id) {
        $data = array("state" => "CANCELLED");

        $api_resource = "payment/" . $payment_id . "/refund/" . $refund_id;
        $api_verb = "PUT";

        $response = $this->call_certiTrade($api_verb, $api_resource, $data, false);

        return $response;
    }

    /////
    // Get:ers
    /////

    /**
     * Get information for the current Merchant.
     *
     * @return Resource|Collection|APIProblem
     */
    public function get_merchant() {
        $api_resource = "merchant/" . $this->_merchant_id;
        $api_verb = "GET";

        $response = $this->call_certiTrade($api_verb, $api_resource, '', false);

        return $response;
    }

    /**
     * Get information about a Payment.
     *
     * @param  string $payment_id a Payment reference number.
     * @return Resource|Collection|APIProblem
     */
    public function get_payment($payment_id) {
        $api_resource = "payment/" . $payment_id;
        $api_verb = "GET";

        $response = $this->call_certiTrade($api_verb, $api_resource, '', false);

        return $response;
    }

    /**
     * Get all Payments matching query.
     *
     * Query arguments can be merchant, state, amount, currency,
     * reference, payment_account and method.
     *
     * @param  array $api_query (for example array('currency' => 'SEK'))
     * @return Resource|Collection|APIProblem
     */
    public function get_payment_list($api_query) {
        $api_resource = "payment";
        if (!empty($api_query)) {
            $api_resource .= '?';

            $numQueries = count($api_query);
            $index = 0;
            foreach ($api_query as $key => $val) {
                $api_resource .= "$key=$val";
                $index++;

                if ($index < $numQueries) {
                    $api_resource .= "&";
                }
            }
        }
        $api_verb = "GET";

        $response = $this->call_certiTrade($api_verb, $api_resource, '', true, 'payments');

        return $response;
    }

    /**
     * Get a Refund for a Payment
     *
     * @param  string $payment_id a Payment reference number.
     * @param  string $refund_id a Refund reference number.
     * @return Resource|Collection|APIProblem
     */
    public function get_refund($payment_id, $refund_id) {
        $api_resource = "payment/" . $payment_id . "/refund/" .$refund_id;
        $api_verb = "GET";

        $response = $this->call_certiTrade($api_verb, $api_resource, '', false);

        return $response;
    }

    /**
     * Get all Refunds for a Payment
     *
     * @param  string $payment_id a Payment reference number.
     * @return Resource|Collection|APIProblem
     */
    public function get_refunds($payment_id) {
        $api_resource = "payment/" . $payment_id . "/refund";
        $api_verb = "GET";

        $response = $this->call_certiTrade($api_verb, $api_resource, '', true, 'refunds');

        return $response;
    }

    /////
    // Private functions
    /////

    /**
     * Assembles the API request, fires it at the webservice, assembles and returns the answer.
     * @internal
     * @param  string $api_verb REST verb to use (POST, PUT etc.).
     * @param  string $api_resource Merchant API resource (payment, payment_account etc.).
     * @param  string $data OPTIONAL Request data, if applicable.
     * @param  bool $isCollection OPTIONAL if target url is a resource or collection
     * @param  string $collectionName OPTIONAL
     * @return Resource|Collection|APIProblem
     */
    private function call_certiTrade($api_verb, $api_resource, $data = "", $isCollection = false, $collectionName = null) {
        // Create a Guzzle Client
        $client = new Client($this->_base_url);

        // Get the date in "Mon, 01 Jan 2014 13:30:00 GMT" format
        $date = gmdate("D, d M Y H:i:s")." GMT";

        // If we have a data payload encode it to JSON and add the proper HTTP header.
        if (!empty($data)) {
            $data_encoded = utf8_encode(json_encode($data, JSON_PRETTY_PRINT + JSON_UNESCAPED_SLASHES));
            $headers['Content-Type'] = "application/json; charset=UTF-8";
        }

        // Add our API resource to our url
        $resource_url = $this->_resource_url."/".$api_resource;

        // Calculate the authorization header
        $authorization_header = $this->calculate_authorization_hash($data_encoded, $api_verb, $resource_url, $date);

        // Add the Authorization HTTP header and Date header.
        $headers['Authorization'] = $authorization_header;
        $headers['Date'] = $date;

        // Use the proper REST verb for the request.
        if     ($api_verb == "GET") {     $request = $client->get($resource_url, $headers); }
        elseif ($api_verb == "POST") {    $request = $client->post($resource_url, $headers); }
        elseif ($api_verb == "PUT") {     $request = $client->put($resource_url, $headers); }
        elseif ($api_verb == "DELETE") {  $request = $client->delete($resource_url, $headers); }
        elseif ($api_verb == "OPTIONS") { $request = $client->options($resource_url, $headers); }

        // Add our data payload to the request.
        if(!empty($data_encoded)) {
            $request->setBody($data_encoded);
        }

        //////
        // SSL Setup and Configuration
        //
        // Here we force cURL (Guzzle uses cURL) to verify the ssl certificate that the CertiTrade server presents to us.
        // Just accepting the certificate the server presents to us is not enough, it must also be verified.
        //
        // Force SSL version because automagic handshake fails spectacularily
        $request->getCurlOptions()->set(CURLOPT_SSLVERSION, 3);
        // Verify that the name provided by the certificate matches the hostname
        $request->getCurlOptions()->set(CURLOPT_SSL_VERIFYHOST, 2);
        // Verify that the certificate presented by the server has a valid certificate chain
        $request->getCurlOptions()->set(CURLOPT_SSL_VERIFYPEER, true); // Setting this to false will allow Man-in-the-Middle attacks. DON'T!
        // OPTIONAL: If your PHP installation can't find the proper certificates for the chain
        //           you can add the certs to a file and load it like this:
        //if (!is_file(FILE_WITH_CERTS)) throw new Exception("Cannot load ".FILE_WITH_CERTS.". No such file.");
        //$request->getCurlOptions()->set(CURLOPT_CAINFO, CACERT_BUNDLE);

        try {
            $ct_response = $request->send();
            $representation = json_decode($ct_response->getBody(true));

            // collect all 'atomic' (not links/embedded) data
            $atomics = new \stdClass();
            foreach ($representation as $key => $item) {
                if ($key != '_links' && $key != '_embedded') {
                    $atomics->$key = $item;
                }
            }

            if ($isCollection) {
                return new Collection($atomics, $representation->_links, $representation->_embedded, $collectionName);
            }
            else {
                return new Resource($atomics, $representation->_links, $representation->_embedded);
            }
        }
        catch (\Guzzle\Http\Exception\ClientErrorResponseException $e) { // 4xx
            $ct_response = $e->getResponse();
            $representation = json_decode($ct_response->getBody(true));

            return new APIProblem($representation->httpStatus, $representation->title, $representation->detail, $representation->describedBy);
        }
        catch (\Guzzle\Http\Exception\ServerErrorResponseException $e) { // 5xx
            $success = false;
            $http_code = 0;
            $http_message = "Could not connect to CTServer";
            $http_body = "ERROR: Could not connect to CTServer";
        }
        catch (\Guzzle\Http\Exception\CurlException $e) {  // If we get a network error
            $success = false;
            $http_code = 0;
            $http_message = "Could not connect to CTServer";
            $http_body = "ERROR: Could not connect to CTServer";
        }

        // Assemble our return values in a stdClass
        $return = new \stdClass();
        $return->success      = $success;
        $return->http_code    = $http_code;
        $return->http_message = $http_message;
        $return->http_body    = $http_body;

        return $return;
    }

    /**
     * Calculates the Authorization Hash
     * @internal
     * @param  string $request_data_encoded Request datablock, if applicable.
     * @param  string $rest_verb REST verb (POST, GET etc.).
     * @param  string $resource_url Merchant API resource url.
     * @param  string $date Date in "Mon, 01 Jan 2014 13:30:00 GMT" format.
     * @return string Assembled Authorization string.
     */
    private function calculate_authorization_hash($request_data_encoded, $rest_verb, $resource_url, $date) {
        // Assemble the string to hash
        $hash_data = $rest_verb.
                     $this->_base_url.$resource_url.
                     $date.
                     $request_data_encoded;

        // Hash it with sha256 and the API key
        $authorization_hash = hash_hmac('sha256', $hash_data, $this->_api_key);

        // Assemble the complete Authorization header and return it
        $authorization_header = "CertiTrade m".$this->_merchant_id.":".$authorization_hash;

        return $authorization_header;
    }
}
