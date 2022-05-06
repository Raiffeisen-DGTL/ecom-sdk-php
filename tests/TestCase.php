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

use PHPUnit\Framework\TestCase as BaseTestCase;
use Exception;

if (false === defined('CLIENT_NAME')) {
    define('CLIENT_NAME', 'php_sdk');
}

if (false === defined('CLIENT_VERSION')) {
    define(
        'CLIENT_VERSION',
        @json_decode(
            file_get_contents(dirname(__DIR__).DIRECTORY_SEPARATOR.'composer.json'),
            true
        )['version']
    );
}

/**
 * Test case preset.
 */
abstract class TestCase extends BaseTestCase
{
    /**
     * The secret key.
     *
     * @const string
     */
    const SECRET_KEY = 'test_secret_key';

    /**
     * The public identifier.
     *
     * @const string
     */
    const PUBLIC_ID = 'test_public_id';

    /**
     * Tests target.
     *
     * @var Client
     */
    protected $client;


    /**
     * Set up tests.
     *
     * @return void
     *
     * @throws Exception
     */
    public function setUp()
    {
        parent::setUp();

        // Init target.
        $this->client = new Client(self::SECRET_KEY, self::PUBLIC_ID);

    }//end setUp()


    /**
     * Set expect exception.
     *
     * @param string       $class   Class name.
     * @param null|string  $message Message text.
     * @param null|integer $code    Code number.
     *
     * @return void
     */
    protected function setException($class, $message=null, $code=null)
    {
        if (null !== $code) {
            $this->expectExceptionCode($code);
        }

        if (null !== $message) {
            $this->expectExceptionMessage($message);
        }

        $this->expectException($class);

    }//end setException()


}//end class
