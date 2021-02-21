<?php

namespace Teepluss\Gateway\Drivers;

use Teepluss\Gateway\GatewayException;

class TrueMoneyApi extends BaseTrueMoneyApi
{
    public function build($extends = array())
    {
        $payer = $this->payer->get()->toArray();

        $address = $this->address->get()->toArray();

        $payment = $this->payment->get()->toArray();
        $payment['amount']   = $payment['amount'] ?: $this->decimals($this->_amount);
        $payment['currency'] = $payment['currency'] ?: $this->_currency;

        $products = $this->product->get()->toArray();

        $payment['item_list']['items'] = $products;

        $defaults = array(
            'app_id'        => $this->getAppId(),
            'intent'        => 'sale',
            'request_id'    => $this->_invoice,
            'locale'        => null,
            'payer'         => $payer,
            'payment_type'  => 'redirect',
            'redirect_urls' => array(
                'return_url' => $this->_successUrl,
                'cancel_url' => $this->_cancelUrl
            ),
            'billing_address' => $address,
            'payment_info'  => $payment
        );

        $request = array_merge($defaults, $extends);

        // True Money API need to count item price to summary.
        $amount = array_map(function($v) {
            return $v['price'];
        }, $products);

        $summary = array_sum($amount);
        $this->setAmount($summary);

        $request['signature'] = $this->generateSignature($request);

        $requestAsString = json_encode($request);

        $options = array(
            CURLOPT_HTTPHEADER => array(
                'Content-Type: application/json',
                'Content-Length: ' . strlen($requestAsString),
                'Authorization:Bearer ' . $this->getBearer()
            )
        );

        $response = $this->makeRequest($this->_gatewayCreatePaymentUrl, $requestAsString, $options);

        try {
            $uuid = request()->header('client-uuid', time());

            $activity = array_get($_SERVER, 'REQUEST_URI', null);

            $level = 'info';

            $activity_name = 'TMN_CREATE_PAYMENT';

            $activity_message = $requestAsString;
            MakroHelper::log($level, $activity, $activity_name . '_request', $activity_message, $uuid);

            $activity_message = json_encode(array_get($response,'response'));
            MakroHelper::log($level, $activity, $activity_name . '_response', $activity_message, $uuid);
        } catch (\Exception $e) {

        }

        return $response;
    }

    public function render($attrs = array())
    {
        $data = $this->build($attrs);

        $response = json_decode($data['response'], true);

        $response_code = isset($response['result']['response_code']) ? $response['result']['response_code'] : null;

        if (is_null($response_code) || $response_code != 0)
        {
            $message = "TrueMoneyApi:{$response_code}";

            if (isset($response['result']['developer_message'])) {
                $message .= "-{$response['result']['developer_message']}";
            }

            throw new GatewayException($message);
        }

        $paymentId = $response['payment_id'];
        $requestId = $response['request_id'];

        // This process only TrueMoneyApi driver,
        // cause True Money doesn't send invoice.
        $this->setReferenceId($paymentId);

        $this->_gatewayUrl .= '/' . $paymentId . '/process';

        $redirectSignature = $this->hash($paymentId.$requestId);

        $form = $this->makeFormPayment(array(
            'signature' => $redirectSignature
        ));

        return $form;
    }

    public function getGatewayPaymentId()
    {
        if (parent::isBackendPosted())
        {
            return $_POST['payment_id'];
        }

        throw new GatewayException('Gateway invoice return from backend posted only.');
    }

    public function getBackendResult()
    {
        $result = parent::getBackendResult();

        if (! empty($result['data']['dump'])) {
            try {
                $uuid = request()->header('client-uuid', time());

                $activity = array_get($_SERVER, 'REQUEST_URI', null);

                $level = 'info';

                $activity_name = 'TMN_RETRIEVE_PAYMENT';

                $activity_message = json_encode(['payment_id' => $_POST['payment_id']]);
                MakroHelper::log($level, $activity, $activity_name . '_request', $activity_message, $uuid);

                $activity_message = $result['data']['dump'];
                MakroHelper::log($level, $activity, $activity_name . '_response', $activity_message, $uuid);
            } catch (\Exception $e) {

            }
        }

        return $result;
    }

    private function generateSignature($request)
    {
        $compact = $this->getAppId() . $this->getInvoice();

        $items = $request['payment_info']['item_list']['items'];

        for ($i = 0; $i < count($items); $i++)
        {
            $item = $items[$i];

            $compact .= $item['shop_code'] . $item['price'];
        }

        return $this->hash($compact);
    }

    private function hash($string)
    {
        return base64_encode(hash_hmac('sha256', $string, $this->getSecret(), true));
    }
}
