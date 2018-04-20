<?php namespace Teepluss\Gateway\Drivers;

use Teepluss\Gateway\GatewayException;

class TrueMoneyApi extends DriverAbstract implements DriverInterface {

    /**
     * Define Gateway name
     */
    const GATEWAY = 'TrueMoney';

    /**
     * App ID
     *
     * @var string
     */
    private $_appId;

    /**
     * Shop code
     *
     * @var string
     */
    private $_shopCode;

    /**
     * Secret
     *
     * @var string
     */
    private $_secret;

    /**
     * Bearer
     *
     * @var string
     */
    private $_bearer;

    /**
     * Gateway URL
     *
     * @var string
     */
    protected $_gatewayUrl = 'https://api-payment.truemoney.com/payments/v1/payment';

    /**
     * Create payment URL
     * @var string
     */
    protected $_gatewayCreatePaymentUrl = 'https://api-payment.truemoney.com/payments/v1/payment';

    /**
     * Enquiry URL
     *
     * @var string
     */
    protected $_gatewayEnquiryUrl = 'https://api-payment.truemoney.com/payments/v1/payment';

    /**
     * Gateway URL for SandboxMode
     *
     * @var string
     */
    protected $_sandboxGatewayUrl = 'https://api-payment.tmn-dev.com/payments/v1/payment';

    /**
     * Create payment URL  for SandboxMode
     * @var string
     */
    protected $_sandboxGatewayCreatePaymentUrl = 'https://api-payment.tmn-dev.com/payments/v1/payment';

    /**
     * Enquiry URL  for SandboxMode
     *
     * @var string
     */
    protected $_sandboxGatewayEnquiryUrl = 'https://api-payment.tmn-dev.com/payments/v1/payment';

    protected $payer;

    protected $address;

    protected $payment;

    protected $product;

    /**
     * Construct the adapter
     */
    public function __construct($params = array())
    {
        parent::__construct($params);

        $this->payer = new TrueMoneyApi\Payer;

        $this->address = new TrueMoneyApi\Address;

        $this->payment = new TrueMoneyApi\Payment;

        $this->product = new TrueMoneyApi\Product;
    }

    /**
     * Set appId.
     *
     * @param  string $val
     * @return \Teepluss\Gateway\Drivers\TrueMoneyApi
     */
    public function setAppId($val)
    {
        $this->_appId = $val;

        return $this;
    }

    /**
     * Get appId.
     *
     * @return string
     */
    public function getAppId()
    {
        return $this->_appId;
    }

    /**
     * Set shoeCode.
     *
     * @param  string $val
     * @return \Teepluss\Gateway\Drivers\TrueMoneyApi
     */
    public function setShopCode($val)
    {
        $this->_shopCode = $val;

        return $this;
    }

    public function getShopCode()
    {
        return $this->_shopCode;
    }

    /**
     * Set secret.
     *
     * @param  string $val
     * @return \Teepluss\Gateway\Drivers\TrueMoneyApi
     */
    public function setSecret($val)
    {
        $this->_secret = $val;

        return $this;
    }

    public function getSecret()
    {
        return $this->_secret;
    }

    /**
     * Set bearer.
     *
     * @param  string $val
     * @return \Teepluss\Gateway\Drivers\TrueMoneyApi
     */
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
     * Enable sandbox mode.
     *
     * @param  string $val
     * @return \Teepluss\Gateway\Drivers\TrueMoneyApi
     */
    public function setSandboxMode($val)
    {
        $this->_sandbox = $val;

        if ($val == true)
        {
            $this->_gatewayUrl = $this->_sandboxGatewayUrl;
            $this->_gatewayEnquiryUrl = $this->_sandboxGatewayEnquiryUrl;
            $this->_gatewayCreatePaymentUrl = $this->_sandboxGatewayCreatePaymentUrl;
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
     * Set debug
     */
    public function setDebug($val)
    {
        $this->_debug = $val;
        return $this;
    }

    /**
     * Get debug
     */
    public function getDebug()
    {
        return $this->_debug;
    }

    /**
     * Set account for merchant.
     *
     * @param \Teepluss\Gateway\Drivers\TrueMoneyApi
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

    public function payer($params = array())
    {
        return $this->payer->initialize($params);
    }

    public function address($params = array())
    {
        return $this->address->initialize($params);
    }

    public function payment($params = array())
    {
        return $this->payment->initialize($params);
    }

    public function product()
    {
        $product = $this->product;

        $product->setDefaultShopCode($this->_shopCode);

        return $product;
    }

    /**
     * Request token payment.
     *
     * @param  array  $extends
     * @return string json
     */
    public function build($extends = array())
    {
        $payer = $this->payer->get()->toArray();

        $address = $this->address->get()->toArray();

        $payment = $this->payment->get()->toArray();
        $payment['amount']   = $payment['amount'] ?: $this->decimals($this->_amount);
        $payment['currency'] = $payment['currency'] ?: $this->_currency;

        $products = $this->product->get()->toArray();

        $payment['item_list']['items'] = $products;

        $defaults = array(
            'app_id'        => $this->_appId,
            'intent'        => 'sale',
            'request_id'    => $this->_invoice,
            'locale'        => null,
            'payer'         => $payer,
            'payment_type'  => 'redirect',
            'redirect_urls' => array(
                'return_url' => $this->_successUrl,
                'cancel_url' => $this->_cancelUrl
            ),
            'billing_address' => $address,
            'payment_info'  => $payment
        );

        $request = array_merge($defaults, $extends);

        // True Money API need to count item price to summary.
        $amount = array_map(function($v) {
            return $v['price'];
        }, $products);

        $summary = array_sum($amount);
        $this->setAmount($summary);

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

    /**
     * Render payment form to redirect.
     *
     * @param string HTML
     */
    public function render($attrs = array())
    {
        $data = $this->build($attrs);

        $response = json_decode($data['response'], true);

        $response_code = empty($response['result']['response_code']) ? null : $response['result']['response_code'];

        if (is_null($response_code) || $response_code != 0)
        {
            $message = "TrueMoneyApi:{$response_code}";

            if (isset($response['result']['developer_message'])) {
                $message .= "-{$response['result']['developer_message']}";
            }

            throw new GatewayException($message);
        }

        $paymentId = $response['payment_id'];
        $requestId = $response['request_id'];

        // This process only TrueMoneyApi driver,
        // cause True Money doesn't send invoice.
        $this->setReferenceId($paymentId);

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
        // True Money doesn't return invoice.
    }

    /**
     * Get paymentId return from gateway feed data.
     *
     * This paymentId return from gateway, so don't need set method.
     *
     * @access public
     * @return string
     */
    public function getGatewayPaymentId()
    {
        if (parent::isBackendPosted())
        {
            return $_POST['payment_id'];
        }

        throw new GatewayException('Gateway invoice return from backend posted only.');
    }

    /**
     * Get post foreground result from API gateway.
     *
     * @param string
     */
    public function getFrontendResult()
    {
        $result = $this->getBackendResult();

        // Foreground proccess, we not stamp as re-check.
        if (isset($result['custom']['recheck']))
        {
            $result['custom']['recheck'] = 'no';
        }

        return $result;
    }

    /**
     * Get post background result from API gateway.
     *
     * @param string
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

        if (empty($response)) {
            throw new GatewayException('[TrueMoneyApi] There is something wrong!');
        }

        $paymentInfo = $response['payment_info'];
        $paymentResult = $response['payment_result'];

        $amount = $paymentResult['paid_amount'];
        $invoice = $paymentResult['request_id'];
        $currency = $paymentInfo['currency'];

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

}
