<?php namespace Teepluss\Gateway\Drivers\TrueMoneyApi;

use Illuminate\Support\Collection;

class PayerInfo extends ObjectAbstract {

    public $payerInfo = array();

    public function __construct()
    {
        $this->payerInfo = array(
            'email'     => null,
            'firstname' => null,
            'lastname'  => 'creditcard',
            'payer_id'  => 'CYBS-BAY',
            'phone'     => ''
        );
    }

    public function setEmail($val)
    {
        $this->payerInfo['email'] = $val;

        return $this;
    }

    public function setFirstName($val)
    {
        $this->payerInfo['firstname'] = $val;

        return $this;
    }

    public function setLastName($val)
    {
        $this->payerInfo['lastname'] = $val;

        return $this;
    }

    public function setPayerId($val)
    {
        $this->payerInfo['payer_id'] = $val;

        return $this;
    }

    public function setPhone($val)
    {
        $this->payerInfo['phone'] = $val;

        return $this;
    }

    public function get()
    {
        return new Collection($this->payerInfo);
    }

}