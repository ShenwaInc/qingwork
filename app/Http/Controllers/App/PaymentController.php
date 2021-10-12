<?php

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use App\Services\PayService;
use App\Services\SettingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PaymentController extends Controller
{
    //支付回调：异步
    public function notify(Request $request, $payment){
        $params = $request->all();
        $result = PayService::notify($payment,$params);
        $params['result'] = $result;
        Log::info('PaymentNotify'.ucfirst($payment),$params);
        if (is_error($result)){
            return $this->message($result['message']);
        }
        if($result){
            return $this->message('支付成功','','success');
        }else{
            return $this->message('支付失败，请重试','','success');
        }
    }

    //支付回调：同步
    public function response(Request $request, $payment){
        $params = $request->all();
        $result =  PayService::notify($payment,$params, 'return');
        if (is_error($result)){
            return $this->message($result['message']);
        }
        if($result){
            return $this->message('支付成功','','success');
        }else{
            return $this->message('支付失败，请重试','','success');
        }
    }

}
