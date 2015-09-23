<?php namespace Teepluss\Gateway\Drivers\TrueMoneyApi;

use Illuminate\Support\Collection;

class Payment extends ObjectAbstract {

    public $payment = array();

    public function __construct()
    {
        $this->payment = array(
            'amount'    => null,
            'currency'  => null,
            'item_list' => array(),
            'ref1'      => '',
            'ref2'      => '',
            'ref3'      => ''
        );
    }

    public function setAmount($val, $decimals = 2)
    {
        $this->payment['amount'] = number_format($val, $decimals, '.', '');

        return $this;
    }

    public function setCurrency($val)
    {
        $this->payment['currency'] = $val;

        return $this;
    }

    public function setRef1($val)
    {
        $this->payment['ref1'] = $val;

        return $this;
    }

    public function setRef2($val)
    {
        $this->payment['ref2'] = $val;

        return $this;
    }

    public function setRef3($val)
    {
        $this->payment['ref3'] = $val;

        return $this;
    }

    public function get()
    {
        return new Collection($this->payment);
    }

}