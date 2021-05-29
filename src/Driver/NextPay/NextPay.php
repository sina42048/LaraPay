<?php

namespace Sina42048\LaraPay\Driver\NextPay;

use Sina42048\LaraPay\Exception\PaymentRequestException;
use Sina42048\LaraPay\Exception\PaymentVerifyException;
use Illuminate\Support\Facades\Request;
use Sina42048\LaraPay\Abstract\Driver;
use Illuminate\Support\Facades\Http;
use Sina42048\LaraPay\LaraRecipt;
use Sina42048\LaraPay\LaraBill;

/**
 * class NextPay
 * @author Sina Fathollahi
 * @package Sina42048\LaraPay\Driver
 */
class NextPay extends Driver{

    /**
     * {@inheritdoc}
     */
    public function pay(callable $func) {
        $response = Http::post($this->config['payment_request_url'], [
            'api_key' => $this->config['api_key'],
            'amount' => $this->bill->getAmount(),
            'order_id' => $this->bill->order_id,
            'customer_phone' => $this->bill->customer_phone ?? '',
            'customer_json_fields' => $this->bill->customer_json_fields ?? '',
            'callback_uri' => $this->config['callback_url']
        ]);

        if ( $response->json()['code'] != -1 ) {
            throw new PaymentRequestException($this->translateErrorMessages($response->json()['code']));
        }
        $this->data['code'] = $response->json()['code'];
        $this->data['trans_id'] = $response->json()['trans_id'];
        $this->data['link'] = $this->config['payment_start_url'] . '/' . $response->json()['trans_id'];

        $this->bill->setTransactionId($response->json()['trans_id']);
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
                $amount = $func(Request::get('trans_id'));
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
            $response = Http::post($this->config['payment_verify_url'], [
                'api_key' => $this->config['api_key'],
                'trans_id' => Request::get('trans_id'),
                'amount' => $this->bill->getAmount()
            ]);

            if ( isset($response->json()['code']) && $response->json()['code'] < -4 ) {
                throw new PaymentVerifyException($this->translateErrorMessages($response->json()['code']));
            }
            
            $recipt = $this->createRecipt( array_merge($response->json(), ['trans_id' => Request::get('trans_id'), 'amount' => $this->bill->getAmount()]) );

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
        $trans_id = Request::get('trans_id');
        return isset($trans_id);
    }


    /**
     * {@inheritdoc}
     */
    protected function createRecipt($reciptData) {
        $recipt = new LaraRecipt();
        $recipt->setTransactionId($reciptData['trans_id']);
        $recipt->amount($reciptData['amount']);
        $recipt->statusCode($reciptData['code']);
        $recipt->order_id = $reciptData['order_id'];
        $recipt->card_holder = $reciptData['card_holder'];
        $recipt->customer_phone = $reciptData['customer_phone'];
        $recipt->custom = $reciptData['custom'];
        return $recipt;
    }

    /**
     * {@inheritdoc}
     */
    protected function translateErrorMessages($errorCode) {

        $errors = [
            '-20'  => 'کد api_key ارسال نشده است',
            '-21' => 'trans_id ارسال نشده است',
            '-22' => 'مبلغ ارسال نشده است',
            '-23' => 'لینک ارسال نشده است',
            '-24' => 'مبلغ صحیح نیست',
            '-25' => 'تراکنش قبلا انجام شده و قابل ارسال نیست',
            '-26' => 'مقدار توکن ارسال نشده است',
            '-27' => 'شماره سفارش صحیح نیست',
            '-28' => 'مقدار فیلد custom_json_field از نوع json نیست',
            '-29' => 'کد بازگشت مبلغ صحیح نیست',
            '-30' => 'مبلغ کمتر از حداقل پرداختی است',
            '-31' => 'صندوق کاربری موجود نیست',
            '-32' => 'مسیر بازگشت صحیح نیست',
            '-33' => 'کلید مجوز دهی صحیح نیست',
            '-34' => 'کد تراکنش صحیح نیست',
            '-35' => 'ساختار کلید مجوز دهی صحیح نیست',
            '-36' => 'شماره سفارش ارسال نشده است',
            '-37' => 'شماره تراکنش یافت نشد',
            '-38' => 'توکن ارسالی موجود نیست',
            '-39' => 'کلید مجوز دهی موجود نیست',
            '-40' => 'کلید مجوز دهی مسدود شده است',
            '-41' => 'خطا در دریافت پارامتر ُ شماره شناسایی صحت اعتبار که از بانک ارسال شده موجود نیست',
            '-42' => 'سیستم پرداخت دچار مشکل شده است',
            '-43' => 'درگاه پرداختی برای انجام درخواست یافت نشد',
            '-44' => 'پاسخ دریافت شده از بانک معتبر نیست',
            '-45' => 'سیستم پرداخت غیر فعال است',
            '-46' => 'درخواست نامعتبر',
            '-47' => 'کلید مجوز دهی یافت نشد',
            '-48' => 'نرخ کمیسیون تعیین نشده است',
            '-49' => 'تراکنش مورد نظر تکراریست',
            '-50' => 'حساب کاربری برای صندوق مالی یافت نشد',
            '-51' => 'شناسه کاربری یافت نشد',
            '-52' => 'حساب کاربری تایید نشده است',
            '-60' => 'ایمیل صحیح نیست',
            '-61' => 'کد ملی صحیح نیست',
            '-62' => 'کد پستی صحیح نیست',
            '-63' => 'آدرس پستی صحیح نیست و یا بیش از ۱۵۰ کارکتر است',
            '-64' => 'توضیحات صحیح نیست یا بیش از ۱۵۰ کارکتر است',
            '-65' => 'نام و نام خانوادگی صحیح نیست یا بیش از ۱۵۰ کارکتر است',
            '-66' => 'تلفن صحیح نیست',
            '-67' => 'نام کاربری صحیح نیست یا بیش از ۳۰ کارکتر است',
            '-68' => 'نام محصول صحیح نیست یا بیش از ۳- کارکتر است',
            '-69' => 'آدرس بازگشتی callback_url صحیح نیست یا بیش از',
            '-70' => 'آدرس بازگشتی callback_url صحیح نیست یا بیش از',
            '-71' => 'موبایل صحیح نیست',
            '-72' => 'بانک پاسخگو نبوده لطفا با پشتیبانی تماس بگیرید',
            '-73' => 'مسیر بازگشت طولانی یا دای خطا میباشد',
            '-90' => 'بازگشت مبلغ به درستی انجام نشد',
            '-91' => 'عملیات ناموفق در بازگشت مبلغ',
            '-92' => 'در عملیات بازگشت مبلغ خطا رخ داده است',
            '-93' => 'موجودی صندوق کاربری برای بازگشت مبلغ کافی نیست',
            '-94' => 'کلید بازگشت مبلغ یافت نشد',
        ];
        return $errors[$errorCode] ?? 'خطای ناشناخته رخ داده است';
    }

    /**
     * {@inheritdoc}
     */
    protected function translateStatusCode($statusCode) {
        $status = [
            '-1' => 'منتظر ارسال تراکنش و ادامه پرداخت',
            '-2' => 'پرداخت رد شده توسط کاربر یا بانک',
            '-3' => 'پرداخت در حال انتظار جواب بانک',
            '-4' => 'پرداخت لغو شده است',
        ];
        return $status[$statusCode] ?? 'خطای ناشناخته رخ داده است';
    }

    /**
     * {@inheritdoc}
     */
    protected function failedPaymentStatusCodes() {
        return [-1, -2, -3, -4];
    }
}