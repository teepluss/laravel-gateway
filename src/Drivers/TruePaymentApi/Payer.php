<?php namespace Teepluss\Gateway\Drivers\TruePaymentApi;

use Illuminate\Support\Collection;

class Payer extends ObjectAbstract {

    public $payer = array();

    public function __construct()
    {
        $this->payer = array(
            'ssoid'     => null,
            'trueid'    => null,
            'fullname'  => null,
            'address'   => null,
            'district'  => null,
            'province'  => null,
            'zip'       => null,
            'country'   => null,
            'mphone'    => null,
            'citizenid' => null
        );
    }

    public function setSsoId($val)
    {
        $this->payer['ssoid'] = $val;

        return $this;
    }

    public function setTrueId($val = null)
    {
        $this->payer['trueid'] = $val;

        return $this;
    }

    public function setFullName($val)
    {
        $this->payer['fullname'] = $val;

        return $this;
    }

    public function setAddress($val)
    {
        $this->payer['address'] = $val;

        return $this;
    }

    public function setDistrict($val)
    {
        $this->payer['district'] = $val;

        return $this;
    }

    public function setProvince($val)
    {
        $this->payer['province'] = $val;

        return $this;
    }

    public function setZip($val)
    {
        $this->payer['zip'] = $val;

        return $this;
    }

    public function setCountry($val)
    {
        $this->payer['country'] = $val;

        return $this;
    }

    public function setMphone($val)
    {
        $this->payer['mphone'] = $val;

        return $this;
    }

    public function setCitizenId($val)
    {
        $this->payer['citizenid'] = $val;

        return $this;
    }

    public function get()
    {
        return new Collection($this->payer);
    }

}