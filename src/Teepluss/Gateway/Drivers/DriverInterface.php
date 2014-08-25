<?php namespace Teepluss\Gateway\Drivers;

interface DriverInterface {

    /**
     * Construct the adapter
     */
    public function __construct($opts=array());

    /**
     * Enable sandbox API
     */
    public function setSandboxMode($val);

    /**
     * Get the status sandbox mode
     */
    public function getSandboxMode();

    /**
     * Transform payment fields and build to array
     */
    public function build($opts=array());

    /**
     * Render the HTML payment Form
     */
    public function render($opts=array());

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