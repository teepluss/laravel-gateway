<?php namespace Teepluss\Gateway;

use Illuminate\Support\Traits\MacroableTrait;
use Teepluss\Gateway\Drivers\DriverInterface;

class Repository {

    use MacroableTrait {
        __call as macroCall;
    }

    /**
     * The provider implementation.
     *
     * @var \Teepluss\Gateway
     */
    protected $provider;

    /**
     * Create a new provider repository instance.
     *
     * @param  \Illuminate\Cache\StoreInterface  $store
     */
    public function __construct(DriverInterface $provider)
    {
        $this->provider = $provider;
    }

    /**
     * Handle dynamic calls into macros or pass missing methods to the provider.
     *
     * @param  string  $method
     * @param  array   $parameters
     * @return mixed
     */
    public function __call($method, $parameters)
    {
        if (static::hasMacro($method))
        {
            return $this->macroCall($method, $parameters);
        }
        else
        {
            return call_user_func_array(array($this->provider, $method), $parameters);
        }
    }

}