<?php namespace Teepluss\Gateway\Drivers;

use Teepluss\Gateway\GatewayException;

class Bbl extends DriverAbstract implements DriverInterface {

    /**
     * Gateway name.
     */
    const GATEWAY = "Bbl";

    /**
     * Merchant ID
     */
    private $_merchantId;

    /**
     * @var Username
     */
    private $_username;

    /**
     * @var Password
     */
    private $_password;

    /**
     * @var Payment Method
     */
    private $_method = "CC";

    /**
     * @var Gateway URL
     */
    protected $_gatewayUrl = "https://ipay.bangkokbank.com/b2c/eng/payment/payForm.jsp";

    /**
     * @var Check payment transaction (available only paysbuy)
     */
    protected $_checkUrl = "https://ipay.bangkokbank.com/b2c/eng/merchant/api/orderApi.jsp";

    /**
     * @var Reference var
     * BBL Only
     */
    protected $_ref1;
    protected $_ref2;
    protected $_ref3;
    protected $_ref4;
    protected $_ref5;

    /**
     * @var BBL prefix
     */
    protected $_prefix;

    /**
     * @var mapping to transfrom parameter from gateway
     */
    protected $_defaults_params = array(
        'merchantId'  => "",
        'currCode'    => "764",
        'lang'        => "E",
        'amount'      => "",
        'successUrl'  => "",
        'failUrl'     => "",
        'cancelUrl'   => "",
        'payType'     => "N",
        'payMethod'   => "CC",
        'orderRef'    => "",
        'remark'      => "-",
        'redirect'    => "30",
        'orderRef1'   => "",
        'orderRef2'   => "",
        'orderRef3'   => "",
        'orderRef4'   => "",
        'orderRef5'   => "",
        'templateId'  => 1,
        'prefix'      => ""
    );

    /**
     * @var mapping language frontend interface
     */
    protected $_language_maps = array(
        'EN' => "E",
        'TH' => "T"
    );

    /**
     * @var mapping currency
     */
    protected $_currency_maps = array(
        'USD' => "840",
        'THB' => "764"
    );

    /**
     * @var mapping payment methods
     */
    protected $_method_maps = array(
        'ALL' => "Accept All Method Available",
        'CC'  => "Credit Card"
    );

    /**
     * Construct the payment adapter.
     *
     * @access public
     * @param  array $params (default: array())
     * @return void
     */
    public function __construct($params = array())
    {
        parent::__construct($params);
    }

    /**
     * Set to enable sandbox mode.
     *
     * [NOTICE] Bbl doesn't implement sandbox yet!
     *
     * @access public
     * @param  bool
     * @return object
     */
    public function setSandboxMode($val)
    {
        $this->_sandbox = $val;

        return $this;
    }

    /**
     * Get sandbox enable.
     *
     * [NOTICE] Bbl doesn't implement sandbox yet!
     *
     * @access public
     * @return bool
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

        $this->setMerchantId($val);

        return $this;
    }

    /**
     * Set gateway merchant.
     *
     * Kbank using merchant instead of email
     *
     * @access public
     * @param  string $val
     * @return object
     */
    public function setMerchantId($val)
    {
        $this->_merchantId = $val;

        return $this;
    }

    /**
     * Get gateway merchant
     *
     * @access public
     * @return string
     */
    public function getMerchantId()
    {
        return $this->_merchantId;
    }

    /**
     * Set gateway username.
     *
     * API require username to access logs
     *
     * @access public
     * @param  string $val
     * @return object
     */
    public function setUsername($val)
    {
        $this->_username = $val;

        return $this;
    }

    /**
     * Get gateway username.
     *
     * @access public
     * @return string
     */
    public function getUsername()
    {
        return $this->_username;
    }

    /**
     * Set gateway password.
     *
     * API require password to access
     *
     * @access public
     * @param  string $val
     * @return object
     */
    public function setPassword($val)
    {
        $this->_password = $val;

        return $this;
    }

    /**
     * Get gateway username.
     *
     * @access public
     * @return string
     */
    public function getPassword()
    {
        return $this->_password;
    }

    /**
     * Set payment method.
     *
     * @access public
     * @param  string $val
     * @return object
     */
    public function setMethod($val)
    {
        $val = strtoupper($val);

        if (array_key_exists($val, $this->_method_maps))
        {
            $this->_method = $val;
        }

        return $this;
    }

    /**
     * Get payment method.
     *
     * @access public
     * @return string
     */
    public function getMethod()
    {
        return $this->_method;
    }

    /**
     * Get invoice return from gateway feed data.
     *
     * This invoice return from gateway, so don't need set method
     *
     * @access public
     * @return string
     */
    public function getGatewayInvoice()
    {
        if ($this->isBackendPosted())
        {
            return $_POST['Ref'];
        }

        throw new GatewayException('Gateway invoice return from backend posted only.');
    }

    /**
     * State of backend post to server.
     *
     * override from abstract
     *
     * @access public
     * @return bool
     */
    public function isBackendPosted()
    {
        return isset($_POST['Ref']);
    }

    /**
     * Build array data and mapping from API.
     *
     * [NOTE] Bbl cannot set feed data URL,
     * so you have to contact the bank to add this trust URL
     *
     * @access public
     * @param  array $extends (default: array())
     * @return array
     */
    public function build($extends=array())
    {
        $pass_parameters = array(
            'merchantId' => $this->_merchantId,
            'currCode'   => $this->_currency_maps[$this->_currency],
            'lang'       => $this->_language_maps[$this->_language],
            'amount'     => $this->_amount,
            'successUrl' => $this->_successUrl,
            'failUrl'    => $this->_failUrl,
            'cancelUrl'  => $this->_cancelUrl,
            'payType'    => "N",
            'payMethod'  => $this->_method,
            'orderRef'   => $this->_invoice,
            'remark'     => $this->_remark,
            'orderRef1'  => $this->_ref1,
            'orderRef2'  => $this->_ref2,
            'orderRef3'  => $this->_ref3,
            'orderRef4'  => $this->_ref4,
            'orderRef5'  => $this->_ref5,
            'templateId' => "1",
            'prefix'     => $this->_prefix
        );

        $params = array_merge($pass_parameters, $extends);

        $build_data = array_merge($this->_defaults_params, $params);

        return $build_data;
    }

    /**
     * Render from data with hidden fields
     *
     * @access public
     * @param  array $attrs (default: array())
     * @return string HTML
     */
    public function render($attrs=array())
    {
        // make webpage language
        $data = $this->build($attrs);

        return $this->makeFormPayment($data);
    }

    /**
     * Get a post back result from API gateway
     * Bbl does not post anything to front action
     *
     * @access public
     * @return array
     */
    public function getFrontendResult()
    {
        if (isset($_GET['Ref']))
        {
            $invoice = $_GET['Ref'];
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

        return false;
    }

    /**
     * Get data posted to background process.
     * To enable this feature you need to contect BBL directly
     * Bbl need only trust SSL to return data feed.
     * [IMPORTANT] For response back to Gateway you need to type "OK" on HTML.
     *
     * @access public
     * @return array
     */
    public function getBackendResult()
    {
        if (isset($_POST) && count($_POST) > 0)
        {
            $postdata = $_POST;

            if (array_key_exists('successcode', $postdata))
            {
                $statusResult = ($postdata['successcode'] == 0) ? "success" : "pending";
                $invoice = $postdata['Ref'];
                $amount = $this->decimals($postdata['Amt']);

                $result = array(
                    'status' => true,
                    'data' => array(
                        'gateway'  => self::GATEWAY,
                        'status'   => $this->mapStatusReturned($statusResult),
                        'invoice'  => $invoice,
                        'currency' => $this->_currency,
                        'amount'   => $amount,
                        'dump'     => json_encode($postdata)
                    )
                );

                return $result;
            }
        }

        $result = array(
            'status' => false,
            'msg'    => "Can not get data feed."
        );

        return $result;
    }

    /**
     * Lookup invoice status
     *
     * Using RESTFUL API to get invoice status
     *
     * @access public
     * @param  array $params (default: array())
     * @return array
     */
    public function lookUp($params=array())
    {
        $defaults = array(
            'merchantId' => $this->_merchantId,
            'loginId'    => $this->_username,
            'password'   => $this->_password,
            'actionType' => 'Query',
            'orderRef'   => $this->_invoice,
            'payRef'     => null
        );
        $params = array_merge($defaults, $params);

        $response = $this->makeRequest($this->_checkUrl, $params);

        if ($response['status'])
        {
            $xmlstr = new \SimpleXMLElement($response['response']);
            $record = $xmlstr->record;

            $statusResult = ($record->orderStatus == 'Accepted') ? 'success' : 'pending';

            $result = array(
                'status' => true,
                'data'   => array(
                    'gateway'  => self::GATEWAY,
                    'status'   => $this->mapStatusReturned($statusResult),
                    'invoice'  => (string)$record->ref,
                    'amount'   => (string)$record->amt
                )
            );

            return $result;
        }

        $result = array(
            'status' => false,
            'msg'    => 'Can not get data feed.'
        );

        return $result;
    }

}

