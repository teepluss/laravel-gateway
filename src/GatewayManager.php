<?php namespace Teepluss\Gateway;

use Illuminate\Support\Manager;

class GatewayManager extends Manager {

    /**
     * Create an instance of the Paypal driver.
     *
     * @return \Teepluss\Gateway\Drivers\Paypal
     */
    public function createPaypalDriver()
    {
        return $this->repository(new Drivers\Paypal);
    }

    /**
     * Create an instance of the Paysbuy driver.
     *
     * @return \Teepluss\Gateway\Drivers\Paysbuy
     */
    public function createPaysbuyDriver()
    {
        return $this->repository(new Drivers\Paysbuy);
    }

    /**
     * Create an instance of the Paysbuy API driver.
     *
     * @return \Teepluss\Gateway\Drivers\PaysbuyApi
     */
    public function createPaysbuyApiDriver()
    {
        return $this->repository(new Drivers\PaysbuyApi);
    }

    /**
     * Create an instance of the Kbank driver.
     *
     * @return \Teepluss\Gateway\Drivers\Kbank
     */
    public function createKbankDriver()
    {
        return $this->repository(new Drivers\Kbank);
    }

    /**
     * Create an instance of the BBL driver.
     *
     * @return \Teepluss\Gateway\Drivers\Bbl
     */
    public function createBblDriver()
    {
        return $this->repository(new Drivers\Bbl);
    }

    /**
     * Create an instance of the TrueMoney API driver.
     *
     * @return \Teepluss\Gateway\Drivers\TrueMoneyApi
     */
    public function createTrueMoneyApiDriver()
    {
        return $this->repository(new Drivers\TrueMoneyApi);
    }

    /**
     * Create an instance of the TruePayment API driver.
     *
     * @return \Teepluss\Gateway\Drivers\TruePaymentApi
     */
    public function createTruePaymentApiDriver()
    {
        return $this->repository(new Drivers\TruePaymentApi);
    }

    /**
     * Create a new driver repository with the given implementation.
     *
     * @param  \Teepluss\Gateway\Drivers\DriverInterface  $provider
     * @return \Illuminate\Cache\Repository
     */
    protected function repository($provider)
    {
        return new Repository($provider);
    }

    /**
     * Get the default provider driver name.
     *
     * @return string
     */
    public function getDefaultDriver()
    {
        return 'paypal';
    }

}