<?php namespace Teepluss\Gateway\Drivers;

use Input;
use Teepluss\Gateway\GatewayException;
use Teepluss\Gateway\Drivers\TruePaymentApi\RC4;
use Teepluss\Gateway\Drivers\TruePaymentApi\Format;

class TruePaymentApi extends DriverAbstract implements DriverInterface {

    /**
     * Define Gateway name
     */
    const GATEWAY = 'TruePayment';

    /**
     * App ID
     *
     * @var string
     */
    private $_appId;

    /**
     * Shop ID
     *
     * @var string
     */
    private $_shopId;

    /**
     * Private Key
     *
     * @var string
     */
    private $_privateKey;

    /**
     * Password
     *
     * @var string
     */
    private $_password;

    /**
     * RC4 Key
     *
     * @var string
     */
    private $_rc4key;


    /**
     * Payment method.
     *
     * @var string
     */
    private $_method = 'CCW';

    /**
     * Method mapping.
     *
     * @var array
     */
    protected $_method_maps = array(
        'CCW'  => "Credit Card",
        'DD'   => "Direct Debit",
        'EW'   => "eWallet",
        'MMCC' => "True Money Cash Card",
        'ATM'  => "Money Transfer from ATM"
    );

    /**
     * Gateway URL
     *
     * @var string
     */
    protected $_gatewayUrl = 'https://www.weloveshopping.com/eOCGW/PayAPI/requestPayment.php';

    protected $payer;

    protected $address;

    protected $product;

    /**
     * Response from True background.
     *
     * @var array
     */
    protected $response;

    /**
     * Construct the adapter
     */
    public function __construct($params = array())
    {
        parent::__construct($params);

        $this->payer = new TruePaymentApi\Payer;

        $this->billing = new TruePaymentApi\Billing;

        $this->product = new TruePaymentApi\Product;
    }

    public function setAppId($val)
    {
        $this->_appId = $val;

        return $this;
    }

    public function setShopId($val)
    {
        $this->_shopId = $val;

        return $this;
    }

    public function setPrivateKey($val)
    {
        $this->_privateKey = $val;

        return $this;
    }

    public function setPassword($val)
    {
        $this->_password = $val;

        return $this;
    }

    public function setRC4Key($val)
    {
        $this->_rc4key = $val;

        return $this;
    }

    /**
     * Enable sandbox mode.
     *
     * @param  string $val
     * @return \Teepluss\Gateway\Drivers\TruePaymentApi
     */
    public function setSandboxMode($val)
    {
        $this->_sandbox = $val;

        return $this;
    }

    /**
     * Set account for merchant.
     *
     * @param \Teepluss\Gateway\Drivers\TruePaymentApi
     */
    public function setMerchantAccount($val)
    {
        if (is_array($val))
        {
            return parent::setMerchantAccount($val);
        }

        // Explode from string.
        list($appId, $shopId, $password, $privateKey, $rc4key) = explode(':', $val);

        $this->setAppId($appId);

        $this->setShopId($shopId);

        $this->setPassword($password);

        $this->setPrivateKey($privateKey);

        $this->setRC4Key($rc4key);

        return $this;
    }

    /**
     * Set payment method.
     *
     * @param  string $val
     * @return \Teepluss\Gateway\Drivers\TruePaymentApi
     */
    public function setMethod($val)
    {
        if (array_key_exists($val, $this->_method_maps))
        {
            $this->_method = $val;
        }

        return $this;
    }

    public function payer($params = array())
    {
        return $this->payer->initialize($params);
    }

    public function billing($params = array())
    {
        return $this->billing->initialize($params);
    }

    public function payment($params = array())
    {
        return $this->payment->initialize($params);
    }

    public function product()
    {
        $product = $this->product;

        $product->setDefaultShopId($this->_shopId);

        return $product;
    }

    /**
     * Transform payment fields and build to array
     *
     * @param array $opts
     */
    public function build($extends = array())
    {
        $products = array();

        $payer = $this->payer->get()->toArray();
        $billing = $this->billing->get()->toArray();

        $products = $this->product->get()->toArray();

        $products = array(
            'shopid' => $this->_shopId,
            'items'  => $products
        );

        $addition = array(
            'response_url' => $this->_successUrl,
            'back_url'     => $this->_successUrl,
            'note'         => $this->_remark,
            'is_mobile'    => 'No'
        );

        $toXML = array(
            'rowproduct'   => $products,
            'sof_channel'  => $this->_method,
            'referenceId'  => $this->_invoice,
            'rowuser'      => $payer,
            'billing'      => $billing,
            'extraparam'   => $addition
        );

        $xmlStr = with(new Format)->toXML($toXML, null, 'request');

        $encryptRequestXML = $this->encryptRequestXML($xmlStr);

        $data = array(
            'app_id'    => $this->_appId,
            'xml_order' => $encryptRequestXML[0],
            'chkSum'    => $encryptRequestXML[1]
        );

        return $data;
    }

    public function encryptRequestXML($xml)
    {
        return array(
            RC4::EncryptRC4($this->_rc4key, $xml),
            md5($xml.'|'.$this->_password.'|'.$this->_privateKey)
        );
    }

    public function decryptResponseRaw()
    {
        if ($this->response)
        {
            return $this->response;
        }

        // Raw must come from background process, $_POST['raw'] is dummy.
        $raw = isset($_POST['raw']) ? $_POST['raw'] : file_get_contents('php://input');

        // No data to decrypt.
        if (empty($raw))
        {
            return false;
        }

        $decrypted = RC4::DecryptRC4($this->_rc4key, $raw);
        $data = Format::factory($decrypted, 'xml')->toArray();

        $decryptData = array_get($data, 'payment.@attributes', array());

        // Add decrypt xml to input.
        //Input::merge($decryptData);

        $this->response = $decryptData;

        return $decryptData;
    }

    /**
     * Render the HTML payment Form
     *
     * @param array $attrs
     */
    public function render($attrs = array())
    {
        $data = $this->build($attrs);

        return $form = $this->makeFormPayment($data);
    }

    /**
     * Get invoice return from gateway server
     */
    public function getGatewayInvoice()
    {
        $data = $this->decryptResponseRaw();

        return array_get($data, 'ref3', null);
    }

    /**
     * Get post frontend result from API gateway
     *
     * TruePaymentApi doesn't return state of payment on foreground.
     *
     * @return array
     */
    public function getFrontendResult()
    {
        if ( ! count($_POST) or ! array_key_exists('xmlRes', $_POST))
        {
            return false;
        }

        $postdata = Format::factory($_POST['xmlRes'], 'xml')->toArray();

        $invoice = array_get($postdata, 'ref3');

        // Unkwow state.
        $statusResult = "pending";

        $postdata = array();
        $result = array(
            'status' => true,
            'data'   => array(
                'gateway'  => self::GATEWAY,
                'status'   => $this->mapStatusReturned($statusResult),
                'invoice'  => $invoice,
                'currency' => $this->_currency,
                'amount'   => 0,
                'dump'     => json_encode($postdata)
            )
        );

        return $result;
    }

    /**
     * Get post backend result from API gateway
     */
    public function getBackendResult()
    {
        $data = $this->decryptResponseRaw();

        $statusResult = (array_get($data, 'respcode') == 0) ? 'success' : 'failed';
        $invoice = array_get($data, 'ref3');
        $amount = $this->decimals(array_get($data, 'amount'));

        $result = array(
            'status' => true,
            'data'   => array(
                'gateway'  => self::GATEWAY,
                'status'   => $this->mapStatusReturned($statusResult),
                'invoice'  => $invoice,
                'currency' => $this->_currency,
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
