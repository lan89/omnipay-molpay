<?php

namespace Omnipay\MOLPay;

use Omnipay\Common\CreditCard;
use Omnipay\Tests\GatewayTestCase;

class GatewayTest extends GatewayTestCase
{
    /**
     * @var \Omnipay\MOLPay\Gateway
     */
    protected $gateway;

    public function setUp()
    {
        parent::setUp();

        $this->gateway = new Gateway($this->getHttpClient(), $this->getHttpRequest());

        $this->gateway->setCurrency('MYR');
        $this->gateway->setLocale('en');
        $this->gateway->setMerchantId('test1234');
        $this->gateway->setVerifyKey('abcdefg');

        $this->options = array(
            'amount' => '10.00',
            'card' => new CreditCard(array(
                'country' => 'MY',
                'email' => 'ahlee2326@me.com',
                'name' => 'Lee Siong Chan',
                'phone' => '0123456789',
            )),
            'description' => 'Test Payment',
            'transactionId' => '20160331082207680000',
            'paymentMethod' => 'credit',
        );
    }

    public function testPurchase()
    {
        $response = $this->gateway->purchase($this->options)->send();

        $this->assertFalse($response->isSuccessful());
        $this->assertTrue($response->isRedirect());
        $this->assertNull($response->getTransactionReference());
        $this->assertEquals(
            'https://www.onlinepayment.com.my/MOLPay/pay/test1234/?amount=10.00&bill_desc=Test+Payment&bill_email=ahlee2326%40me.com&bill_mobile=0123456789&bill_name=Lee+Siong+Chan&channel=credit&country=MY&currency=MYR&langcode=en&orderid=20160331082207680000&vcode=f3d5496b444ae3d11e09fa92a753ac60',
            $response->getRedirectUrl()
        );
    }

    public function testCompletePurchaseSuccess()
    {
        $this->getHttpRequest()->request->replace(array(
            'appcode' => 'abcdefg',
            'domain' => 'test4321',
            'paydate' => '2016-03-29 04:02:21',
            'skey' => '0c1e98d6d58f9a30b919bcaeb9790852',
            'status' => '00',
            'tranID' => '000001',
        ));

        $response = $this->gateway->completePurchase($this->options)->send();

        $this->assertTrue($response->isSuccessful());
        $this->assertFalse($response->isRedirect());
    }

    /**
     * @expectedException \Omnipay\Common\Exception\InvalidResponseException
     */
    public function testCompletePurchaseInvalidSKey()
    {
        $this->getHttpRequest()->request->replace(array(
            'appcode' => 'abcdefg',
            'domain' => 'test4321',
            'paydate' => '2016-03-29 04:02:21',
            'skey' => 'I_AM_INVALID_SKEY',
            'status' => '11',
            'tranID' => '000001',
        ));

        $response = $this->gateway->completePurchase($this->options)->send();
    }

    /**
     * @expectedException \Omnipay\Common\Exception\InvalidResponseException
     */
    public function testCompletePurchaseError()
    {
        $this->getHttpRequest()->request->replace(array(
            'appcode' => 'abcdefg',
            'domain' => 'test4321',
            'paydate' => 'I am not a date',
            'skey' => 'ef0903d1906d0968605155f85ec9fcd5',
            'status' => '11',
            'error_desc' => 'Invalid date',
            'tranID' => '000001',
        ));

        $response = $this->gateway->completePurchase($this->options)->send();
        $this->assertFalse($response->isSuccessful());
        $this->assertFalse($response->isRedirect());
        $this->assertNull($response->getTransactionReference());
        $this->assertEquals('Invalid date', $response->getMessage());
    }
}