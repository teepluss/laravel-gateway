<?php namespace Teepluss\Gateway\Drivers\TrueMoneyApi;

use Illuminate\Support\Collection;

class Address extends ObjectAbstract {

    public $address = array();

    public function __construct()
    {
        $this->address = array(
            'city_district'  => 'Tumbon',
            'company_name'   => NULL,
            'company_tax_id' => NULL,
            'country'        => 'TH',
            'email'          => 'user@email.com',
            'forename'       => 'FirstName',
            'line1'          => 'Ratchadapisak Rd.',
            'line2'          => NULL,
            'phone'          => NULL,
            'postal_code'    => '10310',
            'state_province' => 'Bangkok',
            'surname'        => 'LastName',
        );
    }

    public function setCityDistrict($val)
    {
        $this->address['city_district'] = $val;

        return $this;
    }

    public function setCompanyName($val)
    {
        $this->address['company_name'] = $val;

        return $this;
    }

    public function setCompanyTaxId($val)
    {
        $this->address['company_tax_id'] = $val;

        return $this;
    }

    public function setCountry($val)
    {
        $this->address['country'] = $val;

        return $this;
    }

    public function setEmail($val)
    {
        $this->address['email'] = $val;

        return $this;
    }

    public function setForename($val)
    {
        $this->address['forename'] = $val;

        return $this;
    }

    public function setLine1($val)
    {
        $this->address['line1'] = $val;

        return $this;
    }

    public function setLine2($val)
    {
        $this->address['line2'] = $val;

        return $this;
    }

    public function setPhone($val)
    {
        $this->address['phone'] = $val;

        return $this;
    }

    public function setPostalCode($val)
    {
        $this->address['postal_code'] = $val;

        return $this;
    }

    public function setStateProvince($val)
    {
        $this->address['state_province'] = $val;

        return $this;
    }

    public function setSurname($val)
    {
        $this->address['surname'] = $val;

        return $this;
    }

    public function get()
    {
        return new Collection($this->address);
    }

}