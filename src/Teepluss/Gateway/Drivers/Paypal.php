<?php namespace Teepluss\Gateway\Drivers;

use Teepluss\Gateway\GatewayException;

class Paypal extends DriverAbstract implements DriverInterface {

    /**
     * Define Gateway name
     */
    const GATEWAY = "Paypal";

    /**
     * @var Gateway URL
     */
    protected $_gatewayUrl = "https://www.paypal.com/cgi-bin/webscr";

    /**
     * @var mapping to transfrom parameter from gateway
     */
    protected $_defaults_params = array(
        'cmd'             => "_xclick",
        'lc'              => "EN",
        'notify_url'      => "",
        'return'          => "",
        'cancel_return'   => "",
        'business'        => "",
        'invoice'         => "",
        'item_name'       => "",
        'item_number'     => "",
        'amount'          => "",
        'currency_code'   => "THB",
        'discount_amount' => 0,
        'quantity'        => 1,
        'custom'          => ""
    );

    /**
     * @var mapping language frontend interface
     */
    protected $_language_maps = array(
        'EN' => "EN",
        'TH' => "TH"
    );

    /**
     * @var mapping currency
     */
    protected $_currency_maps = array(
        'USD' => "USD",
        'THB' => "THB"
    );

    /**
     * @var mapping payment methods
     */
    protected $_method_maps = array(
        '01' => "Paypal Account",
        '02' => "Credit Card"
    );

    /**
     * Construct the payment adapter
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
     * Set to enable sandbox mode
     *
     * @access public
     * @param  bool
     * @return object
     */
    public function setSandboxMode($val)
    {
        $this->_sandbox = $val;

        if ($val == true)
        {
            $this->_gatewayUrl = str_replace('www.', 'www.sandbox.', $this->_gatewayUrl);
        }

        return $this;
    }

    /**
     * Get sandbox enable
     *
     * @access public
     * @return bool
     */
    public function getSandboxMode()
    {
        return $this->_sandbox;
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
            return $_POST['invoice'];
        }

        throw new GatewayException('Gateway invoice return from backend posted only.');
    }

    /**
     * Build array data and mapping from API.
     *
     * @access public
     * @param  array $extends (default: array())
     * @return array
     */
    public function build($extends = array())
    {
        $pass_parameters = array(
            'business'      => $this->_merchantAccount,
            'invoice'       => $this->_invoice,
            'item_name'     => $this->_purpose,
            'amount'        => $this->_amount,
            'return'        => $this->_successUrl,
            'cancel_return' => $this->_cancelUrl,
            'notify_url'    => $this->_backendUrl,
            'currency_code' => $this->_currency_maps[$this->_currency],
            'lc'            => $this->_language_maps[$this->_language]
        );

        if ($this->_remark)
        {
            $extends = array_merge($extends, array(
                'custom' => $this->_remark
            ));
        }

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
    public function render($attrs = array())
    {
        $data = $this->build($attrs);

        return $this->makeFormPayment($data);
    }

    /**
     * Get a post back result from API gateway.
     *
     * POST data from API
     *
     * @access public
     * @return array (POST)
     */
    public function getFrontendResult()
    {
        if (count($_POST) == 0 or ! array_key_exists('invoice', $_POST))
        {
            return false;
        }

        $postdata = $_POST;

        $statusResult = $postdata['payment_status'];
        $invoice = $postdata['invoice'];
        $amount = $this->decimals($postdata['mc_gross']);

        $result = array(
            'status' => true,
            'data'   => array(
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
     * Get backend posted from server.
     *
     * Check transaction from IPN data
     *
     * @access public
     * @return array
     */
    public function getBackendResult()
    {
        if (isset($_POST) and count($_POST) > 0)
        {
            $postdata = $_POST;
            $postdata['cmd'] = "_notify-validate";

            $response = $this->makeRequest($this->_gatewayUrl, $postdata, array(
                CURLOPT_CAINFO => "cacert.pem"
            ));

            $status = $response['status'];
            $body = $response['response'];

            if (preg_match('|VERIFIED|', $body))
            {
                $statusResult = $postdata['payment_status'];
                $invoice = $postdata['invoice'];
                $amount = $this->decimals($postdata['mc_gross']);
                $result = array(
                    'status' => true,
                    'data' => array(
                        'gateway'  => self::GATEWAY,
                        'status'   => $this->mapStatusReturned($statusResult),
                        'invoice'  => $invoice,
                        'currency' => $this->_currency,
                        'amount'   => $amount,
                        'dump'     => json_encode($postdata)
                    ),
                    'custom' => array(
                        'response_ipn' => $body
                    )
                );

                return $result;
            } // end if verified

        } // end isset post

        // in case ipn not found first
        $result = array(
            'status' => false,
            'msg'    => "Can not verify IPN"
        );

        return $result;
    }

}