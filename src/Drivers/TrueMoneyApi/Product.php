<?php namespace Teepluss\Gateway\Drivers\TrueMoneyApi;

use Illuminate\Support\Collection;

class Product {

    protected $items = array();

    protected $shopCode;

    public function setDefaultShopCode($shopCode)
    {
        $this->shopCode = $shopCode;

        return $this;
    }

    public function add($item)
    {
        $this->items[] = $this->sanitilize($item);
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

    public function setShopCode($val)
    {
        $shopCode = (empty($val) or $val == 'inherit') ? $this->shopCode : $val;

        return array('shop_code' => $shopCode);
    }

    public function setItemId($val)
    {
        return array('item_id' => $val);
    }

    public function setService($val)
    {
        return array('service' => $val);
    }

    public function setProductId($val)
    {
        return array('product_id' => $val);
    }

    public function setDetail($val)
    {
        return array('detail' => $val);
    }

    public function setPrice($val)
    {
        $price = number_format($val, 2, '.', '');

        return array('price' => $price);
    }

    public function setRef1($val)
    {
        return array('ref1' => $val);
    }

    public function setRef2($val)
    {
        return array('ref2' => $val);
    }

    public function setRef3($val)
    {
        return array('ref3' => $val);
    }

    public function get()
    {
        return new Collection($this->items);
    }


}