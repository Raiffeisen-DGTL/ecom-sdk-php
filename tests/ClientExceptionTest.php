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

/**
 * Util test case without real API.
 *
 * @package Qiwi\Api\BillPaymentsTest
 */
class ClientExceptionTest extends TestCase
{


    /**
     * Properties available by magic methods.
     *
     * @return void
     *
     * @throws \ErrorException
     */
    public function testProperties()
    {
        $curl = curl_init();
        $billPaymentsException = new \Raiffeisen\Ecom\ClientException($curl);

        $this->assertTrue(isset($billPaymentsException->curl), 'exists curl attribute');
        $this->assertSame($curl, $billPaymentsException->curl, 'correct set curl attribute');
        $this->setException(Exception::class, 'Not acceptable property curl.');
        $billPaymentsException->curl = curl_copy_handle($curl);

        //phpcs:disable Generic,Squiz.Commenting -- Because IDE helper doc block in line.
        /** @noinspection PhpUndefinedFieldInspection */
        $this->assertFalse(isset($billPaymentsException->qwerty), 'not exists attribute');
        //phpcs:enable Generic,Squiz.Commenting
        $this->setException(Exception::class, 'Undefined property qwerty.');
        //phpcs:disable Generic,Squiz.Commenting -- Because IDE helper doc block in line.
        /** @noinspection PhpUndefinedFieldInspection */
        $billPaymentsException->qwerty = 'test';
        //phpcs:enable Generic,Squiz.Commenting

    }//end testProperties()


}//end class
