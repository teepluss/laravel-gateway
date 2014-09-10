<?php namespace Teepluss\Gateway\Drivers\TruePaymentApi;

use Illuminate\Support\Collection;

class Product {

    protected $items = array();

    protected $shopId;

    public function setDefaultShopId($shopId)
    {
        $this->shopId = $shopId;

        return $this;
    }

    public function add($item)
    {
        $i = count($this->items) ?: 0;

        $this->items['item'.$i] = $this->sanitilize($item);
    }

    /**
     * Sanitilize.
     *
     * @param  array $params
     * @return object
     */
    public function sanitilize($item)
    {
        $data = array();

        foreach ($item as $k => $v)
        {
            $method = "set".ucfirst($k);

            if (method_exists($this, $method))
            {
                $s = $this->$method($v);

                $data[key($s)] = current($s);
            }
        }

        return $data;
    }

    public function setShopIdRef($val)
    {
        $shopId = (empty($val) or $val == 'inherit') ? $this->shopId : $val;

        return array('shopidref' => $shopId);
    }

    public function setPid($val)
    {
        return array('pid' => $val);
    }

    public function setProductId($val)
    {
        return array('productid' => $val);
    }

    public function setTopic($val)
    {
        return array('topic' => $val);
    }

    public function setQuantity($val)
    {
        return array('quantity' => $val);
    }

    public function setTotalPrice($val)
    {
        $price = number_format($val, 2, '.', '');

        return array('totalPrice' => $price);
    }

    public function setMarginPrice($val)
    {
        return array('margin_price' => $val);
    }

    public function get()
    {
        return new Collection($this->items);
    }


}