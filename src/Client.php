<?php
/**
 * Ecommerce payment API SDK
 *
 * @package   Raiffeisen\Ecom
 * @author    Yaroslav <yaroslav@wannabe.pro>
 * @copyright 2022 (c) Raiffeisenbank JSC
 * @license   MIT https://raw.githubusercontent.com/Raiffeisen-DGTL/ecom-sdk-php/master/LICENSE
 */

namespace Raiffeisen\Ecom;

use Exception;
use DateTime;

if (false === defined('CLIENT_NAME')) {
    //phpcs:disable Squiz.Commenting -- Because contingent constant definition.
    /**
     * The client name fingerprint.
     *
     * @const string
     */
    define('CLIENT_NAME', 'php_sdk');
    //phpcs:enable Squiz.Commenting
}

if (false === defined('CLIENT_VERSION')) {
    //phpcs:disable Squiz.Commenting -- Because contingent constant definition.
    /**
     * The client version fingerprint.
     *
     * @const string
     */
    define(
        'CLIENT_VERSION',
        @json_decode(
            file_get_contents(dirname(__DIR__).DIRECTORY_SEPARATOR.'composer.json'),
            true
        )['version']
    );
    //phpcs:enable Squiz.Commenting
}

/**
 * Client for ecommerce payment API.
 *
 * @see https://pay.raif.ru/doc/ecom.html API Documentation.
 *
 * @property string   $secretKey  The secret key, is set-only.
 * @property string   $publicId   The public ID, is full access.
 * @property string   $host       The API URL host.
 * @property resource $curl       The request, is get-only.
 */
class Client
{
    /**
     * The default separator.
     *
     * @const string
     */
    const VALUE_SEPARATOR = '|';

    /**
     * The default hash algorithm.
     *
     * @const string
     */
    const DEFAULT_ALGORITHM = 'sha256';

    /**
     * The API datetime format.
     *
     * @const string
     */
    const DATETIME_FORMAT = 'Y-m-d\TH:i:sP';

    /**
     * The income check type.
     *
     * @const string
     */
    const RECEIPT_TYPE_SELL = 'sell';

    /**
     * The refund check type.
     *
     * @const string
     */
    const RECEIPT_TYPE_REFUND = 'refund';

    /**
     * The API get method.
     *
     * @const string
     */
    const GET = 'GET';

    /**
     * The API post method.
     *
     * @const string
     */
    const POST = 'POST';

    /**
     * The API delete method.
     *
     * @const string
     */
    const DELETE = 'DELETE';

    /**
     * The production API host.
     *
     * @const string
     */
    const HOST_PROD = 'https://e-commerce.raiffeisen.ru';

    /**
     * The test API host.
     *
     * @const string
     */
    const HOST_TEST = 'https://test.ecom.raiffeisen.ru';

    /**
     * The default URL to payment form.
     *
     * @const string
     */
    const PAYMENT_FORM_URI = '/pay';

    /**
     * The default base URL to payment API.
     *
     * @const string
     */
    const PAYMENT_API_URI = '/api/payment/v1';

    /**
     * The default base URL to payments API.
     *
     * @const string
     */
    const PAYMENTS_API_URI = '/api/payments/v1';

    /**
     * The default base URL to fiscal API.
     *
     * @const string
     */
    const FISCAL_API_URI = '/api/fiscal/v1';

    /**
     * The default base URL to settings API.
     *
     * @const string
     */
    const SETTINGS_API_URI = '/api/settings/v1';

    /**
     * The secret key.
     *
     * @var string
     */
    protected $secretKey;

    /**
     * The public identifier.
     *
     * @var string
     */
    protected $publicId;

    /**
     * The API host.
     *
     * @var string
     */
    protected $host;

    /**
     * The request.
     *
     * @var resource
     */
    protected $internalCurl;


    /**
     * Client constructor.
     *
     * @param string $secretKey The secret key.
     * @param string $publicId  The public identifier.
     * @param array  $options   The dictionary of request options.
     */
    public function __construct($secretKey, $publicId, $host = self::HOST_PROD, array $options=[])
    {
        $this->secretKey    = (string) $secretKey;
        $this->publicId     = (string) $publicId;
        $this->host         = (string) $host;
        $this->internalCurl = curl_init();
        curl_setopt_array(
            $this->internalCurl,
            ([
                CURLOPT_USERAGENT => CLIENT_NAME.'-'.CLIENT_VERSION,
            ] + $options)
        );

    }//end __construct()


    /**
     * Setter.
     *
     * @param string $name  The property name.
     * @param mixed  $value The property value.
     *
     * @return void
     *
     * @throws Exception Throw on unexpected property set.
     */
    public function __set($name, $value)
    {
        switch ($name) {
        case 'secretKey':
            $this->secretKey = (string) $value;
            break;
        case 'publicId':
            $this->publicId = (string) $value;
            break;
        case 'host':
            $this->host = (string) $value;
            break;
        case 'curl':
            throw new Exception('Not acceptable property '.$name.'.');
        default:
            throw new Exception('Undefined property '.$name.'.');
        }

    }//end __set()


    /**
     * Getter.
     *
     * @param string $name The property name.
     *
     * @return mixed The property value.
     *
     * @throws Exception Throw on unexpected property get.
     */
    public function __get($name)
    {
        switch ($name) {
        case 'secretKey':
            throw new Exception('Not acceptable property '.$name.'.');
        case 'publicId':
            return $this->publicId;
        case 'host':
            return $this->host;
        case 'curl':
            return $this->internalCurl;
        default:
            throw new Exception('Undefined property '.$name.'.');
        }

    }//end __get()


    /**
     * Checker.
     *
     * @param string $name The property name.
     *
     * @return bool Property set or not.
     *
     * @throws Exception Throw on unexpected property check.
     */
    public function __isset($name)
    {
        switch ($name) {
        case 'secretKey':
            return !empty($this->secretKey);
        case 'publicId':
            return !empty($this->publicId);
        case 'host':
            return !empty($this->host);
        case 'curl':
            return !empty($this->internalCurl);
        default:
            return false;
        }

    }//end __isset()


    /**
     * Checks payment notification event signature.
     *
     * @param string       $signature The signature.
     * @param object|array $eventBody The event data body.
     *
     * @return bool Signature is valid or not.
     */
    public function checkEventSignature($signature, array $eventBody)
    {
        // Preset required fields.
        $eventBody = array_replace_recursive(
            [
                'event'       => 'payment',
                'transaction' => [
                    'amount'  => null,
                    'orderId' => null,
                    'status'  => [
                        'value' => null,
                        'date'  => null,
                    ],
                ],
            ],
            $eventBody
        );

        $processedEventData = [
            'amount'                   => $eventBody['transaction']['amount'],
            'publicId'                 => $this->publicId,
            'order'                    => $eventBody['transaction']['orderId'],
            'transaction.status.value' => $eventBody['transaction']['status']['value'],
            'transaction.status.date'  => $eventBody['transaction']['status']['date'],
        ];
        ksort($processedEventData);
        $processedNotificationDataKeys = join(self::VALUE_SEPARATOR, $processedEventData);
        $hash = hash_hmac(self::DEFAULT_ALGORITHM, $processedNotificationDataKeys, $this->secretKey);

        return $hash === $signature;

    }//end checkEventSignature()


    /**
     * Set callback URL.
     *
     * @param string $callbackUrl The callback URL.
     * @param string $baseUrl     The base settings url.
     *
     * @return array Return result.
     *
     * @throws ClientException
     */
    public function postCallbackUrl($callbackUrl, $baseUrl=self::SETTINGS_API_URI)
    {
        return $this->requestBuilder($baseUrl.'/callback', self::POST, [ 'callbackUrl' => $callbackUrl ]);

    }//end postCallbackUrl()


    /**
     * Get pay URL witch success URL param.
     *
     * @param numeric $amount  The order data.
     * @param string  $orderId The order identifier.
     * @param array   $query   The additional query params.
     * @param string  $baseUrl The base payment form url.
     *
     * @return string
     */
    public function getPayUrl($amount, $orderId, array $query, $baseUrl=self::PAYMENT_FORM_URI)
    {
        // Preset required fields.
        $query = array_replace(
            [
                'publicId' => $this->publicId,
                'amount'   => $amount,
                'orderId'  => $orderId,
            ],
            $query
        );

        return $this->host.$baseUrl.'?'.http_build_query($query, '', '&', PHP_QUERY_RFC3986);

    }//end getPayUrl()

    /**
     * Post payment form witch success URL param.
     *
     * @param numeric $amount  The order data.
     * @param string  $orderId The order identifier.
     * @param array   $query   The additional query params.
     * @param string  $baseUrl The base payment form url.
     *
     * @return array Return result.
     *
     * @throws ClientException
     */
    public function postPayUrl($amount, $orderId, array $query, $baseUrl=self::PAYMENT_FORM_URI)
    {
        // Preset required fields.
        $body = array_replace(
            [
                'publicId' => $this->publicId,
                'amount'   => $amount,
                'orderId'  => $orderId,
            ],
            $query
        );

        return $this->requestBuilder($baseUrl, self::POST, $body, true);

    }//end postPayUrl()


    /**
     * Getting information about the status of a transaction.
     *
     * @param string $orderId The order identifier.
     * @param string $baseUrl The base settings url.
     *
     * @return array Return result.
     *
     * @throws ClientException Throw on API return invalid response.
     */
    public function getOrderTransaction($orderId, $baseUrl=self::PAYMENTS_API_URI)
    {
        $url = $baseUrl.'/orders/'.$orderId.'/transaction';

        return $this->requestBuilder($url);

    }//end getOrderTransaction()


    /**
     * Processing a refund.
     *
     * @param string $orderId  The order identifier.
     * @param string $refundId The refund identifier.
     * @param string $amount   The refund amount.
     * @param string $baseUrl  The base settings url.
     *
     * @return array Return result.
     *
     * @throws ClientException Throw on API return invalid response.
     */
    public function postOrderRefund($orderId, $refundId, $amount, $baseUrl=self::PAYMENTS_API_URI)
    {
        $url = $baseUrl.'/orders/'.$orderId.'/refunds/'.$refundId;

        return $this->requestBuilder($url, self::POST, [ 'amount' => $amount ]);

    }//end postOrderRefund()


    /**
     * Getting refund status.
     *
     * @param string $orderId  The order identifier.
     * @param string $refundId The refund identifier.
     * @param string $baseUrl  The base settings url.
     *
     * @return array Return result.
     *
     * @throws ClientException Throw on API return invalid response.
     */
    public function getOrderRefund($orderId, $refundId, $baseUrl=self::PAYMENTS_API_URI)
    {
        $url = $baseUrl.'/orders/'.$orderId.'/refunds/'.$refundId;

        return $this->requestBuilder($url);

    }//end getOrderRefund()


    /**
     * Getting order information.
     *
     * @param string $orderId The order identifier.
     * @param string $baseUrl The base settings url.
     *
     * @return array Return result.
     *
     * @throws ClientException Throw on API return invalid response.
     */
    public function getOrder($orderId, $baseUrl=self::PAYMENT_API_URI)
    {
        $url = $baseUrl.'/orders/'.$orderId;

        return $this->requestBuilder($url);

    }//end getOrder()


    /**
     * Delete order.
     *
     * @param string $orderId The order identifier.
     * @param string $baseUrl The base settings url.
     *
     * @return array Return result.
     *
     * @throws ClientException Throw on API return invalid response.
     */
    public function deleteOrder($orderId, $baseUrl=self::PAYMENT_API_URI)
    {
        $url = $baseUrl.'/orders/'.$orderId;

        return $this->requestBuilder($url, self::DELETE);

    }//end deleteOrder()


    /**
     * Getting a list of checks.
     *
     * @param string      $orderId     The order identifier.
     * @param string|null $receiptType The base settings url.
     * @param string      $baseUrl     The base settings url.
     *
     * @return array Return result.
     *
     * @throws ClientException Throw on API return invalid response.
     */
    public function getOrderReceipts($orderId, $receiptType=null, $baseUrl=self::FISCAL_API_URI)
    {
        $url = $baseUrl.'/orders/'.$orderId.'/receipts';
        if (true !== empty($receiptType)) {
            $query = ['receiptType' => $receiptType];
            $url   = $url.'?'.http_build_query($query, '', '&', PHP_QUERY_RFC3986);
        }

        return $this->requestBuilder($url);

    }//end getOrderReceipts()


    /**
     * Getting a refund check.
     *
     * @param string $orderId  The order identifier.
     * @param string $refundId The refund identifier.
     * @param string $baseUrl  The base settings url.
     *
     * @return array Return result.
     *
     * @throws ClientException Throw on API return invalid response.
     */
    public function getOrderRefundReceipt($orderId, $refundId, $baseUrl=self::FISCAL_API_URI)
    {
        $url = $baseUrl.'/orders/'.$orderId.'/refunds/'.$refundId.'/receipt';

        return $this->requestBuilder($url);

    }//end getOrderRefundReceipt()


    /**
     * Build request.
     *
     * @param string $url    The url.
     * @param string $method The method.
     * @param array  $body   The body.
     * @param array  $raw    The response raw return flag.
     *
     * @return bool|array Return response.
     *
     * @throws Exception Throw on unsupported $method use.
     * @throws ClientException Throw on API return invalid response.
     */
    protected function requestBuilder($url, $method=self::GET, array $body=[], $raw = false)
    {
        $curl    = curl_copy_handle($this->internalCurl);
        $headers = [
            'Accept: application/json',
            'Authorization: Bearer '.$this->secretKey,
        ];
        if (true !== empty($body) && self::GET !== $method) {
            $body    = json_encode($body, JSON_UNESCAPED_UNICODE);
            $headers = array_merge(
                $headers,
                [
                    'Content-Type: application/json;charset=UTF-8',
                    'Content-Length: '.strlen($body),
                ]
            );
        }

        curl_setopt_array(
            $curl,
            [
                CURLOPT_URL            => $this->host.$url,
                CURLOPT_HTTPHEADER     => $headers,
                CURLOPT_CUSTOMREQUEST  => $method,
                CURLOPT_POSTFIELDS     => $body,
                CURLOPT_RETURNTRANSFER => 1,
            ]
        );
        $response = curl_exec($curl);

        if (false === $response) {
            throw new ClientException($curl, curl_error($curl), curl_getinfo($curl, CURLINFO_RESPONSE_CODE));
        }

        if ($raw) {
            return $response;
        }

        if (false === empty($response)) {
            $json = json_decode($response, true);
            if (null === $json) {
                throw new ClientException($curl, json_last_error_msg(), json_last_error());
            }

            if (true === isset($json['errorCode'])) {
                if (true === isset($json['description'])) {
                    throw new ClientException($curl, $json['description']);
                }

                throw new ClientException($curl, $json['errorCode']);
            }

            return $json;
        }

        return true;

    }//end requestBuilder()


}//end class
