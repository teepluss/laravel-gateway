<?php namespace Teepluss\Gateway\Drivers;

use Teepluss\Gateway\GatewayException;

class TrueMoneyApi extends DriverAbstract implements DriverInterface {

    const GATEWAY = 'TrueMoney';

    private $_appId;

    private $_shopCode;

    private $_secret;

    private $_bearer;

    protected $_gatewayUrl = 'https://api.truemoney.com/payments/v1/payment';

    protected $_gatewayCreatePaymentUrl = 'https://api.truemoney.com/payments/v1/payment';

    protected $_gatewayEnquiryUrl = 'https://api.truemoney.com/payments/v1/payment';

    /**
     * Construct the adapter
     */
    public function __construct($params = array())
    {
        parent::__construct($params);
    }

    public function setAppId($val)
    {
        $this->_appId = $val;

        return $this;
    }

    public function getAppId()
    {
        return $this->_appId;
    }

    public function setShopCode($val)
    {
        $this->_shopCode = $val;

        return $this;
    }

    public function getShopCode()
    {
        return $this->_shopCode;
    }

    public function setSecret($val)
    {
        $this->_secret = $val;

        return $this;
    }

    public function getSecret()
    {
        return $this->_secret;
    }

    public function setBearer($val)
    {
        $this->_bearer = $val;

        return $this;
    }

    public function getBearer()
    {
        return $this->_bearer;
    }

    /**
     * Enable sandbox API
     */
    public function setSandboxMode($val)
    {
        $this->_sandbox = $val;

        if ($val == true)
        {
            $patterns = array(
                '|api\.|' => "dev.",
                '|\.com|' => ".co.th"
            );

            $this->_gatewayUrl = preg_replace(array_keys($patterns), array_values($patterns), $this->_gatewayUrl);
            $this->_gatewayEnquiryUrl = preg_replace(array_keys($patterns), array_values($patterns), $this->_gatewayEnquiryUrl);
            $this->_gatewayCreatePaymentUrl = preg_replace(array_keys($patterns), array_values($patterns), $this->_gatewayCreatePaymentUrl);
        }

        return $this;
    }

    /**
     * Get the status sandbox mode
     */
    public function getSandboxMode()
    {
        return $this->_sandbox;
    }

    /**
     * Set account for merchant.
     *
     * @param object
     */
    public function setMerchantAccount($val)
    {
        if (is_array($val))
        {
            return parent::setMerchantAccount($val);
        }

        // Explode from string.
        list($appId, $shopCode, $secret, $bearer) = explode(':', $val);

        $this->setAppId($appId);

        $this->setShopCode($shopCode);

        $this->setSecret($secret);

        $this->setBearer($bearer);

        return $this;
    }

    /**
     * Transform payment fields and build to array
     */
    public function build($extends = array())
    {
        $defaults = array(
            'app_id' => $this->_appId,
            'intent' => $this->_purpose,
            'request_id' => $this->_invoice,
            'locale' => null, //$this->_language,
            'payer' => array(
                'funding_instrument' => null,
                'installment' => null,
                'payer_info' => array(
                    'email' => 'user@email.com',
                    'firstname' => 'FirstName',
                    'lastname' => 'LastName',
                    'payer_id' => 'userlogin',
                    'phone' => 'payer_phone',
                ),
                'payment_method' => 'creditcard',
                'payment_processor' => 'CYBS-BAY',
            ),
            'payment_type' => 'redirect',
            'redirect_urls' => array(
                'return_url' => $this->_successUrl,
                'cancel_url' => $this->_cancelUrl
            ),
            'billing_address' => array (
                'city_district' => 'Tumbon',
                'company_name' => NULL,
                'company_tax_id' => NULL,
                'country' => 'TH',
                'email' => 'user@email.com',
                'forename' => 'FirstName',
                'line1' => 'Ratchadapisak Rd.',
                'line2' => NULL,
                'phone' => NULL,
                'postal_code' => '10310',
                'state_province' => 'Bangkok',
                'surname' => 'LastName',
                ),
            'payment_info' => array(
                'amount' => null,
                'currency' => $this->_currency,
                'item_list' => array(
                    'items' => array(
                        array(
                            'item_id'    => 1,
                            'shop_code'  => $this->_shopCode,
                            'service'    => 'bill',
                            'product_id' => 'p1',
                            'detail'     => 'd1',
                            'price'      => $this->decimals($this->_amount),
                            'ref1'       => '',
                            'ref2'       => '',
                            'ref3'       => '',
                        )
                    )
                ),
                'ref1' => '',
                'ref2' => '',
                'ref3' => ''
            )
        );

        $request = array_merge($defaults, $extends);

        $request['signature'] = $this->generateSignature($request);

        $requestAsString = json_encode($request);

        $options = array(
            CURLOPT_HTTPHEADER => array(
                'Content-Type: application/json',
                'Content-Length: ' . strlen($requestAsString),
                'Authorization:Bearer ' . $this->_bearer
            )
        );

        return $this->makeRequest($this->_gatewayCreatePaymentUrl, $requestAsString, $options);
    }

    private function generateSignature($request)
    {
        $compact = $this->_appId . $this->_invoice;

        $items = $request['payment_info']['item_list']['items'];

        for ($i = 0; $i < count($items); $i++)
        {
            $item = $items[$i];

            $compact .= $item['shop_code'] . $item['price'];
        }

        return $this->hash($compact);
    }

    private function hash($string)
    {
        return base64_encode(hash_hmac('sha256', $string, $this->_secret, true));
    }

    /**
     * Render the HTML payment Form
     */
    public function render($attrs = array())
    {
        $data = $this->build($attrs);

        $response = json_decode($data['response'], true);

        //sd($response);

        if ( ! isset($response['result']['response_code']) or $response['result']['response_code'] != 0)
        {
            throw new GatewayException('[TrueMoneyApi] There is something wrong!');
        }

        $paymentId = $response['payment_id'];
        $requestId = $response['request_id'];

        $this->_gatewayUrl .= '/' . $paymentId . '/process';

        $redirectSignature = $this->hash($paymentId.$requestId);

        $form = $this->makeFormPayment(array(
            'signature' => $redirectSignature
        ));

        return $form;
    }

    /**
     * Get invoice return from gateway server
     */
    public function getGatewayInvoice()
    {

    }

    /**
     * Get post frontend result from API gateway
     */
    public function getFrontendResult()
    {
        return $this->getBackendResult();
    }

    /**
     * Get post backend result from API gateway
     */
    public function getBackendResult()
    {
        if (count($_POST) == 0 or ! isset($_POST['payment_id']))
        {
            return false;
        }

        $paymentId = $_POST['payment_id'];

        $requestUrl = $this->_gatewayEnquiryUrl.'/'.$paymentId;

        $options = array(
            CURLOPT_HTTPHEADER => array(
                'Content-Type: application/json',
                'Content-Length: 0',
                'Authorization:Bearer ' . $this->_bearer
            )
        );

        $data = $this->makeRequest($requestUrl, null, $options, 'GET');

        $response = json_decode($data['response'], true);

        $paymentInfo = $response['payment_info'];
        $paymentResult = $response['payment_result'];

        $invoice = $paymentResult['request_id'];
        $currency = $paymentInfo['currency'];
        $amount = $paymentResult['paid_amount'];

        $result = array(
            'status' => true,
            'data'   => array(
                'gateway'  => self::GATEWAY,
                'status'   => $this->mapStatusReturned($paymentResult['payment_result_status']),
                'invoice'  => $invoice,
                'currency' => $currency,
                'amount'   => $amount,
                'dump'     => json_encode($data)
            ),
            'custom' => array(
                'recheck' => "yes"
            )
        );

        return $result;
    }

}
