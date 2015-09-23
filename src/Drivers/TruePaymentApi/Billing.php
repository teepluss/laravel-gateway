<?php namespace Teepluss\Gateway\Drivers\TruePaymentApi;

use Illuminate\Support\Collection;

class Billing extends ObjectAbstract {

    public $billing = array();

    public function __construct()
    {
        $this->billing = array(
            'fullname' => null,
            'address'  => null,
            'district' => null,
            'province' => null,
            'zip'      => null,
            'country'  => null
        );
    }

    public function setFullName($val)
    {
        $this->billing['fullname'] = $val;

        return $this;
    }

    public function setAddress($val)
    {
        $this->billing['address'] = $val;

        return $this;
    }

    public function setDistrict($val)
    {
        $this->billing['district'] = $val;

        return $this;
    }

    public function setProvince($val)
    {
        $this->billing['province'] = $val;

        return $this;
    }

    public function setZip($val)
    {
        $this->billing['zip'] = $val;

        return $this;
    }

    public function setCountry($val)
    {
        $this->billing['country'] = $val;

        return $this;
    }

    public function get()
    {
        return new Collection($this->billing);
    }

}