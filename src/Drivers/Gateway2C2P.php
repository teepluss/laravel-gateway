<?php

namespace Teepluss\Gateway\Drivers;

use Teepluss\Gateway\GatewayException;

class Gateway2C2P extends DriverAbstract implements DriverInterface
{
    /**
     * Define Gateway name
     */
    const GATEWAY = '2C2P';

    const VERSION = '7.5';

    /**
     * Merchant ID
     *
     * @var string
     */
    private $_merchantId = null;

    /**
     * Secret Key
     *
     * @var string
     */
    private $_secretKey = null;

    private $_customerEmail = null;

    private $_expire = null;

    protected $_gatewayUrl = 'https://t.2c2p.com/RedirectV3/payment';

    protected $_sandboxGatewayUrl = 'https://demo2.2c2p.com/2C2PFrontEnd/RedirectV3/payment';

    protected $_statusReturned = array (
        'success' => array(
            '000'
        ),
        'pending' => array(
            '001'
        ),
        'failed'  => array(
            '002', '999'
        ),
        'cancel' => array(
            '003'
        )
    );

    public function setSandboxMode($val)
    {
        $this->_sandbox = $val;

        if ($val == true) {
            $this->_gatewayUrl = $this->_sandboxGatewayUrl;
        }

        return $this;
    }

    public function setDebug($val)
    {
        $this->_debug = $val;
        return $this;
    }

    public function getDebug()
    {
        return $this->_debug;
    }

    public function setMerchantId($val)
    {
        $this->_merchantId = $val;
        return $this;
    }

    public function getMerchantId()
    {
        return $this->_merchantId;
    }

    public function setSecretKey($val)
    {
        $this->_secretKey = $val;
        return $this;
    }

    public function getSecretKey()
    {
        return $this->_secretKey;
    }

    public function setPaymentDescription($val)
    {
        $this->_remark = $val;
        return $this;
    }

    public function getPaymentDescription()
    {
        return $this->_remark;
    }

    public function setCustomerEmail($val)
    {
        $this->_customerEmail = $val;
        return $this;
    }

    public function getCustomerEmail()
    {
        return $this->_customerEmail;
    }

    public function setExpire($val)
    {
        $this->_expire = $val;
        return $this;
    }

    public function getExpire()
    {
        return $this->_expire;
    }

    public function getLanguage()
    {
        return strtolower(parent::getLanguage());
    }

    private function getAmountParameter()
    {
        $amount = number_format($this->getAmount(), 2, '', '');

        return substr('000000000000' . $amount, -12);
    }

    public function build($extends = array())
    {
        return $this;
    }

    public function render($opts = array())
    {
        $input = [
            'version' => self::VERSION,
            'merchant_id' => $this->getMerchantId(),
            'currency' => $this->getCurrency(),
            'result_url_1' => $this->getSuccessUrl(),
            'result_url_2' => $this->getBackendUrl(),
            'payment_description' => $this->getPaymentDescription(),
            'order_id' => $this->getInvoice(),
            'amount' => $this->getAmountParameter(),
            'default_lang' => $this->getLanguage(),
        ];

        if (! empty($this->getCustomerEmail()) && filter_var($this->getCustomerEmail(), FILTER_VALIDATE_EMAIL)) {
            $input['customer_email'] = $this->getCustomerEmail();
        }

        if (! empty($this->getExpire())) {
            $input['payment_expiry'] = $this->getExpire();
        }

        $input['hash_value'] = $this->getHashValue();

        $form = $this->makeFormPayment($input);

        return $form;
    }

    public function getFrontendResult()
    {
        return $this->getBackendResult();
    }

    public function getBackendResult()
    {
        if (count($_POST) == 0
            or ! isset($_POST['version'])
            or ! isset($_POST['merchant_id'])
            or ! isset($_POST['order_id'])
            or ! isset($_POST['amount'])
            or ! isset($_POST['payment_status'])
            or ! isset($_POST['hash_value'])
            or $_POST['version'] != self::VERSION
            or $_POST['merchant_id'] != $this->getMerchantId()
        ) {
            // return false;
            throw new GatewayException('Data fail');
        }

        $checkHashStr = $_POST['version']
            . array_get($_POST, 'request_timestamp')
            . $_POST['merchant_id']
            . $_POST['order_id']
            . array_get($_POST, 'invoice_no')
            . array_get($_POST, 'currency')
            . $_POST['amount']
            . array_get($_POST, 'transaction_ref')
            . array_get($_POST, 'approval_code')
            . array_get($_POST, 'eci')
            . array_get($_POST, 'transaction_datetime')
            . array_get($_POST, 'payment_channel')
            . $_POST['payment_status']
            . array_get($_POST, 'channel_response_code')
            . array_get($_POST, 'channel_response_desc')
            . array_get($_POST, 'masked_pan')
            . array_get($_POST, 'stored_card_unique_id')
            . array_get($_POST, 'backend_invoice')
            . array_get($_POST, 'paid_channel')
            . array_get($_POST, 'paid_agent')
            . array_get($_POST, 'recurring_unique_id')
            . array_get($_POST, 'user_defined_1')
            . array_get($_POST, 'user_defined_2')
            . array_get($_POST, 'user_defined_3')
            . array_get($_POST, 'user_defined_4')
            . array_get($_POST, 'user_defined_5')
            . array_get($_POST, 'browser_info')
            . array_get($_POST, 'ippPeriod')
            . array_get($_POST, 'ippInterestType')
            . array_get($_POST, 'ippInterestRate')
            . array_get($_POST, 'ippMerchantAbsorbRate')
            . array_get($_POST, 'payment_scheme')
            . array_get($_POST, 'process_by');

        $checkHash = hash_hmac('sha1',$checkHashStr, $this->getSecretKey(),false);	//Compute hash value
        $hash_value = $_POST['hash_value'];

        //Validate response hash_value
        if(! strcmp(strtolower($hash_value), strtolower($checkHash)) == 0) {
            // return false;
            throw new GatewayException('Hash check = failed');
        }

        $this->setReferenceId(array_get($_POST, 'transaction_ref'));

        if (in_array(array_get($_POST, 'channel_response_code'), ['9022', '009'])) {
            $status = 'expired';
        } else {
            $status = $this->mapStatusReturned($_POST['payment_status']);
        }


        $result = array(
            'status' => true,
            'data'   => array(
                'gateway'  => self::GATEWAY,
                'status'   => $status,
                'invoice'  => $_POST['order_id'],
                'currency' => array_get($_POST, 'currency'),
                'amount'   => $_POST['amount'],
                'dump'     => json_encode($_POST)
            ),
            'custom' => array(
                'recheck' => 'no'
            )
        );

        return $result;
    }

    public function getGatewayInvoice()
    {
        // TODO: Implement getGatewayInvoice() method.
    }

    private function getHashValue()
    {
        /*
        $string = version + merchant_id + payment_description + order_id + invoice_no + currency + amount + customer_email + pay_category_id + promotion + user_defined_1 + user_defined_2 + user_defined_3 + user_defined_4 + user_defined_5 + result_url_1 + result_url_2 + enable_store_card + stored_card_unique_id + request_3ds + recurring + order_prefix + recurring_amount + allow_accumulate + max_accumulate_amount + recurring_interval + recurring_count + charge_next_date+ charge_on_date + payment_option + ipp_interest_type + payment_expiry + default_lang + statement_descriptor + use_storedcard_only + tokenize_without_authorization + product + ipp_period_filter
        */

        // Mandatory
        $string = self::VERSION;
        $string .= $this->getMerchantId();
        $string .= $this->getPaymentDescription();
        $string .= $this->getInvoice();
        $string .= $this->getCurrency();
        $string .= $this->getAmountParameter();

        // Not Mandatory
        $string .= $this->getCustomerEmail();
        $string .= $this->getSuccessUrl();
        $string .= $this->getBackendUrl();
        $string .= $this->getExpire();
        $string .= $this->getLanguage();

        $hashValue = hash_hmac('sha1', $string, $this->getSecretKey(),false);
        $hashValue = strtoupper($hashValue);

        return $hashValue;
    }
}
