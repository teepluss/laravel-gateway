<?php namespace Teepluss\Gateway\Drivers;

use Teepluss\Gateway\GatewayException;

class Paysbuy extends DriverAbstract implements DriverInterface {

    /**
     * Define Gateway name
     */
    const GATEWAY = "Paysbuy";

    /**
     * @var Gateway URL
     */
    protected $_gatewayUrl = "https://www.paysbuy.com/paynow.aspx";

    /**
     * @var Check payment transaction (available only paysbuy)
     */
    protected $_checkUrl = "https://www.paysbuy.com/getinvoicestatus/getinvoicestatus.asmx/GetInvoice";

    /**
     * @var Payment method
     */
    protected $_method = "c";

    /**
     * @var Force method
     */
    protected $_forceMethod = 0;

    /**
     * @var mapping to transfrom parameter from gateway
     */
    protected $_defaults_params = array(
        'currencyCode'   => "840",
        'opt_fix_method' => 0,
        'opt_detail'     => "",
        'biz'            => "",
        'inv'            => "",
        'itm'            => "",
        'amt'            => "",
        'postURL'        => "",
        'reqURL'         => ""
    );

    /**
     * @var mapping language frontend interface
     */
    protected $_language_maps = array(
        'EN' => "e",
        'TH' => "t"
    );

    /**
     * @var mapping currency
     */
    protected $_currency_maps = array(
        'USD' => "840",
        'AUD' => "036",
        'GBP' => "826",
        'EUR' => "978",
        'HKD' => "344",
        'JPY' => "392",
        'NZD' => "554",
        'SGD' => "702",
        'CHF' => "756",
        'THB' => "764"
    );

    /**
     * @var mapping payment methods
     */
    protected $_method_maps = array(
        'psb' => "Paysbuy Account",
        'c'   => "Visa Credit Card",
        'm'   => "Master Card",
        'j'   => "JBC",
        'a'   => "American Express",
        'p'   => "Paypal",
        'cs'  => "Counter Service",
        'ob'  => "Online Banking"
    );

    /**
     * Construct the payment adapter.
     *
     * @access public
     * @param  array $params (default: array())
     * @return void
     */
    public function __construct($params=array())
    {
        parent::__construct($params);
    }

    /**
     * Set to enable sandbox mode.
     *
     * @access public
     * @param  bool
     * @return \Teepluss\Gateway\Drivers\Paysbuy
     */
    public function setSandboxMode($val)
    {
        $this->_sandbox = $val;

        if ($val == true)
        {
            $this->_gatewayUrl = str_replace('www.', 'demo.', $this->_gatewayUrl);
        }

        return $this;
    }

    /**
     * Get sandbox enable.
     *
     * @access public
     * @return bool
     */
    public function getSandboxMode()
    {
        return $this->_sandbox;
    }

    /**
     * Set payment method.
     *
     * @access public
     * @param  string $val
     * @return \Teepluss\Gateway\Drivers\Paysbuy
     */
    public function setMethod($val)
    {
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
     * Set force payment method.
     *
     * @access public
     * @param  string $val
     * @return \Teepluss\Gateway\Drivers\Paysbuy
     */
    public function setForceMethod($val)
    {
        $this->_forceMethod = $val;

        return $this;
    }

    /**
     * Get force payment method.
     *
     * @access public
     * @return string
     */
    public function getForceMethod($val)
    {
        $this->_forceMethod = $val;
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
        if (parent::isBackendPosted())
        {
            return substr($_POST['result'], 2);
        }

        throw new GatewayException('Gateway invoice return from backend posted only.');
    }

    /**
     * State of success payment returned.
     *
     * override from abstract
     *
     * @access public
     * @return bool
     */
    public function isSuccessPosted()
    {
        if (parent::isSuccessPosted())
        {
            if (isset($_POST) and array_key_exists('result', $_POST))
            {
                $statusResult = substr($_POST['result'], 0, 2);

                return (strcmp($statusResult, 99) != 0);
            }
        }

        return false;
    }

    /**
     * State of canceled payment returned.
     * override from abstract
     *
     * @access public
     * @return bool
     */
    public function isCancelPosted()
    {
        if (parent::isSuccessPosted())
        {
            if (isset($_POST) and array_key_exists('result', $_POST))
            {
                $statusResult = substr($_POST['result'], 0, 2);

                return ((strcmp($statusResult, 99) == 0) or $statusResult == '');
            }
        }

        return false;
    }

    /**
     * Build array data and mapping from API.
     *
     * @access public
     * @param  array $extends (default: array())
     * @return array
     */
    protected function build($extends=array())
    {
        $pass_parameters = array(
            'biz'            => $this->_merchantAccount,
            'inv'            => $this->_invoice,
            'itm'            => $this->_purpose,
            'amt'            => $this->_amount,
            'postURL'        => $this->_successUrl,
            'reqURL'         => $this->_backendUrl,
            'opt_fix_method' => $this->_forceMethod,
            'opt_detail'     => $this->_remark,
            'currencyCode'   => $this->_currency_maps[$this->_currency]
        );
        $params = array_merge($pass_parameters, $extends);
        $build_data = array_merge($this->_defaults_params, $params);

        return $build_data;
    }

    /**
     * Render from data with hidden fields.
     *
     * @access public
     * @param  array $attrs (default: array())
     * @return string HTML
     */
    public function render($attrs=array())
    {
        // make optional with query string
        $opts = array(
            $this->_method => "true",
            'lang'         => $this->_language_maps[$this->_language],
        );

        // Paysbuy account doesn't need to put anything.
        if ($this->_method == 'psb')
        {
            unset($opts[$this->_method]);
        }

        $query = http_build_query($opts);

        $this->_gatewayUrl .= "?".$query;

        $data = $this->build($attrs);

        return $this->makeFormPayment($data);
    }

    /**
     * Get a post back result from API gateway.
     *
     * POST data from API
     * Only Paysbuy we re-check transaction
     *
     * @access public
     * @return array (POST)
     */
    public function getFrontendResult()
    {
        if (count($_POST) == 0 or ! array_key_exists('apCode', $_POST))
        {
            return false;
        }

        $postdata = $_POST;

        $status = substr($postdata['result'], 0, 2);
        $invoice = substr($postdata['result'], 2);
        $amount = $this->decimals($postdata['amt']);

        $statusResult = ($status == 00) ? "success" : "pending";

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

    /**
     * Get data posted to background process.
     *
     * Sandbox is not available to use this, because have no API
     *
     * @access public
     * @return array
     */
    public function getBackendResult()
    {
        // paysbuy sandbox mode is fucking, so they don't have a simulate API to check invoice
        // anyway we can still use get fronend method instead.
        if ($this->_sandbox == true)
        {
            return $this->getFrontendResult();
        }

        if (count($_POST) == 0 or ! array_key_exists('apCode', $_POST))
        {
            return false;
        }

        $postdata = $_POST;

        // invoice from response
        $invoice = substr($postdata['result'], 2);

        // email to look up
        $merchantEmail = $this->_merchantAccount;

        try
        {
            $params = array(
                'merchantEmail' => $merchantEmail,
                'invoiceNo'     => $invoice,
                'strApCode'     => $postdata['apCode']
            );
            $response = $this->makeRequest($this->_checkUrl, $params);
            $xml = $response['response'];

            // parse XML
            $sxe = new SimpleXMLElement($xml);

            $methodResult = (string)$sxe->MethodResult;
            $statusResult = (string)$sxe->StatusResult;

            $amount = (string)$sxe->AmountResult;
            $amount = $this->decimals($amount);

            $result = array(
                'status' => true,
                'data'   => array(
                    'gateway'  => self::GATEWAY,
                    'status'   => $this->mapStatusReturned($statusResult),
                    'invoice'  => $invoice,
                    'currency' => $this->_currency,
                    'amount'   => $amount,
                    'dump'     => json_encode($postdata)
                ),
                'custom' => array(
                    'recheck' => "yes"
                )
            );
        }
        catch (\Exception $e)
        {
            $result = array(
                'status' => false,
                'msg'    => $e->getMessage()
            );
        }

        return $result;
    }

}