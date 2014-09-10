<?php namespace Teepluss\Gateway\Drivers\TruePaymentApi;

abstract class ObjectAbstract {

    /**
     * Option intialize.
     *
     * @param  array $params
     * @return object
     */
    public function initialize($params)
    {
        if (count($params))
        {
            foreach ($params as $key => $val)
            {
                $method = "set".ucfirst($key);

                if (method_exists($this, $method))
                {
                    $this->$method($val);
                }
            }
        }

        return $this;
    }

}