<?php namespace Teepluss\Gateway\Drivers;

class DriverAbstract {

    /**
     * Sandbox mode
     *
     * @var boolean
     */
    protected $_sandbox = false;

    /**
     * Gateway language
     *
     * @var string
     */
    protected $_language = "EN";

    /**
     * Gateway currency
     *
     * @var string
     */
    protected $_currency = "THB";

    /**
     * Gateway endpoint URL
     *
     * @var string
     */
    protected $_gatewayUrl;

    /**
     * Success response URL
     *
     * @var string
     */
    protected $_successUrl;

    /**
     * Cancel response URL
     *
     * @var string
     */
    protected $_cancelUrl;

    /**
     * Fail response URL
     *
     * @var string
     */
    protected $_failUrl;

    /**
     * Background process URL
     *
     * @var string
     */
    protected $_backendUrl;

    /**
     * Include submit button
     *
     * @var boolean
     */
    protected $_includeSubmitBtn = false;

    /**
     * Gateway account (email)
     *
     * @var string
     */
    protected $_merchantAccount;

    /**
     * Invoice
     *
     * @var mixed
     */
    protected $_invoice;

    /**
     * Payment description.
     *
     * @var string
     */
    protected $_purpose = 'Sale';

    /**
     * Payment amount
     *
     * @var integer
     */
    protected $_amount;

    /**
     * Payment note
     *
     * @var string
     */
    protected $_remark;

    /**
     * Client IP
     *
     * @var string
     */
    protected $_client_ip_address;

    /**
     * Reference ID
     *
     * This use when invoice doesn't return from background.
     *
     * @var mixed
     */
    protected $_referenceId;

    /**
     * Gateway status
     *
     * @var array
     */
    protected $_statusReturned = array(
        'success' => array(
            'success', 'successes', 'succeeded',
            'complete', 'completed', 'accept'
        ),
        'failed'  => array(
            'fail', 'failed', 'reject', 'rejected',
            'cancel', 'canceled', 'error'
        ),
        'pending' => array(
            'pending', 'waiting', 'wait',
            'process', 'progress'
        ),
        // Some foreground.
        'unknown' => array(
            'unknown', 'unknow'
        )
    );

    /**
     * Construct the adapter payment.
     *
     * @param array $params
     */
    public function __construct($params=array())
    {
        $this->initialize($params);
    }

    /**
     * Option intialize.
     *
     * @param  array $params
     * @return object
     */
    public function initialize($params)
    {
        if (count($params) > 0)
        {
            foreach ($params as $key => $val)
            {
                $method = "set".ucfirst($key);

                if (method_exists($this, $method))
                {
                    $this->$method($val);
                }
                else
                {
                    $this->setAny($key, $val);
                }
            }
        }

        return $this;
    }

    /**
     * Merchant account.
     *
     * @param  string
     * @return object
     */
    public function setMerchantAccount($val)
    {
        // Some adpater you need to pass parameters more than email.
        if (is_array($val))
        {
            $opts = $val;

            foreach ($opts as $key => $val)
            {
                $method = 'set'.ucfirst($key);

                if (method_exists($this, $method))
                {
                    $this->$method($val);
                }
                else
                {
                    throw new EpayException('Method "'.$key.'" is not match for the adapter.');
                }
            }

            return $this;
        }

        // Normally this is an email.
        $this->_merchantAccount = $val;

        return $this;
    }

    /**
     * Get merchant account.
     *
     * @return string
     */
    public function getMerchantAccount()
    {
        return $this->_merchantAccount;
    }

    /**
     * Gateway language interface.
     *
     * @param  string $val
     * @return object
     */
    public function setLanguage($val)
    {
        $this->_language = strtoupper($val);

        return $this;
    }

    /**
     * Get gateway language interface.
     *
     * @return string
     */
    public function getLanguage()
    {
        return $this->_language;
    }

    /**
     * Set payment currency.
     *
     * @param  string $val
     * @return object
     */
    public function setCurrency($val)
    {
        $this->_currency = strtoupper($val);

        return $this;
    }

    /**
     * Get payment currency.
     *
     * @return string
     */
    public function getCurrency()
    {
        return $this->_currency;
    }

    /**
     * Set unique invoice ID.
     *
     * @param  string
     * @return object
     */
    public function setInvoice($val)
    {
        $this->_invoice = $val;

        return $this;
    }

    /**
     * Get invoice ID.
     *
     * @return string
     */
    public function getInvoice()
    {
        return $this->_invoice;
    }

    /**
     * Set purpose of payment
     *
     * @param  string
     * @return object
     */
    public function setPurpose($val)
    {
        $this->_purpose = $val;

        return $this;
    }

    /**
     * Get purpose of payment.
     *
     * @return string
     */
    public function getPurpose()
    {
        return $this->_purpose;
    }

    /**
     * Set payment amount.
     *
     * @param  integer
     * @return object
     */
    public function setAmount($val)
    {
        if (is_numeric($val))
        {
            $this->_amount = $val;

            return $this;
        }

        throw new EpayException('Amount must be integer.');
    }

    /**
     * Get payment amount.
     *
     * @return integer
     */
    public function getAmount()
    {
        return $this->_amount;
    }

    /**
     * Set payment note.
     *
     * @param  string
     * @return object
     */
    public function setRemark($val)
    {
        $this->_remark = $val;

        return $this;
    }

    /**
     * Get payment note.
     *
     * @return string
     */
    public function getRemark()
    {
        return $this->_remark;
    }

    /**
     * Set reference id.
     *
     * @param  mixed $val
     * @return void
     */
    public function setReferenceId($val)
    {
        $this->_referenceId = $val;
    }

    /**
     * Get reference id.
     *
     * @return mixed
     */
    public function getReferenceId()
    {
        return $this->_referenceId;
    }

    /**
     * Set client IP address
     *
     * @param  string $val
     * @return object
     */
    public function setClientIpAddress($val)
    {
        $this->_client_ip_address = $val;

        return $this;
    }

    /**
     * Get client IP address
     *
     * @return string
     */
    public function getClientIpAddress()
    {
        if ( ! $this->_client_ip_address)
        {
            if (!empty($_SERVER["HTTP_CLIENT_IP"]))
            {
                $ip = $_SERVER["HTTP_CLIENT_IP"];
            }
            elseif (!empty($_SERVER["HTTP_X_FORWARDED_FOR"]))
            {
                $ip = $_SERVER["HTTP_X_FORWARDED_FOR"];
            }
            else
            {
                $ip = $_SERVER["REMOTE_ADDR"];
            }

            $this->_client_ip_address = $ip;
        }

        return $this->_client_ip_address;
    }

    /**
     * Set success front URL.
     *
     * @param  string
     * @return object
     */
    public function setSuccessUrl($val)
    {
        $this->_successUrl = $this->addQueryState($val, 'success');

        return $this;
    }

    /**
     * Get success front URL.
     *
     * @return string
     */
    public function getSuccessUrl()
    {
        return $this->_successUrl;
    }

    /**
     * Set cancel front URL.
     *
     * Status cancel from gateway,
     * this is not support for all gateways.
     *
     * @param  string
     * @return object
     */
    public function setCancelUrl($val)
    {
        $this->_cancelUrl = $this->addQueryState($val, 'cancel');

        return $this;
    }

    /**
     * Get cancel front URL.
     *
     * @return string
     */
    public function getCancelUrl()
    {
        return $this->_cancelUrl;
    }

    /**
     * Set fail front URL.
     *
     * Status fail from gateway,
     * this is not support for all gateways.
     *
     * @param  string
     * @return object
     */
    public function setFailUrl($val)
    {
        $this->_failUrl = $this->addQueryState($val, 'fail');

        return $this;
    }

    /**
     * Get fail front URL
     *
     * @return string
     */
    public function getFailUrl()
    {
        return $this->_failUrl;
    }

    /**
     * Set backend process URL.
     *
     * Background process server to server
     *
     * @param  string
     * @return object
     */
    public function setBackendUrl($val)
    {
        $this->_backendUrl = $this->addQueryState($val, 'backend');

        return $this;
    }

    /**
     * Get backend process URL.
     *
     * @return string
     */
    public function getBackendUrl()
    {
        return $this->_backendUrl;
    }

    /**
     * Set anything else that gateway allow.
     *
     * @param  string $name
     * @param  mixed  $val
     * @return object
     */
    public function setAny($name, $val)
    {
        $variable = '_'.(string) $name;

        $this->$variable = $val;

        return $this;
    }

    /**
     * Get anything else that gateway allow.
     *
     * @param  string $name
     * @return mixed
     */
    public function getAny($name)
    {
        $variable = '_'.(string) $name;

        return isset($this->$variable) ? $this->$variable : null;
    }

    /**
     * State of success payment returned.
     *
     * @return boolean
     */
    public function isSuccessPosted()
    {
        if (array_key_exists('state', $_GET))
        {
            return (strcmp($_GET['state'], 'success') == 0);
        }

        return false;
    }

    /**
     * State of canceled payment returned.
     *
     * @return boolean
     */
    public function isCancelPosted()
    {
        if (array_key_exists('state', $_GET))
        {
            return (strcmp($_GET['state'], 'cancel') == 0);
        }

        return false;
    }

    /**
     * State of rejected payment returned.
     *
     * This state does not support for many gateway
     *
     * @return boolean
     */
    public function isFailPosted()
    {
        if (array_key_exists('state', $_GET))
        {
            return (strcmp($_GET['state'], 'fail') == 0);
        }

        return false;
    }

    /**
     * State of backend post to server.
     *
     * @return boolean
     */
    public function isBackendPosted()
    {
        if (array_key_exists('state', $_GET) and count($_POST))
        {
            return (strcmp($_GET['state'], 'backend') == 0);
        }

        return false;
    }

    /**
     * Add state to query URL.
     *
     * @param  string $url
     * @param  string $state
     * @return string
     */
    private function addQueryState($url, $state)
    {
        if (strpos($url, '?'))
        {
            $url .= "&state=".$state;
        }
        else
        {
            $url .= "?state=".$state;
        }

        return $url;
    }

    /**
     * Form payment include submit button.
     *
     * @return object
     */
    public function includeSubmitButton()
    {
        $this->_includeSubmitBtn = true;

        return $this;
    }

    /**
     * Alias of includeSubmitButton.
     *
     * @return object
     */
    public function includeSubmitBtn()
    {
        return $this->includeSubmitButton();
    }

    /**
     * Building HTML Form
     *
     * Adapter use this method to building a hidden form.
     *
     * @param  array
     * @return string HTML
     */
    protected function makeFormPayment($attrs = array(), $method = 'POST')
    {
        $hiddens = array();

        $addition = array();

        foreach ($attrs as $key => $val)
        {
            $hiddens[] = '<input type="hidden" name="'.$key.'" value="'.$val.'" />' . "\n";
        }

        if ($this->_includeSubmitBtn)
        {
            $addition[] = '<p><input type="submit" value="Pay Now"></p>';
        }

        $form = '
            <form method="'.$method.'" action="'.$this->_gatewayUrl.'" id="form-gateway">
                '.implode('', $hiddens).'
                '.implode('', $addition).'
            </form>
        ';

        return $form;
    }

    /**
     * Make POST request via CURL.
     *
     * @param  string $url
     * @param  array
     * @param  array
     * @return array
     */
    protected function makeRequest($url, $data = array(), $curl_opts_extends = array(), $method = 'POST')
    {
        $curl = curl_init();

        if (is_array($data))
        {
            $data = http_build_query($data);
        }

        $curl_opts = array(
            CURLOPT_URL            => $url,
            CURLOPT_CUSTOMREQUEST  => $method,
            CURLOPT_POSTFIELDS     => $data,
            CURLOPT_RETURNTRANSFER => true,
            //CURLOPT_HEADER         => false,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => 0
        );

        // Override or extend curl options
        if (count($curl_opts_extends))
        {
            foreach ($curl_opts_extends as $key => $val)
            {
                $curl_opts[$key] = $val;
            }
        }

        curl_setopt_array($curl, $curl_opts);

        // Response returned.
        $response = curl_exec($curl);
        $status   = curl_getinfo($curl, CURLINFO_HTTP_CODE);

        curl_close($curl);

        return array(
            'status'   => ($status == 200) ? true : false,
            'response' => $response
        );
    }

    /**
     * Map status returned from all gateway.
     *
     * Because too much format return from gateway
     *
     * @param  string
     * @return string
     */
    protected function mapStatusReturned($keyword)
    {
        $keyword = strtolower($keyword);
        $all_status = $this->_statusReturned;

        foreach ($all_status as $status => $group)
        {
            if (in_array($keyword, $group))
            {
                return $status;
            }
        }

        return 'other';
    }

    /**
     * Change integer to real number format.
     *
     * @param  integer $int
     * @param  integer $decimals
     * @return float
     */
    protected function decimals($int, $decimals = 2)
    {
        return number_format($int, $decimals, '.', '');
    }

}