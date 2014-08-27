<?php namespace Teepluss\Gateway\Drivers;

use Teepluss\Gateway\GatewayException;

class Kbank extends DriverAbstract implements DriverInterface {

    /**
     * Define Gateway name
     */
    const GATEWAY = "Kbank";

    /**
     * Define security hash prefix
     */
    const HASHSUM = "DEFINE_SECURITY";

    /**
     * Merchant ID
     */
    private $_merchantId;

    /**
     * Terminal ID
     */
    private $_terminalId;

    /**
     * @var Gateway URL
     */
    protected $_gatewayUrl = "https://rt05.kasikornbank.com/pgpayment/payment.aspx";

    /**
     * @var Method payment (credit, debit)
     */
    protected $_method = "credit";

    /**
     * @var mapping to transfrom parameter from gateway
     */
    protected $_defaults_params = array(
        'MERCHANT2'   => "",
        'TERM2'       => "",
        'AMOUNT2'     => "",
        'URL2'        => "",
        'RESPURL'     => "",
        'IPCUST2'     => "",
        'DETAIL2'     => "",
        'INVMERCHANT' => "",
        'FILLSPACE'   => "Y",
        'CHECKSUM'    => ""
    );

    /**
     * @var status return for success
     */
    protected $_success_group = array('00');

    /**
     * @var mapping payment methods
     */
    protected $_method_maps = array(
        'credit' => "02",
        'debit'  => "01"
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
     * [NOTICE] Kbank doesn't implement sandbox yet!
     *
     * @access public
     * @param  bool
     * @return \Teepluss\Gateway\Drivers\Kbank class (chaining)
     */
    public function setSandboxMode($val)
    {
        $this->_sandbox = $val;

        return $this;
    }

    /**
     * Get sandbox enable.
     *
     * [NOTICE] Kbank doesn't implement sandbox yet!
     *
     * @access public
     * @return bool
     */
    public function getSandboxMode()
    {
        return $this->_sandbox;
    }

    /**
     * Set payment method
     * credit | debit
     *
     * @access public
     * @param  string $val
     * @return \Teepluss\Gateway\Drivers\Kbank class (chaining)
     */
    public function setMethod($val)
    {
        $this->_method = $val;

        return $this;
    }

    /**
     * Get payment method
     *
     * @access public
     * @return string
     */
    public function getMethod()
    {
        return $this->_method;
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
        list($merchantId, $terminalId) = explode(':', $val);

        $this->setMerchantId($merchantId);

        $this->setTerminalId($terminalId);

        return $this;
    }

    /**
     * Set gateway merchant
     * Kbank using merchant instead of email
     *
     * @access public
     * @param  string $val
     * @return \Teepluss\Gateway\Drivers\Kbank class (chaining)
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
     * Set gateway terminal.
     *
     * Kbank using terms instead of config interface
     *
     * @access public
     * @param  string $val
     * @return \Teepluss\Gateway\Drivers\Kbank class (chaining)
     */
    public function setTerminalId($val)
    {
        $this->_terminalId = $val;

        return $this;
    }

    /**
     * Get gateway term
     *
     * @access public
     * @return string
     */
    public function getTerminalId()
    {
        return $this->_terminalId;
    }

    /**
     * Get invoice return from gateway feed data
     * This invoice return from gateway, so don't need set method
     *
     * @access public
     * @return string
     */
    public function getGatewayInvoice()
    {
        if (parent::isBackendPosted())
        {
            $pmgwresp = $_POST['PMGWRESP'];
            $invoice = substr($pmgwresp, (57-1), 12);

            // Re-format
            return preg_replace('#^(X|0)+#', '', $invoice);
        }

        throw new GatewayException('Gateway invoice return from backend posted only.');
    }

    /**
     * State of success payment returned.
     * override from abstract
     *
     * @access public
     * @return bool
     */
    public function isSuccessPosted()
    {
        if (parent::isSuccessPosted())
        {
            if (isset($_POST) and array_key_exists('HOSTRESP', $_POST))
            {
                $statusResult = $_POST['HOSTRESP'];

                return (in_array($statusResult, $this->_success_group));
            }
        }

        return false;
    }

    /**
     * State of canceled payment returned.
     *
     * override from abstract
     *
     * @access public
     * @return bool
     */
    public function isCancelPosted()
    {
        if (parent::isSuccessPosted())
        {
            if (isset($_POST['HOSTRESP']))
            {
                $statusResult = $_POST['HOSTRESP'];
                return ( ! in_array($statusResult, $this->_success_group));
            }
        }

        return false;
    }

    /**
     * Build array data and mapping from API
     * [NOTE] Kbank can set feed data URL by the field "RESPURL",
     * but you have to contact the bank to add this trust URL
     *
     * @access public
     * @param  array $extends (default: array())
     * @return array
     */
    protected function build($extends=array())
    {
        // Kbank amount formatting
        $amount = $this->_amount * 100;
        $amount = sprintf('%012d', $amount);

        // get real client IP
        $ip_address = $this->getClientIpAddress();

        $crumbs = md5(self::HASHSUM.$this->_invoice);
        $pass_parameters = array(
            'MERCHANT2'   => $this->_merchantId,
            'TERM2'       => $this->_terminalId,
            'INVMERCHANT' => $this->_invoice,
            'DETAIL2'     => $this->_purpose,
            'AMOUNT2'     => $amount,
            'URL2'        => $this->_successUrl,
            'RESPURL'     => $this->_backendUrl,
            'IPCUST2'     => $ip_address,
            'CHECKSUM'    => $crumbs,
            'SHOPID'      => $this->_method_maps[$this->_method]
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
     * POST data from API
     *
     * @access public
     * @return array (POST)
     */
    public function getFrontendResult()
    {
        if (count($_POST) == 0 or ! array_key_exists('HOSTRESP', $_POST))
        {
            return false;
        }

        $postdata = $_POST;

        $hostresp = $postdata['HOSTRESP'];
        $statusResult = (in_array($hostresp, $this->_success_group)) ? "success" : "pending";
        $invoice = (int)$postdata['RETURNINV'];
        $amount = ($postdata['AMOUNT'] / 100);
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
            )
        );

        return $result;
    }

    /**
     * Get data posted to background process.
     * To enable this feature you need to contect K-Bank directly
     * K-Bank need only trust SSL to return data feed.
     *
     * @access public
     * @return array
     */
    public function getBackendResult()
    {
        if (isset($_POST) and count($_POST) > 0)
        {
            $postdata = $_POST;

            if (array_key_exists('PMGWRESP', $postdata))
            {
                // mapping variables from data responded
                $pmgwresp = $postdata['PMGWRESP'];
                $splitters = array(
                    'ResponseCode'  => array(1, 2),
                    'Reserved1'     => array(3, 12),
                    'Authorize'     => array(15, 6),
                    'Reserved2'     => array(21, 36),
                    'TransAmount'   => array(83, 12),
                    'Invoice'       => array(57, 12),
                    'Timestamp'     => array(69, 14),
                    'Reserved3'     => array(95, 40),
                    'CardType'      => array(135, 20),
                    'Reserved4'     => array(155, 40),
                    'THBAmount'     => array(195, 12),
                    'TransCurrency' => array(207, 3),
                    'FXRate'        => array(210, 12)
                );
                $response = array();

                foreach ($splitters as $var_name => $pos)
                {
                    $begin = $pos[0] - 1;
                    $ended = $pos[1];

                    $theValue = substr($pmgwresp, $begin, $ended);
                    $theValue = preg_replace('#^X+|X+$#', '', $theValue);

                    $response[$var_name] = $theValue;
                }

                $statusResult = (in_array($response['ResponseCode'], $this->_success_group)) ? "success" : "pending";
                $invoice = (int)$response['Invoice'];
                $amount = ($response['TransAmount'] / 100);
                $amount = $this->decimals($amount, 2);

                $result = array(
                    'status' => true,
                    'data' => array(
                        'gateway'  => self::GATEWAY,
                        'status'   => $this->mapStatusReturned($statusResult),
                        'invoice'  => $invoice,
                        'currency' => $this->_currency,
                        'amount'   => $amount,
                        'dump'     => json_encode($response)
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

}
