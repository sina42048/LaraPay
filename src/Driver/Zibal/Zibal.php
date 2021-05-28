<?php

namespace Sina42048\LaraPay\Driver\Zibal;

use Sina42048\LaraPay\Exception\PaymentRequestException;
use Sina42048\LaraPay\Exception\PaymentVerifyException;
use Illuminate\Support\Facades\Request;
use Sina42048\LaraPay\Abstract\Driver;
use Illuminate\Support\Facades\Http;
use Sina42048\LaraPay\LaraRecipt;
use Sina42048\LaraPay\LaraBill;

/**
 * class Zibal
 * @author Sina Fathollahi
 * @package Sina42048\LaraPay\Driver
 */
class Zibal extends Driver{

    /**
     * {@inheritdoc}
     */
    public function pay(callable $func) {
        $response = Http::post($this->config['payment_request_url'], [
            'merchant' => $this->config['sand_box'] ? 'zibal' : $this->config['merchant'],
            'amount' => $this->bill->getAmount(),
            'description' => $this->bill->description ?? '',
            'orderId' => $this->bill->orderId ?? '',
            'mobile' => $this->bill->mobile ?? '',
            'email' => $this->bill->email ?? '',
            'linkToPay' => $this->bill->linkToPay ?? '',
            'sms' => $this->bill->sms ?? '',
            'callbackUrl' => $this->config['callback_url']
        ]);

        if ( isset($response->json()['result']) && $response->json()['result'] > 100 || $response->json()['result'] < 100 ) {
            throw new PaymentRequestException($this->translateErrorMessages($response->json()['result']));
        }
        $this->data['trackId'] = $response->json()['trackId'];
        $this->data['message'] = $response->json()['message'];
        $this->data['result'] = $response->json()['message'];
        $this->data['link'] = array_key_exists('payLink',$response->json()) ? $response->json()['payLink'] : $this->config['payment_start_url'] . '/' . $this->data['trackId'];

        $this->bill->setTransactionId($response->json()['trackId']);
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
                $amount = $func(Request::get('trackId'));
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
                'merchant' => $this->config['sand_box'] ? 'zibal' : $this->config['merchant'],
                'trackId' => Request::get('trackId'),
            ]);

            if ($response->status() == 200 && in_array($response->json()['result'], $this->failedPaymentStatusCodes())) {
                throw new PaymentVerifyException($this->translateStatusCode($response->json()['result']));
            }

            $recipt = $this->createRecipt( array_merge($response->json(), ['trackId' => Request::get('trackId'), 'amount' => $this->bill->getAmount()]) );

            return call_user_func($func, $recipt);
        }
        throw new PaymentVerifyException("Bad Request");
    }

    /**
     * {@inheritdoc}
     */
    protected function verifyAccessPermissions()
    {
        $trackId = Request::get('trackId');
        $success = Request::get('success');
        $status = Request::get('status');
        return isset($trackId, $success, $status);
    }


    /**
     * {@inheritdoc}
     */
    protected function createRecipt($reciptData) {
        $recipt = new LaraRecipt();
        $recipt->setTransactionId($reciptData['trackId']);
        $recipt->amount($reciptData['amount']);
        $recipt->statusCode($reciptData['result']);
        $recipt->paid_at = $reciptData['paidAt'];
        $recipt->card_number = $reciptData['cardNumber'];
        $recipt->ref_number = $reciptData['refNumber'];
        $recipt->description = $reciptData['description'];
        $recipt->order_id = $reciptData['orderId'];
        $recipt->result = $reciptData['result'];
        $recipt->message = $reciptData['message'];
        return $recipt;
    }

    /**
     * {@inheritdoc}
     */
    protected function translateErrorMessages($errorCode) {

        $errors = [
            '102' => 'خطای اعتبار سنجی',
            '103' => 'آی پی یا مرچنت پذیرنده صحیح نیست',
            '104' => 'مرچنت کد فعال نیست با پشتیبانی تماس بگیرید',
            '201' => 'تلاش بیش از حد در بازه زمانی کوتاه',
            '105' => 'ترمینال تعلیق شده است با پشتیبانی تماس یگیرید',
            '106' => 'سطح تایید پذیرنده پایین تر از سطح نقره ای است',
            '113' => 'اجازه دسترسی به تسویه اشتراکی را ندارید',
            '-1'  => 'پارامترهای وارد شده صحیح نمی باشد'
        ];
        return $errors[$errorCode] ?? 'خطای ناشناخته رخ داده است';
    }

    /**
     * {@inheritdoc}
     */
    protected function translateStatusCode($statusCode) {
        $status = [
            '102' => 'merchant یافت نشد',
            '103' => 'merchant غیر فعال',
            '104' => 'merchant نامعتبر',
            '201' => 'پرداخت قبلا تایید شده است',
            '202' => 'سفارش پرداخت نشده یا نا موفق بوده است',
            '203' => 'trackId نامعتبر است'
        ];
        return $status[$statusCode] ?? 'خطای ناشناخته رخ داده است';
    }

    /**
     * {@inheritdoc}
     */
    protected function failedPaymentStatusCodes() {
        return [102, 103, 104, 201, 202, 203];
    }
}