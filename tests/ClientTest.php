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

use phpmock\phpunit\PHPMock;

/**
 * Util test case without real API.
 *
 * @package Qiwi\Api\BillPaymentsTest
 */
class ClientTest extends TestCase
{
    use PHPMock;

    /**
     * CURL test instance.
     *
     * @var false|resource
     */
    protected $curl = false;


    /**
     * Setup CURL functions mock.
     *
     * @param array        $options Expected CURL options.
     * @param false|string $result  Result to CURL execute.
     *
     * @return void
     */
    protected function setMockResponse(array $options, $result)
    {
        $this->curl = curl_init();
        $this->getFunctionMock('Raiffeisen\Ecom', 'curl_copy_handle')->expects($this->once())->willReturnCallback(
            function ($curl) use ($options) {
                $this->assertEquals($this->client->curl, $curl, 'Copy original CURL handler');
                return $this->curl;
            }
        );
        $this->getFunctionMock('Raiffeisen\Ecom', 'curl_setopt_array')->expects($this->once())->willReturnCallback(
            function ($curl, $argument) use ($options) {
                $this->assertEquals($this->curl, $curl, 'Use copy of original CURL handler');
                $this->assertArraySubset($options, $argument, 'Receive CURL options set');
            }
        );
        $this->getFunctionMock('Raiffeisen\Ecom', 'curl_exec')->expects($this->once())->willReturnCallback(
            function ($curl) use ($result) {
                $this->assertEquals($this->curl, $curl, 'Use copy of original CURL handler');
                return $result;
            }
        );

    }//end setMockResponse()


    /**
     * Setup CURL functions mock witch error result.
     *
     * @param array  $options Expected CURL options.
     * @param string $error   Error message.
     *
     * @return void
     */
    protected function setMockError(array $options, $error)
    {
        $this->setMockResponse($options, false);
        $info = [
            CURLINFO_RESPONSE_CODE => 500,
            CURLOPT_HTTPHEADER     => [],
        ];
        $this->getFunctionMock('Raiffeisen\Ecom', 'curl_error')->expects($this->once())->willReturnCallback(
            function ($curl) use ($error) {
                $this->assertEquals($this->curl, $curl, 'Use copy of original CURL handler');
                return $error;
            }
        );
        $this->getFunctionMock('Raiffeisen\Ecom', 'curl_getinfo')->expects($this->atLeastOnce())->willReturnCallback(
            function ($curl, $name) use ($info) {
                $this->assertEquals($this->curl, $curl, 'Use copy of original CURL handler');
                $this->assertArrayHasKey($name, $info, 'Get CURL info param');
                return $info[$name];
            }
        );

    }//end setMockError()


    /**
     * Request exception.
     *
     * @return void
     *
     * @throws ClientException
     */
    public function testRequestException()
    {
        $this->setMockError(
            [
                CURLOPT_URL           => Client::PAYMENTS_API_URI.'/orders/test_order_ID',
                CURLOPT_CUSTOMREQUEST => Client::GET,
            ],
            'test CURL error'
        );
        $this->setException(ClientException::class, 'test CURL error', 500);
        $this->client->getOrder('test_order_ID');

    }//end testRequestException()


    /**
     * CheckEventSignature.
     *
     * @see https://developer.qiwi.com/ru/bill-payments/#notification
     *
     * @return void
     */
    public function testCheckEventSignature()
    {
        $notificationData = [
            'transaction' => [
                'amount'  => 1,
                'orderId' => 'test_transaction_order_id',
                'status'  => [
                    'value' => 'test_transaction_status_value',
                    'date'  => 'test_transaction_status_date',
                ],
            ],
        ];
        $this->assertFalse(
            $this->client->checkEventSignature(
                'foo',
                $notificationData
            ),
            'should return false on wrong signature'
        );

        $this->assertTrue(
            $this->client->checkEventSignature(
                'a682b6908e2cb28098ec1eef2eb1b54ac5b0ffd06a9f78d0934f2ee3e167fb1d',
                $notificationData
            ),
            'should return true on valid signature'
        );

    }//end testCheckEventSignature()


    /**
     * Get pay url.
     *
     * @return void
     */
    public function testGetPayUrl()
    {
        $payUrl = $this->client->getPayUrl(1, 'test_order_id', ['test_param_key' => 'test_param_value']);
        $this->assertEquals(
            'https://e-commerce.raiffeisen.ru/pay/?publicId=test_public_id&amount=1&orderId=test_order_id&test_param_key=test_param_value',
            $payUrl,
            'witch success URL'
        );

    }//end testGetPayUrl()


}//end class
