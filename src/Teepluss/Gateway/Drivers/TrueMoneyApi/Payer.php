<?php namespace Teepluss\Gateway\Drivers\TrueMoneyApi;

use Illuminate\Support\Collection;

class Payer extends ObjectAbstract {

    public $payer = array();

    //private $payerInfo;

    public function __construct()
    {
        $this->payerInfo = new PayerInfo();

        $this->payer = array(
            'funding_instrument' => null,
            'installment'        => null,
            'payment_method'     => 'creditcard',
            'payment_processor'  => 'CYBS-BAY',
            'payer_info'         => $this->payerInfo->get()
        );
    }

    public function setFundingInstrument($val)
    {
        $this->payer['funding_instrument'] = $val;

        return $this;
    }

    public function setInstallment($val = null)
    {
        $this->payer['installment'] = $val;

        return $this;
    }

    public function setPaymentMethod($val)
    {
        $this->payer['payment_method'] = $val;

        return $this;
    }

    public function setPaymentProcesser($val)
    {
        $this->payer['payment_processor'] = $val;

        return $this;
    }

    public function setPayerInfo($val = array())
    {
        $this->payer['payer_info'] = $this->payerInfo->initialize($val)->get();

        return $this;
    }

    public function get()
    {
        return new Collection($this->payer);
    }

}