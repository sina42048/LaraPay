<?php

namespace Sina42048\LaraPay\Driver\IdPay;

use Sina42048\LaraPay\Abstract\Driver;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Request;
use Sina42048\LaraPay\Exception\PaymentRequestException;
use Sina42048\LaraPay\Exception\PaymentVerifyException;
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
            'X-API-KEY' => $this->config['api_key'],
            'X-SANDBOX' => $this->config['sand_box'] ? 1 : 0
        ])->post($this->config['payment_request_url'], [
            'order_id' => $this->bill->order_id,
            'amount' => $this->bill->getAmount(),
            'name' => $this->bill->name,
            'phone' => $this->bill->phone,
            'mail' => $this->bill->mail,
            'desc' => $this->bill->desc,
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
    public function render() {
        return View::make('larapay::payment', [
            'method' => 'POST',
            'inputs' => $this->data,
            'url' => $this->data['link'],
        ]);
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

        call_user_func($func, $recipt);
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
            '11' => 'کاربر مسدود شده است',
            '12' => 'کلید API وارد شده صحیح نمی باشد',
            '13' => 'IP معتبر نمی باشد',
            '14' => 'وب سرویس در حال بررسی یا تایید نشده است',
            '21' => 'حساب بانکی متصل به وب سرویس تایید نشده است',
            '24' => 'حساب بانکی متصل به سرویس غیر فعال شده است',
            '22' => 'وب سرویس یافت نشد',
            '23' => 'اعتبار سنجی وب سرویس ناموفق بود',
            '31' => 'کد تراکنش id نباید خالی باشد',
            '32' => 'شماره سفارش order_id نباید خالی باشد',
            '33' => 'مبلغ amount نباید خالی باشد',
            '34' => 'مبلع سفارش صحیح نمی باشد',
            '35' => 'مبلغ سفارش صحیح نمی باشد',
            '36' => 'مبلغ amount بیشتر از حد مجاز است',
            '37' => 'آدرس بازگشت callback نمی تواند خالی باشد',
            '38' => 'دامنه آدرس بازگشتی callback با آدرس دامین همخوانی ندارد',
            '41' => 'فیلتر وضعیت تراکنش ها باید آرایاه ای از وضعیت های مجاز مستندات باشد',
            '42' => 'فیلتر تاریخ پرداخت باید بصورت آرایه ای باشد',
            '43' => 'فیلتر تاریخ تسویه باید بصورت آرایه ای باشد',
            '51' => 'تراکنش ایجاد نشد',
            '53' => 'تایید پرداخت امکان پذیر نیست',
            '54' => 'مدت زمان تایید پرداخت سپری شده است',
            '52' => 'استعلام نتیجه ای نداشت'
        ];
        return $errors[$errorCode] ?? 'خطای ناشناخته رخ داده است';
    }

    /**
     * {@inheritdoc}
     */
    protected function translateStatusCode($statusCode) {
        $status = [
            '1' => 'پرداخت انجام نشده است',
            '2' => 'پرداخت ناموفق بوده است',
            '3' => 'حطا رخ داده است',
            '4' => 'بلوکه شده',
            '5' => 'برگشت به پرداخت کننده',
            '6' => 'برگشت خورده سیستمی',
            '7' => 'انصراف از پرداخت',
            '8' => 'به درگاه پرداخت منتقل شد',
            '10' => 'در انتظار تایید پرداخت',
            '100' => 'پرداخت تایید شده است',
            '101' => 'پرداخت قبلا تایید شده است',
            '200' => 'به دریافت کننده واریز شد'
        ];
        return $status[$statusCode] ?? 'خطای ناشناخته رخ داده است';
    }

    /**
     * {@inheritdoc}
     */
    protected function failedPaymentStatusCodes() {
        return [1, 2, 3, 4, 5, 6, 7, 8, 10, 101, 200];
    }
    
}