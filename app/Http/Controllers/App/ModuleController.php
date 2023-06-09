<?php

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use App\Utils\WeModule;
use Illuminate\Http\Request;

class ModuleController extends Controller
{
    //

    public function entry(Request $request, $moduleName, $do='index'){
        $WeModule = new WeModule();
        try {
            $site = $WeModule->create($moduleName);
        }catch (\Exception $exception){
            return $this->message('模块初始化失败，请联系技术处理');
        }
        $method = "doMobile" . ucfirst($do);
        if (!method_exists($site,$method)){
            return $this->message("模块不支持{$method}()方法");
        }
        return $site->$method($request);
    }

    public function Api(Request $request, $moduleName){
        define('IN_API', true);
        global $_W;
        $_W['isapi'] = true;
        $WeModule = new WeModule();
        //判断模块权限，待完善
        try {
            $site = $WeModule->create($moduleName);
        }catch (\Exception $exception){
            return $this->message('模块初始化失败，请联系技术处理');
        }
        $method = "doMobileApi";
        if (!method_exists($site,$method)){
            return $this->message("模块不支持$method()方法");
        }
        return $site->$method($request);
    }

}
