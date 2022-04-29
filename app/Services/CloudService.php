<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class CloudService
{

    static $identity = 'swa_framework_laravel';
    static $cloudapi = 'https://chat.gxit.org/app/index.php?i=4&c=entry&m=swa_supersale&do=api';
    static $cloudactive = 'https://chat.gxit.org/app/index.php?i=4&c=entry&m=swa_supersale&do=app&r=whotalkcloud.active&siteroot=';
    static $apilist = array('getcom'=>'cloud.vendor','rmcom'=>'cloud.vendor.remove','require'=>'cloud.install','structure'=>'cloud.structure','upgrade'=>'cloud.makepatch');
    static $vendors = array('aliyun'=>'阿里短信SDK','aop'=>'支付宝支付SDK','wxpayv3'=>'微信支付SDK','tim'=>'接口签名验证工具','getui'=>'APP推送SDK');

    static function ComExists($component){
        return is_dir(self::com_path("{$component}/"));
    }

    static function LoadCom($component){
        if (!self::ComExists($component)) return error(-1,'未安装对应组件:'.self::$vendors[$component]);
        $mainclass = array('aliyun'=>'SmsDemo','aop'=>'AopClient','wxpayv3'=>'WxPayApi','tim'=>'TLSSigAPIv2','getui'=>'IGeTui');
        if (class_exists($mainclass[$component])) return true;
        $compath = self::com_path();
        switch ($component){
            case 'wxpayv3' :
                require_once "{$compath}wxpayv3/WxPay.Api.php";
                require_once "{$compath}wxpayv3/WxPay.Data.php";
                break;
            case 'alipay' :
                require("{$compath}alipay/wappay/service/AlipayTradeService.php");
                require_once("{$compath}alipay/wappay/buildermodel/AlipayTradeWapPayContentBuilder.php");
                break;
            case 'aop' :
                require_once "{$compath}aop/AopClient.php";
                require_once "{$compath}aop/request/AlipayTradeQueryRequest.php";
                break;
            case 'aliyun' :
                require_once "{$compath}aliyun/src/dysmsapi.php";
                require_once "{$compath}aliyun/vendor/autoload.php";
                break;
            case 'tim' :
                include_once "{$compath}tim/TLSSigAPIv2.php";
                break;
            case 'getui' :
                require_once "{$compath}getui/autoload.php";
                break;
            default :
                break;
        }
        return true;
    }

    static function RequireCom(){
        $hasCom = self::ComExists('aliyun');
        if ($hasCom){
            return self::CloudUpdate('swa_whotalk_componet',self::com_path());
        }else{
            $requirecom = self::CloudRequire('swa_whotalk_componet',self::com_path());
            if (!is_error($requirecom)){
                //组件包下载标记
                DB::table('gxswa_cloud')->updateOrInsert(array(
                        'identity'=>'swa_whotalk_componet'
                    ),array(
                        'name'=>'Whotalk国际版依赖包',
                        'modulename'=>'',
                        'type'=>2,
                        'logo'=>'https://shenwahuanan.oss-cn-shenzhen.aliyuncs.com/images/4/2021/08/Mpar00P5PjJPrxAW1FWCP3CPz87qjc.png',
                        'website'=>'https://www.whotalk.com.cn/',
                        'version'=>'1.0.4',
                        'releasedate'=>2021090401,
                        'rootpath'=>'',
                        'online'=>'',
                        'addtime'=>TIMESTAMP,
                        'dateline'=>TIMESTAMP
                    ));
            }
            return $requirecom;
        }
    }

    static function RequireModule($identity,$path='addons'){
        $moduleName = str_replace("laravel_module_", "", $identity);
        $targetpath = base_path("public/$path/$moduleName");
        $from = 'local';
        if (!is_dir($targetpath)){
            $result = self::CloudRequire($identity,$targetpath);
            if(is_error($result)) return $result;
            $from = 'cloud';
        }
        //进入模块安装流程
        return ModuleService::install($moduleName,$path,$from);
    }

    static function com_path($path=""){
        global $_W;
        if (!isset($_W['com_path'])){
            $compath = substr(sha1($_W['config']['setting']['authkey']."-".$_W['config']['site']['id']),5,6);
            $_W['com_path'] = app_path("com$compath/");
        }
        return $_W['com_path'] . $path;
    }

    static function getPlugins(){
        $plugins = [];
        $condition = array('type'=>1);
        //获取已安装模块
        $components = DB::table('gxswa_cloud')->where($condition)->orderByRaw("`id` desc")->get()->toArray();
        if (!empty($components)){
            foreach ($components as $com){
                $com['logo'] = asset($com['logo']);
                $com['lastupdate'] = $com['updatetime'] ? date('Y/m/d H:i',$com['updatetime']) : '初始安装';
                $com['cloudinfo'] = !empty($com['online']) ? unserialize($com['online']) : array();
                $com['installtime'] = date('Y/m/d H:i',$com['addtime']);
                $com['action'] = '<div class="layui-btn-group">';
                if (!empty($com['cloudinfo']) && $com['cloudinfo']['isnew']){
                    $com['action'] .= '<a href="'.url('console/setting/comupdate').'?cid='.$com['id'].'" class="layui-btn layui-btn-sm layui-btn-danger confirm" data-text="升级前请做好源码和数据备份，避免升级故障导致系统无法正常运行">升级</a>';
                }
                $com['action'] .= '<a href="'.url('console/setting/comcheck').'?cid='.$com['id'].'" class="layui-btn layui-btn-sm layui-btn-normal ajaxshow">'.(empty($com['cloudinfo']) ? '检测更新' : '重新检测').'</a>';
                $com['action'] .= '<a href="'.url('console/setting/comremove').'?cid='.$com['id'].'" class="layui-btn layui-btn-sm layui-btn-primary confirm" data-text="即将卸载该应用并删除应用产生的所有数据，是否确定要卸载？">卸载</a></div>';
                $plugins[$com['modulename']] = $com;
            }
        }
        //获取本地未安装模块
        if (DEVELOPMENT){
            $modules = FileService::file_tree(public_path('addons'), array('*/Manifest.php'));
            if (!empty($modules)){
                foreach ($modules as $value){
                    $identity = str_replace(array(public_path('addons/'),"/Manifest.php"),'', $value);
                    if (empty($identity) || isset($plugins[$identity])) continue;
                    $className = ucfirst($identity)."_Manifest";
                    $ManiFest = require_once $value;
                    $com = $ManiFest->application;
                    $com['logo'] = asset($com['logo']);
                    $com['website'] = $com['url'];
                    $com['cloudinfo'] = array();
                    $com['addtime'] = 0;
                    if ($ManiFest->installed){
                        $com['installtime'] = '本地安装';
                        $com['lastupdate'] = '-';
                        $com['action'] = '<a href="'.url('console/setting/pluginrm').'?nid='.$identity.'" class="layui-btn layui-btn-sm layui-btn-primary confirm" data-text="即将卸载该应用并删除应用产生的所有数据，是否确定要卸载？">卸载</a></div>';
                    }else{
                        $com['installtime'] = '-';
                        $com['lastupdate'] = '<span class="layui-badge">未安装</span>';
                        $com['action'] = '<a href="'.url('console/setting/plugininst').'?nid='.$identity.'" class="layui-btn layui-btn-sm layui-btn-normal confirm" data-text="确定要安装该应用？">安装</a>';
                    }
                    $plugins[$identity] = $com;
                }
            }
        }
        //获取云端未安装组件
        $cachekey = "cloud:module_list:1";
        $res = Cache::get($cachekey, array());
        if (empty($res)){
            $data = array(
                'r'=>'cloud.packages',
                'pidentity'=>self::$identity,
                'page'=>1,
                'category'=>1
            );
            $res = CloudService::CloudApi("", $data);
            Cache::put($cachekey, $res, 600);
        }
        if (!is_error($res) && !empty($res['servers'])){
            foreach ($res['servers'] as $value){
                $identifie = str_replace("laravel_module_", "", $value['identity']);
                if (empty($identifie)) continue;
                $releaseDate = intval($value['release']['releasedate']);
                if (isset($plugins[$identifie])){
                    $local = $plugins[$identifie];
                    if ($local['addtime']==0) continue;
                    if (version_compare($local['version'], $value['release']['version'], '>=') && $local['releasedate']>=$releaseDate){
                        continue;
                    }
                    if (empty($local['cloudinfo']) || !$local['cloudinfo']['isnew']){
                        $local['action'] = '<a href="'.url('console/setting/cloudUp').'?nid='.$identifie.'" class="layui-btn layui-btn-sm layui-btn-danger confirm" data-text="升级前请做好源码和数据备份，避免升级故障导致系统无法正常运行">升级</a>'.$local['action'];
                    }
                    $local['cloudinfo'] = array('isnew'=>true,'version'=>$value['release']['version'],'releasedate'=>$releaseDate);
                    $plugins[$identifie] = $local;
                }else{
                    $com = array(
                        'id'=>0,
                        'name'=>$value['name'],
                        'identifie'=>$identifie,
                        'version'=>$value['release']['version'],
                        'releasedate'=>$releaseDate,
                        'ability'=>$value['name'],
                        'description'=>$value['summary'],
                        'author'=>$value['author'],
                        'website'=>$value['website'],
                        'logo'=>$value['icon']
                    );
                    $com['lastupdate'] = '<span class="layui-badge">未安装</span>';
                    $com['cloudinfo'] = array();
                    $com['installtime'] = '-';
                    $com['action'] = '<a href="'.url('console/setting/cloudinst').'?nid='.$value['identity'].'" class="layui-btn layui-btn-sm layui-btn-normal confirm" data-text="确定要安装该应用？">安装</a>';
                    $plugins[$identifie] = $com;
                }
            }
        }
        return $plugins;
    }

    static function MoveDir($oldDir, $aimDir, $overWrite = false){
        $aimDir = str_replace('', '/', $aimDir);
        $aimDir = substr($aimDir, -1) == '/' ? $aimDir : $aimDir . '/';
        $oldDir = str_replace('', '/', $oldDir);
        $oldDir = substr($oldDir, -1) == '/' ? $oldDir : $oldDir . '/';
        if (!is_dir($oldDir)) {
            return false;
        }
        if (!file_exists($aimDir)) {
            FileService::mkdirs($aimDir);
        }
        @ $dirHandle = opendir($oldDir);
        if (!$dirHandle) {
            return false;
        }
        while (false !== ($file = readdir($dirHandle))) {
            if ($file == '.' || $file == '..') {
                continue;
            }
            if (!is_dir($oldDir . $file)) {
                FileService::file_move($oldDir . $file,$aimDir . $file);
            } else {
                self::MoveDir($oldDir . $file, $aimDir . $file, $overWrite);
            }
        }
        closedir($dirHandle);
        return FileService::rmdirs($oldDir);
    }

    static function CloudRequire($identity,$targetpath,$patch=''){
        $data = array(
            'identity'=>$identity,
            'fp'=>self::$identity
        );
        $zipcontent = self::CloudApi('require',$data,true);
        if (is_error($zipcontent)) return $zipcontent;
        if (!$zipcontent) return error(-1,'安装包提取失败');
        if (!$patch){
            $patch = base_path("storage/patch/");
        }
        if (!is_dir($patch)){
            FileService::mkdirs($patch);
        }
        $filename = FileService::file_random_name($patch,'zip');
        if (false == file_put_contents($patch.$filename, $zipcontent)) {
            return error(-1,'安装包解压失败：权限不足');
        }

        $zip = new \ZipArchive();
        $openRes = $zip->open($patch.$filename);
        if ($openRes === TRUE) {
            $zip->extractTo($targetpath);
            $zip->close();
            //删除补丁包
            @unlink($patch.$filename);
        }else{
            @unlink($patch.$filename);
            return error(-1,'安装包解压失败，请重试');
        }

        //如果解压包内嵌则操作搬移
        if (is_dir($targetpath.$identity.'/')){
            if (is_dir($patch.$identity)){
                FileService::rmdirs($patch.$identity);
            }
            self::MoveDir($targetpath.$identity,$patch);
            self::CloudPatch($targetpath,$patch.$identity.'/',true);
            FileService::rmdirs($patch.$identity, true);
        }
        return true;
    }

    static function CloudUpdate($identity,$targetpath,$patch=''){
        $data = array(
            'identity'=>$identity,
            'fp'=>self::$identity
        );
        $ugradeinfo = self::CloudApi('structure',$data);
        if (is_error($ugradeinfo)) return $ugradeinfo;
        $structures = json_decode(base64_decode($ugradeinfo['structure']), true);
        $difference = self::CloudCompare($structures,$targetpath);
        if (empty($difference)) return true;
        $data = array(
            'identity'=>$identity,
            'fp'=>self::$identity,
            'releasedate'=>$ugradeinfo['releasedate'],
            'difference'=>base64_encode(json_encode($difference))
        );
        $zipcontent = self::CloudApi('upgrade',$data,true);
        if (is_error($zipcontent)) return $zipcontent;
        if (!$patch){
            $patch = base_path('storage/patch/');
        }
        if (!is_dir($patch)){
            FileService::mkdirs($patch);
        }
        $filename = FileService::file_random_name($patch,'zip');
        $fullname = $patch.$filename;
        if (false == file_put_contents($fullname, $zipcontent)) {
            return error(-1,'补丁下载失败：权限不足');
        }
        $patchpath = $patch.$identity.$ugradeinfo['releasedate'].'/';
        if (is_dir($patchpath)){
            FileService::rmdirs($patchpath);
        }
        $zip = new \ZipArchive();
        $openRes = $zip->open($fullname);
        if ($openRes === TRUE) {
            $zip->extractTo($patchpath);
            $zip->close();
            @unlink($fullname);
        }else{
            @unlink($fullname);
            return error(-1,'补丁解压失败，请重试');
        }
        //5、将补丁文件更新到本地
        self::CloudPatch($targetpath,$patchpath,true);
        FileService::rmdirs($patchpath);
        return true;
    }

    static function CloudCompare($structures=array(),$target='',$basedir=''){
        if (empty($structures) || !$target) return false;
        if (!is_dir($target)) return  $structures;
        $difference = array();
        foreach ($structures as $item){
            if (is_array($item)){
                $folder = $basedir.$item[0];
                $dirdiff = array();
                if (!is_dir($target.$folder)){
                    $dirdiff = $item;
                }else{
                    $structure = self::CloudCompare($item[1],$target,$folder.'/');
                    if (!empty($structure)){
                        $dirdiff = array($item[0],$structure);
                    }
                }
                if (!empty($dirdiff)){
                    $difference[] = $dirdiff;
                }
            }else{
                $fileinfo = explode('|',$item);
                $filepath = $basedir.$fileinfo[0];
                if (!file_exists($target.$filepath)){
                    $difference[] = $item;
                }else{
                    $md5 = md5_file($target.$filepath);
                    $hash = substr($md5,0,4).substr($md5,-4);
                    if($hash!=$fileinfo[1]){
                        $difference[] = $item;
                    }
                }
            }
        }
        return $difference;
    }

    static function CloudPatch($target,$source,$overwrite=false){
        if (!$target || !$source) return false;
        if (!is_dir($target)){
            if ($overwrite){
                FileService::mkdirs($target);
            }else{
                return false;
            }
        }
        if (!is_dir($source)) return  false;
        $handle = dir($source);
        if ($dh = opendir($source)){
            while ($entry = $handle->read()) {
                if ($entry!= "." && $entry!=".." && $entry!=".svn" && $entry!=".git"){
                    $new = $source.$entry;
                    if(is_dir($new)) {
                        if (!is_dir($target.$entry)){
                            FileService::mkdirs($target.$entry.'/');
                        }
                        self::CloudPatch($target.$entry.'/',$source.$entry.'/',$overwrite);
                    }else{
                        if(file_exists($target.$entry)){
                            if($overwrite){
                                @unlink($target.$entry);
                            }else{
                                if (md5_file($target.$entry)==md5_file($new)) continue;
                                @unlink($target.$entry);
                            }
                        }
                        @copy($new, $target.$entry);
                    }
                }
            }
            closedir($dh);
        }
        return true;
    }

    static function CloudApi($apiname,$data=array(),$return=false){
        global $_W;
        if (!$data['appsecret']) $data['appsecret'] = self::AppSecret();
        if (!isset($data['r'])){
            $data['r'] = self::$apilist[$apiname];
        }
        if (!isset($data['fp'])){
            $data['fp'] = self::$identity;
        }
        $data['t'] = TIMESTAMP;
        $data['siteroot'] = $_W['siteroot'];
        $data['siteid'] = $_W['config']['site']['id'];
        $data['sign'] = self::GetSignature($data['appsecret'],$data);
        $res = HttpService::ihttp_post(self::$cloudapi,$data);
        if (is_error($res)) return $res;
        $result = json_decode($res['content'],true);
        if(empty($result) && $return) return $res['content'];
        if (isset($result['message']) && isset($result['type'])){
            if ($result['type']!='success' && !is_array($result['message']) && !$result['redirect']){
                return error(-1,$result['message']);
            }
        }
        return $result;
    }

    static function CloudActive(){
        global $_W;
        $default = array('state'=>'已授权','siteid'=>0,'siteroot'=>$_W['siteroot'],'expiretime'=>0,'status'=>0);
        $cachekey = CacheService::system_key('Whotalk:Authorize:Active');
        $authorize = Cache::get($cachekey,$default);
        $res = self::CloudApi('',array('r'=>'whotalkcloud.active.state'));
        if (is_error($res)){
            $authorize['state'] = $res['message'];
            return $authorize;
        }
        if (!isset($res['site']) || !isset($res['authorize'])){
            $authorize['state'] = '授权状态查询失败';
            return $authorize;
        }

        $authorize['siteid'] = $res['site']['id'];
        if ($res['site']['status']==1 && $res['authorize']['status']==1){
            $authorize['expiretime'] = $res['authorize']['expiretime'];
            if ($res['authorize']['expiretime']>0 && $res['authorize']['expiretime']<TIMESTAMP){
                $authorize['status'] = 2;
                $authorize['state'] = '授权已到期';
            }else{
                $authorize['status'] = 1;
            }
        }

        Cache::put($cachekey, $authorize, 3600);

        return $authorize;
    }

    static function CloudEnv($search, $replace){
        if (empty($search) || empty($replace)) return false;
        $envfile = base_path(".env");
        $reader = fopen($envfile,'r');
        $envdata = fread($reader,filesize($envfile));
        fclose($reader);
        $envdata = str_replace($search, $replace,$envdata);
        $writer = fopen($envfile,'w');
        $complete = fwrite($writer,$envdata);
        fclose($writer);
        return $complete;
    }

    static function AppSecret(){
        global $_W;
        return sha1($_W['config']['setting']['authkey'].'-'.$_W['siteroot'].'-'.$_W['config']['site']['id']);
    }

    static function GetSignature($appsecret='',$data=array()){
        if (!$appsecret) return false;
        unset($data['sign'],$data['appsecret']);
        ksort($data);
        $string = base64_encode(http_build_query($data)).$appsecret;
        return md5($string);
    }

}
