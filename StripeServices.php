<?php

namespace App\Services;

use Exception;
use Stripe\Exception\ApiErrorException;
use Stripe\Exception\AuthenticationException;
use Stripe\Exception\CardException;
use Stripe\Exception\InvalidRequestException;
use Stripe\Exception\RateLimitException;
use Stripe\Stripe;
use Stripe\StripeClient;

class StripeServices{

    private $stripe;
    public function __construct()
    {
        $this->stripe = new StripeClient(config('services.stripe.secret'));
        Stripe::setApiKey(config('services.stripe.secret'));
    }
    /*
        --- Card Example ---
        'card' =>
        [
            'number' => '4242424242424242',
            'exp_month' => '5',
            'exp_year'=>'2024',
            'cvc' => '314'
        ]
     */
    public function pay($card,$amount,$currency="AED",$description="First Test Charge")
    {
        try{
            $response = $this->stripe->tokens->create([
                'card' => $card,
            ]);

           $response =  $this->stripe->charges->create([
                'amount' => $amount * 100,
                'currency' => $currency,
                'source' => $response->id,
                'description' => $description,
            ]);
            return [
                'status' => true,
                'data' => $response,
                'message' => [
                    'en' => "pay operation Succeeded ",
                    'ar' => "نجحت عملية الدفع"
                ],
                'error' => ''
            ];
            
        }
        catch(CardException $e) {
            $error = [
                'status' => 'Status is:' . $e->getHttpStatus(),
                'type' => 'Type is:' . $e->getError()->type,
                'code' => 'Code is:' . $e->getError()->code,
                'params' => 'Param is:' . $e->getError()->param,
                'message' => 'Message is:' . $e->getError()->message,
            ];

            return [
                'status' => false,
                'data' => [],
                'message' => [
                    'en' => "Invalid Card",
                    'ar' => "بطاقة غير صالحة"
                ],
                'error' => $error
            ];
        } catch (RateLimitException $ex) {
            return [
                'status' => false,
                'data' => [],
                'message' => [
                    'en' => "Too many requests made to the API too quickly",
                    'ar' => "تم تقديم طلبات كثيرة جدًا إلى واجهة برمجة التطبيقات بسرعة كبيرة جدًا"
                ],
                'error' => $ex
            ];
        } catch (InvalidRequestException $ex) {
            return [
                'status' => false,
                'data' => [],
                'message' => [
                    'en' => "Invalid parameters were supplied to Stripe's API",
                    'ar' => "تم توفير معلمات غير صالحة لـ Stripe's API"
                ],
                'error' => $ex
            ];
        } catch (AuthenticationException $ex) {
            return [
                'status' => false,
                'data' => [],
                'message' => [
                    'en' => "Authentication with Stripe's API failed (maybe you changed API keys recently)",
                    'ar' => "فشلت المصادقة مع Stripe's API (ربما قمت بتغيير مفاتيح API مؤخرًا)"
                ],
                'error' => $ex
            ];
        } catch (\Stripe\Exception\ApiConnectionException $ex) {
            return [
                'status' => false,
                'data' => [],
                'message' => [
                    'en' => "Network communication with Stripe failed",
                    'ar' => "فشل اتصال الشبكة مع Stripe"
                ],
                'error' => $ex
            ];
        } catch (ApiErrorException $ex) {
            return [
                'status' => false,
                'data' => [],
                'message' => [
                    'en' => "Display a very generic error to the user, and maybe send",
                    'ar' => "عرض خطأ عام للغاية للمستخدم ، وربما إرسال"
                ],
                'error' => $ex
            ];
        } catch (Exception $ex) {
            return [
                'status' => false,
                'data' => [],
                'message' => [
                    'en' => "Something happened Error",
                    'ar' => "حدث شيء خطأ"
                ],
                'error' => $ex
            ];
        }
    }

    public function retriveAllCharges($limit = 3,$take= 0)
    {
        try{
            $data = ['limit' => $limit];

            if($take != 0){
                $data['starting_after'] = $take;
            }
            $response = $this->stripe->charges->all($data);
            return [
                'status' => true,
                'data' =>$response
            ];
            return [
                'status' => true,
                'data' => $response,
                'message' => [
                    'en' => "retrieve All Charges operation Succeeded ",
                    'ar' => "استرجاع جميع عمليات التحويل بنجاح"
                ],
                'error' => ''
            ];
        }
        catch(Exception $ex){
            return [
                'status' => false,
                'data' => [],
                'message' => [
                    'en' => "Something happened Error",
                    'ar' => "حدث شيء خطأ"
                ],
                'error' => $ex
            ];
        }
    }

    public function retrivePayByChargeID($chargeID)
    {
        try{
            $response = $this->stripe->charges->retrieve($chargeID);

            return [
                'status' => true,
                'data' => $response,
                'message' => [
                    'en' => "retrieve Pay By Charge ID operation Succeeded ",
                    'ar' => "استرجاع الدفع بواسطة معرّف المصاريف بنجاح"
                ],
                'error' => ''
            ];
        }
        catch(Exception $ex){
            return [
                'status' => false,
                'data' => [],
                'message' => [
                    'en' => "Something happened Error",
                    'ar' => "حدث شيء خطأ"
                ],
                'error' => $ex
            ];
        }
    }

    public function refund($chargeID)
    {
        try{
            $response =  $this->stripe->refunds->create(['charge' => $chargeID]);

            return [
                'status' => true,
                'data' => $response,
                'message' => [
                    'en' => "refund operation Succeeded ",
                    'ar' => "نجحت عملية الاسترداد"
                ],
                'error' => ''
            ];

        }
        catch(Exception $ex){
            return [
                'status' => false,
                'data' => [],
                'message' => [
                    'en' => "Something happened Error",
                    'ar' => "حدث شيء خطأ"
                ],
                'error' => $ex
            ];
        }
    }

    public function refundList($limit=3,$take=0)
    {
        try{
            $data = ['limit' => $limit];

            if($take != 0){
                $data['starting_after'] = $take;
            }
            $response = $this->stripe->refunds->all($data);

            return [
                'status' => true,
                'data' => $response,
                'message' => [
                    'en' => "Getting all Refund List Succeeded ",
                    'ar' => "نجحت عملية الحصول على قائمة الاسترداد بالكامل"
                ],
                'error' => ''
            ];
        }
        catch(Exception $ex){
            return [
                'status' => false,
                'data' => [],
                'message' => [
                    'en' => "Something happened Error",
                    'ar' => "حدث شيء خطأ"
                ],
                'error' => $ex
            ];
        }

    }

}





?>
