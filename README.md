## Gateway for Laravel 4

Gateway is payment gateway adapters that support Paypal, Paysbuy, Bangkok Bank, Kasikorn Bank, True Money.

### Installation

- [Gateway on Packagist](https://packagist.org/packages/teepluss/gateway)
- [Gateway on GitHub](https://github.com/teepluss/laravel4-gateway)

To get the lastest version of Gateway simply require it in your `composer.json` file.

~~~
"teepluss/gateway": "dev-master"
~~~

You'll then need to run `composer install` to download it and have the autoloader updated.

Once Theme is installed you need to register the service provider with the application. Open up `app/config/app.php` and find the `providers` key.

~~~
'providers' => array(

    'Teepluss\Gateway\GatewayServiceProvider'

)
~~~

Gateway also ships with a facade which provides the static syntax for creating collections. You can register the facade in the `aliases` key of your `app/config/app.php` file.

~~~
'aliases' => array(

    'Gateway' => 'Teepluss\Gateway\Facades\Gateway'

)
~~~

## Usage

Generate payment form.
~~~php
$adapter = Gateway::driver('Paypal');

$adapter->setSandboxMode(true);

$adapter->setSuccessUrl('http://www.domain/foreground/success')
        ->setCancelUrl('http://www.domain/foreground/cancel')
        ->setBackendUrl('http://www.domain/background/invoice/00001');

$adapter->setMerchantAccount('demo@gmail.com');

$adapter->setLanguage('TH')
        ->setCurrency('THB');

$adapter->setInvoice(00001)
        ->setPurpose('Buy a beer.')
        ->setAmount(100);

$adapter->setRemark('Short note');

$generated = $adapter->render();

var_dump($generated);
~~~

You can use intialize also.
~~~php
$adapter = Gateway::driver('Paypal')->initialize(array(
    'sandboxMode'     => true,
    'successUrl'      => 'http://www.domain/foreground/success',
    'cancelUrl'       => 'http://www.domain/foreground/cancel',
    'backendUrl'      => 'http://www.domain/background/invoice/00001',
    'merchantAccount' => 'seller@domain.to',
    'language'        => 'TH',
    'currency'        => 'THB',
    'invoice'         => uniqid(),
    'purpose'         => 'Buy a beer.',
    'amount'          => 100,
    'remark'          => 'Short note'
));

$generated = $adapter->render();

var_dump($generated);
~~~

How to set TrueMoneyApi and PaysbuyApi
~~~php
// True Money
$gateway = Gateway::driver('TrueMoneyApi');
$gateway->setMerchantAccount('appId:shopCode:secret:bearer');

// Paysbuy
$gateway = Gateway::driver('PaysbuyApi');
$gateway->setMerchantAccount('merchantId:username:secureCode');

// Paysbuy having non-apu version.
$gateway = Gateway::driver('Paysbuy');
$gateway->setMerchantAccount('email');
~~~

Checking foregound process.
~~~php
$adapter = Gateway::driver('Paypal');

$adapter->setSandboxMode(true);

$adapter->setMerchantAccount('seller@domain.to');

$adapter->setInvoice(00001);

$result = $adapter->getFrontendResult();

var_dump($result);
~~~

Checking background process (IPN).
~~~php
$adapter = Gateway::driver('Paypal');

$adapter->setSandboxMode(true);

$adapter->setMerchantAccount('demo@gmail.com');

$adapter->setInvoice(00001);

$result = $adapter->getBackendResult();

var_dump($result);
~~~

Extending the core.
~~~php

use Teepluss\Gateway\Drivers\DriverAbstract;
use Teepluss\Gateway\Drivers\DriverInterface;

class Strip extends DriverAbstract imlements DriverInterface {
    //....
}

use Teepluss\Gateway\Repository;

Gateway::extend('Stripe', function()
{
    return new Repository(new Strip);
});
~~~

## Support or Contact

If you have some problem, Contact teepluss@gmail.com


[![Support via PayPal](https://rawgithub.com/chris---/Donation-Badges/master/paypal.jpeg)](https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=9GEC8J7FAG6JA)
