<?php namespace Teepluss\Gateway\Drivers;

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

    private function encryptRequestXML($xml)
    {
        return array(
            RC4::EncryptRC4($this->_rc4key, $xml),
            md5($xml.'|'.$this->_password.'|'.$this->_privateKey)
        );
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

    }

    /**
     * Get post frontend result from API gateway
     */
    public function getFrontendResult()
    {

    }

    /**
     * Get post backend result from API gateway
     */
    public function getBackendResult()
    {

    }

}
