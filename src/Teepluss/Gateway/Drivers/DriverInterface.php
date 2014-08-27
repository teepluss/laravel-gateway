<?php namespace Teepluss\Gateway\Drivers;

interface DriverInterface {

    /**
     * Construct the adapter
     */
    public function __construct($opts=array());

    /**
     * Enable sandbox API
     *
     * @param string $val
     */
    public function setSandboxMode($val);

    /**
     * Set merchant account.
     *
     * @param string $val
     */
    public function setMerchantAccount($val);

    /**
     * Transform payment fields and build to array
     *
     * @param array $opts
     */
    public function build($opts = array());

    /**
     * Render the HTML payment Form
     *
     * @param array $opts
     */
    public function render($opts = array());

    /**
     * Get invoice return from gateway server
     */
    public function getGatewayInvoice();

    /**
     * Get post frontend result from API gateway
     */
    public function getFrontendResult();

    /**
     * Get post backend result from API gateway
     */
    public function getBackendResult();

}