<?php

namespace Sina42048\LaraPay\Driver\ParsPal;

use Sina42048\LaraPay\Exception\PaymentRequestException;
use Sina42048\LaraPay\Exception\PaymentVerifyException;
use Illuminate\Support\Facades\Request;
use Sina42048\LaraPay\Abstract\Driver;
use Illuminate\Support\Facades\Http;
use Sina42048\LaraPay\LaraRecipt;
use Sina42048\LaraPay\LaraBill;

/**
 * class ParsPal
 * @author Sina Fathollahi
 * @package Sina42048\LaraPay\Driver
 */
class ParsPal extends Driver{

   /**
     * {@inheritdoc}
     */
    public function pay(callable $func) {

        $response = Http::withHeaders([
            'APIKEY' => $this->config['api_key'],
        ])->post($this->config['sand_box'] ? $this->config['payment_sand_box_request_url'] : $this->config['payment_request_url'], [
            'amount' => $this->bill->getAmount(),
            'reserve_id' => $this->bill->reservce_id ?? '',
            'order_id' => $this->bill->order_id ?? '',
            'currency' => $this->bill->currency ?? '',
            'payer' => $this->bill->payer ?? '',
            'description' => $this->bill->description ?? '',
            'default_psp' => $this->bill->default_psp ?? '',
            'return_url' => $this->config['callback_url']
        ]);

        if ($response->status() > 201 && isset($response->json()['error_code'])) {
            throw new PaymentRequestException($this->translateErrorMessages($response->json()['error_code']));
        }
        if ($response->status() > 201 && isset($response->json()['Message'])) {
            throw new PaymentRequestException($response->json()['Message']);
        }
        $this->data['payment_id'] = $response->json()['payment_id'];
        $this->data['message'] = $response->json()['message'];
        $this->data['link'] = $response->json()['link'];
        $this->data['status'] = $response->json()['status'];

        $this->bill->setTransactionId($response->json()['payment_id']);
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
                $amount = $func(Request::post('payment_id'));
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
                'APIKEY' => $this->config['api_key'],
            ])->post($this->config['sand_box'] ? $this->config['payment_sand_box_verify_url'] : $this->config['payment_verify_url'], [
                'amount' => $this->bill->getAmount(),
                'recipt_number' => Request::input('recipt_number'),
            ]);

            if ($response->status() > 201) {
                throw new PaymentRequestException($this->translateErrorMessages($response->json()['error_code']));
            }
            
            $recipt = $this->createRecipt( array_merge($response->json() , ['payment_id' => Request::input('payment_id'), 'amount' => $this->bill->getAmount()] ));

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
        $status = Request::input('status');
        $recipt_number = Request::input('recipt_number');
        $payment_id = Request::input('payment_id');
        return isset($status, $recipt_number, $payment_id, $reserve_id, $order_id);
    }


    /**
     * {@inheritdoc}
     */
    protected function createRecipt($reciptData) {
        $recipt = new LaraRecipt();
        $recipt->setTransactionId($reciptData['payment_id']);
        $recipt->amount($reciptData['amount']);
        $recipt->statusCode($reciptData['status']);
        $recipt->message = $reciptData['message'];
        $recipt->id = $reciptData['id'];
        $recipt->currency = $reciptData['currency'];
        return $recipt;
    }

    /**
     * {@inheritdoc}
     */
    protected function translateErrorMessages($errorCode) {

        $errors = [
            '-2'  => 'توکن شناسایی apikey ارسال نشده است',
            '-1'  => 'توکن شناسایی apikey صحیح نمی باشد',
            '1'   => 'مبلغ ارسالی صحیح نمی باشد',
            '2'   => 'مسیر بازگشتی callback_url صحیح نمی باشد',
            '3' => 'واحد پول ارسالی معتبر نمی باشد',
            '4' => 'آی پی ارسال کننده درخواست معتبر نمی باشد',
            '10' => 'درگاه/حساب غیر فعال است',
            '11' => 'درگاه/حساب مسدود شده است',
            '12' => 'درگاه راه اندازی نشده است',
            '20' => 'در حال حاضر سرویس در دسترس نیست',
            '30' => 'محتوایی برای درخواست ارسال نشده است',
            '99' => 'خطای تعریف نشده',
        ];
        return $errors[$errorCode] ?? 'خطای ناشناخته رخ داده است';
    }

    /**
     * {@inheritdoc}
     */
    protected function translateStatusCode($statusCode) {
        $status = [
            '99' => 'انصراف کاربر از پرداخت',
            '88' => 'پرداخت ناموفق',
            '77' => 'لغو پرداخت توسط کاربر'
        ];
        return $status[$statusCode] ?? 'خطای ناشناخته رخ داده است';
    }

    /**
     * {@inheritdoc}
     */
    protected function failedPaymentStatusCodes() {
        return [99, 88, 77];
    }
    
}