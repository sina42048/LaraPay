<?php

namespace Sina42048\LaraPay\Driver\ZarinPal;

use Sina42048\LaraPay\Exception\PaymentRequestException;
use Sina42048\LaraPay\Exception\PaymentVerifyException;
use Illuminate\Support\Facades\Request;
use Sina42048\LaraPay\Abstract\Driver;
use Illuminate\Support\Facades\Http;
use Sina42048\LaraPay\LaraRecipt;
use Sina42048\LaraPay\LaraBill;

/**
 * class ZarinPal
 * @author Sina Fathollahi
 * @package Sina42048\LaraPay\Driver
 */
class ZarinPal extends Driver{

    /**
     * {@inheritdoc}
     */
    public function pay(callable $func) {
        $response = Http::post($this->config['payment_request_url'], [
            'merchant_id' => $this->config['sand_box'] ? '4ced0a1e-4ad8-4309-9668-3ea3ae8e8897' : $this->config['merchant_id'],
            'amount' => $this->bill->getAmount(),
            'description' => $this->bill->description ?? '',
            'metadata' => $this->bill->metadata ?? null,
            'mobile' => $this->bill->mobile ?? '',
            'email' => $this->bill->email ?? '',
            'callback_url' => $this->config['callback_url']
        ]);

        if ( isset($response->json()['errors'], $response->json()['errors']['code']) ) {
            throw new PaymentRequestException($this->translateErrorMessages($response->json()['errors']['code']));
        }
        $this->data['code'] = $response->json()['data']['code'];
        $this->data['message'] = $response->json()['data']['message'];
        $this->data['link'] = $this->config['payment_start_url'] . '/' . $response->json()['data']['authority'];

        $this->bill->setTransactionId($response->json()['data']['authority']);
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
                $amount = $func(Request::get('Authority'));
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
                'merchant_id' => $this->config['merchant_id'],
                'amount' => $this->bill->getAmount(),
                'authority' => Request::get('Authority')
            ]);

            if ( isset($response->json()['errors'], $response->json()['errors']['code']) ) {
                throw new PaymentVerifyException($this->translateErrorMessages($response->json()['errors']['code']));
            }
            
            $recipt = $this->createRecipt( array_merge($response->json(), ['Authority' => Request::get('Authority'), 'amount' => $this->bill->getAmount()]) );

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
        $authority = Request::get('Authority');
        return isset($authority);
    }


    /**
     * {@inheritdoc}
     */
    protected function createRecipt($reciptData) {
        $recipt = new LaraRecipt();
        $recipt->setTransactionId($reciptData['Authority']);
        $recipt->amount($reciptData['amount']);
        $recipt->statusCode($reciptData['data']['code']);
        $recipt->card_hash = $reciptData['data']['card_hash'];
        $recipt->card_pan = $reciptData['data']['card_pan'];
        $recipt->ref_id = $reciptData['data']['ref_id'];
        $recipt->fee_type = $reciptData['data']['fee_type'];
        $recipt->fee = $reciptData['data']['fee'];
        return $recipt;
    }

    /**
     * {@inheritdoc}
     */
    protected function translateErrorMessages($errorCode) {

        $errors = [
            '-9'  => 'خطای اعتبار سنجی',
            '-10' => 'آی پی یا مرچنت پذیرنده صحیح نیست',
            '-11' => 'مرچنت کد فعال نیست با پشتیبانی تماس بگیرید',
            '-12' => 'تلاش بیش از حد در بازه زمانی کوتاه',
            '-15' => 'ترمینال تعلیق شده است با پشتیبانی تماس یگیرید',
            '-16' => 'سطح تایید پذیرنده پایین تر از سطح نقره ای است',
            '-30' => 'اجازه دسترسی به تسویه اشتراکی را ندارید',
            '-31' => 'حساب بانکی تسویه را به پنل اضافه کنید مقادیر وارد شده برای تسهیم درست نیست',
            '-32' => 'دستمزد شناور معتبر نیست',
            '-33' => 'درصد های وارد شده درست نیست',
            '-34' => 'مبلغ از کل تراکنش بیشتر است',
            '-35' => 'تعداد افراد دریافت کننده تسهیم بیش از حد مجاز است',
            '-40' => 'پارامترها اشتباه ارسال شده اند',
            '-50' => 'مبلغ پرداخت شده با مقدار مبلغ در وریفای متفاوت است',
            '-51' => 'پرداخت ناموفق',
            '-52' => 'خطای غیر منتظره رخ داده است',
            '-53' => 'اتوریتی برای این مرچنت کد نیست',
            '-54' => 'اتوریتی نامعنبر است',
        ];
        return $errors[$errorCode] ?? 'خطای ناشناخته رخ داده است';
    }

    /**
     * {@inheritdoc}
     */
    protected function translateStatusCode($statusCode) {
        $status = [
            '101' => 'پرداخت قبلا تایید شده است'
        ];
        return $status[$statusCode] ?? 'خطای ناشناخته رخ داده است';
    }

    /**
     * {@inheritdoc}
     */
    protected function failedPaymentStatusCodes() {
        return [101];
    }
}