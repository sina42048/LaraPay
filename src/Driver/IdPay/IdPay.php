<?php

namespace Sina42048\LaraPay\Driver\IdPay;

use Sina42048\LaraPay\Abstract\Driver;
use Illuminate\Support\Facades\Http;
use Sina42048\LaraPay\Exception\PaymentRequestException;

/**
 * class IdPay
 * @author Sina Fathollahi
 * @package Sina42048\LaraPay\Driver\IdPay
 */
class IdPay extends Driver{

    /**
     * {@inheritdoc}
     */
    public function pay() {
        $response = Http::withHeaders([
            'X-API-KEY' => $this->config['api_key'],
            'X-SANDBOX' => $this->config['sand_box'] ? 1 : 0
        ])->post($this->config['payment_request_url'], [
            'order_id' => $this->bill->order_id,
            'amount' => $this->bill->amount,
            'name' => $this->bill->name,
            'phone' => $this->bill->phone,
            'mail' => $this->bill->mail,
            'desc' => $this->bill->desc,
            'callback' => $this->config['callback_url']
        ]);

        if ($response->status() > 201) {
            throw new PaymentRequestException($this->translateErrorMessages($response->status(), $response->json()['error_code']));
        }
        
    }

    /**
     * {@inheritdoc}
     */
    protected function translateErrorMessages($statusCode, $errorCode) {
        $statusCode = (string) $statusCode;
        $errorCode = (string) $errorCode;

        $errors = [
            '403' => [
                '11' => 'کاربر مسدود شده است',
                '12' => 'کلید API وارد شده صحیح نمی باشد',
                '13' => 'IP معتبر نمی باشد',
                '14' => 'وب سرویس در حال بررسی یا تایید نشده است',
                '21' => 'حساب بانکی متصل به وب سرویس تایید نشده است',
                '24' => 'حساب بانکی متصل به سرویس غیر فعال شده است',
            ],
            '404' => [
                '22' => 'وب سرویس یافت نشد',
            ],
            '401' => [
                '23' => 'اعتبار سنجی وب سرویس ناموفق بود'
            ],
            '406' => [
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
            ],
            '405' => [
                '51' => 'تراکنش ایجاد نشد',
                '53' => 'تایید پرداخت امکان پذیر نیست',
                '54' => 'مدت زمان تایید پرداخت سپری شده است'
            ],
            '400' => [
                '52' => 'استعلام نتیجه ای نداشت'
            ]
        ];
        return $errors[$statusCode][$errorCode] ?? 'خطای ناشناخته رخ داده است';
    }
    
}