<?php
namespace Elavon\Tpv;

use PHPUnit_Framework_TestCase;
use Exception;

/**
 * Test Tpv
 */
class TpvTest extends PHPUnit_Framework_TestCase
{
    public function testInstance()
    {
        $config = require (realpath(__DIR__.'/../../..').'/config.php');

        $tpv = new Tpv($config);

        $this->assertInstanceOf('Elavon\Tpv\Tpv', $tpv);

        return $tpv;
    }

    public function testConstruct()
    {
        try {
            $tpv = new Tpv(array(
                'MERCHANT_ID' => 'XXXXXXXXXX',
                'ACCOUNT' => 'XXXXXXXXX',
                'KEY' => 'XXXXXXXXXXXX',
                'CURRENCY' => 'EUR',
                'AUTO_SETTLE_FLAG' => '1'
            ));
        } catch (Exception $e) {
            $this->assertContains('Environment', $e->getMessage());
        }

        try {
            $tpv = new Tpv(array(
                'Environment' => 'test',
                'ACCOUNT' => 'XXXXXXXXX',
                'KEY' => 'XXXXXXXXXXXX',
                'CURRENCY' => 'EUR',
                'AUTO_SETTLE_FLAG' => '1'
            ));
        } catch (Exception $e) {
            $this->assertContains('MERCHANT_ID', $e->getMessage());
        }

        try {
            $tpv = new Tpv(array(
                'Environment' => 'test',
                'MERCHANT_ID' => 'XXXXXXXXXX',
                'KEY' => 'XXXXXXXXXXXX',
                'CURRENCY' => 'EUR',
                'AUTO_SETTLE_FLAG' => '1'
            ));
        } catch (Exception $e) {
            $this->assertContains('ACCOUNT', $e->getMessage());
        }

        try {
            $tpv = new Tpv(array(
                'Environment' => 'test',
                'MERCHANT_ID' => 'XXXXXXXXXX',
                'ACCOUNT' => 'XXXXXXXXX',
                'CURRENCY' => 'EUR',
                'AUTO_SETTLE_FLAG' => '1'
            ));
        } catch (Exception $e) {
            $this->assertContains('KEY', $e->getMessage());
        }

        try {
            $tpv = new Tpv(array(
                'Environment' => 'test',
                'MERCHANT_ID' => 'XXXXXXXXXX',
                'ACCOUNT' => 'XXXXXXXXX',
                'KEY' => 'XXXXXXXXXXXX',
                'AUTO_SETTLE_FLAG' => '1'
            ));
        } catch (Exception $e) {
            $this->assertContains('CURRENCY', $e->getMessage());
        }

        try {
            $tpv = new Tpv(array(
                'Environment' => 'test',
                'MERCHANT_ID' => 'XXXXXXXXXX',
                'ACCOUNT' => 'XXXXXXXXX',
                'KEY' => 'XXXXXXXXXXXX',
                'CURRENCY' => 'EUR',
            ));
        } catch (Exception $e) {
            $this->assertContains('AUTO_SETTLE_FLAG', $e->getMessage());
        }
    }

    /**
     * @depends testInstance
     */
    public function testAmounts($tpv)
    {
        $this->assertEquals('000', $tpv->getAmount(0));
        $this->assertEquals('000', $tpv->getAmount(null));
        $this->assertEquals('400', $tpv->getAmount(4));
        $this->assertEquals('410', $tpv->getAmount(4.1));
        $this->assertEquals('410', $tpv->getAmount(4.10));
        $this->assertEquals('410', $tpv->getAmount(4.100));
        $this->assertEquals('410', $tpv->getAmount('4,10'));
        $this->assertEquals('410', $tpv->getAmount('4.10'));
        $this->assertEquals('410', $tpv->getAmount('4.1'));
        $this->assertEquals('410', $tpv->getAmount('4,1'));
        $this->assertEquals('040', $tpv->getAmount(0.4));
        $this->assertEquals('004', $tpv->getAmount(0.04));
        $this->assertEquals('000', $tpv->getAmount(0.004));
        $this->assertEquals('001', $tpv->getAmount(0.006));
        $this->assertEquals('400', $tpv->getAmount('4€'));
        $this->assertEquals('100050', $tpv->getAmount('1.000,50'));
    }

    /**
     * @depends testInstance
     */
    public function testFormFields($tpv)
    {
        try {
            $tpv->setFormHiddens([
                'ORDER_ID' => '10',
            ]);
        } catch (Exception $e) {
            $this->assertContains('AMOUNT', $e->getMessage());
        }

        try {
            $tpv->setFormHiddens([
                'AMOUNT' => '1,1',
            ]);
        } catch (Exception $e) {
            $this->assertContains('ORDER_ID', $e->getMessage());
        }

        try {
            $tpv->setFormHiddens([
                'ORDER_ID' => '0',
                'AMOUNT' => '1,1',
            ]);
        } catch (Exception $e) {
            $this->assertContains('ORDER_ID', $e->getMessage());
        }

        try {
            $tpv->setFormHiddens([
                'ORDER_ID' => '10',
                'AMOUNT' => '0',
            ]);
        } catch (Exception $e) {
            $this->assertContains('AMOUNT', $e->getMessage());
        }

        $tpv->setFormHiddens([
            'ORDER_ID' => '10',
            'AMOUNT' => '10',
        ]);

        $fields = $tpv->getFormHiddens();

        $this->assertContains('<input', $fields);
        $this->assertContains('MERCHANT_ID', $fields);
        $this->assertContains('ORDER_ID', $fields);
        $this->assertContains('ACCOUNT', $fields);
        $this->assertContains('CURRENCY', $fields);
        $this->assertContains('AMOUNT', $fields);
        $this->assertContains('TIMESTAMP', $fields);
        $this->assertContains('SHA1HASH', $fields);
        $this->assertContains('AUTO_SETTLE_FLAG', $fields);

        return $tpv;
    }
}
