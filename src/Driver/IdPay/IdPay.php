<?php

namespace Sina42048\LaraPay\Driver\IdPay;

use Sina42048\LaraPay\Exception\PaymentRequestException;
use Sina42048\LaraPay\Exception\PaymentVerifyException;
use Illuminate\Support\Facades\Request;
use Sina42048\LaraPay\Abstract\Driver;
use Illuminate\Support\Facades\Http;
use Sina42048\LaraPay\LaraRecipt;
use Sina42048\LaraPay\LaraBill;

/**
 * class IdPay
 * @author Sina Fathollahi
 * @package Sina42048\LaraPay\Driver
 */
class IdPay extends Driver{

    /**
     * {@inheritdoc}
     */
    public function pay(callable $func) {
        $response = Http::withHeaders([
            'X-API-KEY' => $this->config['sand_box'] ? '6a7f99eb-7c20-4412-a972-6dfb7cd253a4' : $this->config['api_key'],
            'X-SANDBOX' => $this->config['sand_box'] ? 1 : 0
        ])->post($this->config['payment_request_url'], [
            'order_id' => $this->bill->order_id,
            'amount' => $this->bill->getAmount(),
            'name' => $this->bill->name ?? '',
            'phone' => $this->bill->phone ?? '',
            'mail' => $this->bill->mail ?? '',
            'desc' => $this->bill->desc ?? '',
            'callback' => $this->config['callback_url']
        ]);

        if ($response->status() > 201) {
            throw new PaymentRequestException($this->translateErrorMessages($response->json()['error_code']));
        }
        $this->data['id'] = $response->json()['id'];
        $this->data['link'] = $response->json()['link'];

        $this->bill->setTransactionId($response->json()['id']);
        if (is_callable($func)) {
            call_user_func($func, $this->bill->getTransactionId(), $this->driverName);
        }
        return $this;
    }
    
    /**
     * {@inheritdoc}
     */
    public function checkAmount(callable $func)
    {
        if (empty($this->bill)) {
            if (is_callable($func)) {
                $amount = $func(Request::input('id'));
                $this->bill = new LaraBill();
                $this->bill->amount($amount);
            }
        }
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function verify(callable $func) {
        if ($this->verifyAccessPermissions()) {
            $response = Http::withHeaders([
                'X-API-KEY' => $this->config['api_key'],
                'X-SANDBOX' => $this->config['sand_box'] ? 1 : 0
            ])->post($this->config['payment_verification_url'], [
                'id' => Request::input('id'),
                'order_id' => Request::input('order_id'),
            ]);

            if ($response->status() > 201) {
                throw new PaymentVerifyException($this->translateErrorMessages($response->json()['error_code']));
            }
            
            $recipt = $this->createRecipt($response->json());
            if ($response->status() == 200 && in_array($recipt->getStatusCode(), $this->failedPaymentStatusCodes())) {
                throw new PaymentVerifyException($this->translateStatusCode($recipt->getStatusCode()));
            }

            return call_user_func($func, $recipt);
        }
        throw new PaymentVerifyException("Bad Request");
    }

    /**
     * {@inheritdoc}
     */
    protected function verifyAccessPermissions()
    {
        $id = Request::input('id');
        $order_id = Request::input('order_id');
        return isset($id, $order_id);
    }

    /**
     * {@inheritdoc}
     */
    protected function createRecipt($reciptData) {
        $recipt = new LaraRecipt();
        $recipt->setTransactionId($reciptData['id']);
        $recipt->amount($reciptData['amount']);
        $recipt->statusCode($reciptData['status']);
        $recipt->track_id = $reciptData['track_id'];
        $recipt->order_id = $reciptData['order_id'];
        $recipt->card_no = $reciptData['card_no'];
        $recipt->hashed_card_no = $reciptData['hashed_card_no'];
        $recipt->date = $reciptData['date'];
        return $recipt;
    }

    /**
     * {@inheritdoc}
     */
    protected function translateErrorMessages($errorCode) {

        $errors = [
            '11' => '?????????? ?????????? ?????? ??????',
            '12' => '???????? API ???????? ?????? ???????? ?????? ????????',
            '13' => 'IP ?????????? ?????? ????????',
            '14' => '???? ?????????? ???? ?????? ?????????? ???? ?????????? ???????? ??????',
            '21' => '???????? ?????????? ???????? ???? ???? ?????????? ?????????? ???????? ??????',
            '24' => '???????? ?????????? ???????? ???? ?????????? ?????? ???????? ?????? ??????',
            '22' => '???? ?????????? ???????? ??????',
            '23' => '???????????? ???????? ???? ?????????? ???????????? ??????',
            '31' => '???? ???????????? id ?????????? ???????? ????????',
            '32' => '?????????? ?????????? order_id ?????????? ???????? ????????',
            '33' => '???????? amount ?????????? ???????? ????????',
            '34' => '???????? ?????????? ???????? ?????? ????????',
            '35' => '???????? ?????????? ???????? ?????? ????????',
            '36' => '???????? amount ?????????? ???? ???? ???????? ??????',
            '37' => '???????? ???????????? callback ?????? ?????????? ???????? ????????',
            '38' => '?????????? ???????? ?????????????? callback ???? ???????? ?????????? ?????????????? ??????????',
            '41' => '?????????? ?????????? ???????????? ???? ???????? ???????????? ???? ???? ?????????? ?????? ???????? ?????????????? ????????',
            '42' => '?????????? ?????????? ???????????? ???????? ?????????? ?????????? ???? ????????',
            '43' => '?????????? ?????????? ?????????? ???????? ?????????? ?????????? ???? ????????',
            '51' => '???????????? ?????????? ??????',
            '53' => '?????????? ???????????? ?????????? ???????? ????????',
            '54' => '?????? ???????? ?????????? ???????????? ???????? ?????? ??????',
            '52' => '?????????????? ?????????? ???? ??????????'
        ];
        return $errors[$errorCode] ?? '???????? ???????????????? ???? ???????? ??????';
    }

    /**
     * {@inheritdoc}
     */
    protected function translateStatusCode($statusCode) {
        $status = [
            '1' => '???????????? ?????????? ???????? ??????',
            '2' => '???????????? ???????????? ???????? ??????',
            '3' => '?????? ???? ???????? ??????',
            '4' => '?????????? ??????',
            '5' => '?????????? ???? ???????????? ??????????',
            '6' => '?????????? ?????????? ????????????',
            '7' => '???????????? ???? ????????????',
            '8' => '???? ?????????? ???????????? ?????????? ????',
            '10' => '???? ???????????? ?????????? ????????????',
            '101' => '???????????? ???????? ?????????? ?????? ??????',
            '200' => '???? ???????????? ?????????? ?????????? ????'
        ];
        return $status[$statusCode] ?? '???????? ???????????????? ???? ???????? ??????';
    }

    /**
     * {@inheritdoc}
     */
    protected function failedPaymentStatusCodes() {
        return [1, 2, 3, 4, 5, 6, 7, 8, 10, 101, 200];
    }
    
}