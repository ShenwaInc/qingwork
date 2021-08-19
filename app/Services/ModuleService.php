<?php


namespace App\Services;


use App\Models\Module;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class ModuleService
{

    static $coremodules = array('whotalk'=>'Whotalk即时通讯');

    static function Initializer(){
        $query = DB::table('modules');
        $initialized = $query->where('mid','>',0)->first();
        if (!$initialized){
            $query->insert(array(
                ['name'=>'basic','application_type'=>0,'type'=>'system','title'=>'基本文字回复','version'=>'1.0','ability'=>'基本文字回复','description'=>'基本文字回复','author'=>'WeEngine Team','isrulefields'=>1,'issystem'=>1,'wxapp_support'=>1,'welcome_support'=>1,'oauth_type'=>1,'webapp_support'=>1,'phoneapp_support'=>1,'account_support'=>2,'xzapp_support'=>1,'aliapp_support'=>1,'baiduapp_support'=>1,'toutiaoapp_support'=>1],
                ['name'=>'news','application_type'=>0,'type'=>'system','title'=>'基本混合图文回复','version'=>'1.0','ability'=>'基本混合图文回复','description'=>'基本混合图文回复','author'=>'WeEngine Team','isrulefields'=>1,'issystem'=>1,'wxapp_support'=>1,'welcome_support'=>1,'oauth_type'=>1,'webapp_support'=>1,'phoneapp_support'=>1,'account_support'=>2,'xzapp_support'=>1,'aliapp_support'=>1,'baiduapp_support'=>1,'toutiaoapp_support'=>1],
                ['name'=>'music','application_type'=>0,'type'=>'system','title'=>'基本音乐回复','version'=>'1.0','ability'=>'基本音乐回复','description'=>'基本音乐回复','author'=>'WeEngine Team','isrulefields'=>1,'issystem'=>1,'wxapp_support'=>1,'welcome_support'=>1,'oauth_type'=>1,'webapp_support'=>1,'phoneapp_support'=>1,'account_support'=>2,'xzapp_support'=>1,'aliapp_support'=>1,'baiduapp_support'=>1,'toutiaoapp_support'=>1],
                ['name'=>'userapi','application_type'=>0,'type'=>'system','title'=>'自定义接口回复','version'=>'1.0','ability'=>'自定义接口回复','description'=>'自定义接口回复','author'=>'WeEngine Team','isrulefields'=>1,'issystem'=>1,'wxapp_support'=>1,'welcome_support'=>1,'oauth_type'=>1,'webapp_support'=>1,'phoneapp_support'=>1,'account_support'=>2,'xzapp_support'=>1,'aliapp_support'=>1,'baiduapp_support'=>1,'toutiaoapp_support'=>1],
                ['name'=>'userapi','application_type'=>0,'type'=>'system','title'=>'会员中心充值模块','version'=>'1.0','ability'=>'会员中心充值模块','description'=>'会员中心充值模块','author'=>'WeEngine Team','isrulefields'=>1,'issystem'=>1,'wxapp_support'=>1,'welcome_support'=>1,'oauth_type'=>1,'webapp_support'=>1,'phoneapp_support'=>1,'account_support'=>2,'xzapp_support'=>1,'aliapp_support'=>1,'baiduapp_support'=>1,'toutiaoapp_support'=>1],
                ['name'=>'custom','application_type'=>0,'type'=>'system','title'=>'多客服转接','version'=>'1.0','ability'=>'多客服转接','description'=>'多客服转接','author'=>'WeEngine Team','isrulefields'=>1,'issystem'=>1,'wxapp_support'=>1,'welcome_support'=>1,'oauth_type'=>1,'webapp_support'=>1,'phoneapp_support'=>1,'account_support'=>2,'xzapp_support'=>1,'aliapp_support'=>1,'baiduapp_support'=>1,'toutiaoapp_support'=>1],
                ['name'=>'images','application_type'=>0,'type'=>'system','title'=>'基本图片回复','version'=>'1.0','ability'=>'基本图片回复','description'=>'基本图片回复','author'=>'WeEngine Team','isrulefields'=>1,'issystem'=>1,'wxapp_support'=>1,'welcome_support'=>1,'oauth_type'=>1,'webapp_support'=>1,'phoneapp_support'=>1,'account_support'=>2,'xzapp_support'=>1,'aliapp_support'=>1,'baiduapp_support'=>1,'toutiaoapp_support'=>1],
                ['name'=>'video','application_type'=>0,'type'=>'system','title'=>'基本视频回复','version'=>'1.0','ability'=>'基本视频回复','description'=>'基本视频回复','author'=>'WeEngine Team','isrulefields'=>1,'issystem'=>1,'wxapp_support'=>1,'welcome_support'=>1,'oauth_type'=>1,'webapp_support'=>1,'phoneapp_support'=>1,'account_support'=>2,'xzapp_support'=>1,'aliapp_support'=>1,'baiduapp_support'=>1,'toutiaoapp_support'=>1],
                ['name'=>'voice','application_type'=>0,'type'=>'system','title'=>'基本语音回复','version'=>'1.0','ability'=>'基本语音回复','description'=>'基本语音回复','author'=>'WeEngine Team','isrulefields'=>1,'issystem'=>1,'wxapp_support'=>1,'welcome_support'=>1,'oauth_type'=>1,'webapp_support'=>1,'phoneapp_support'=>1,'account_support'=>2,'xzapp_support'=>1,'aliapp_support'=>1,'baiduapp_support'=>1,'toutiaoapp_support'=>1],
                ['name'=>'chats','application_type'=>0,'type'=>'system','title'=>'发送客服消息','version'=>'1.0','ability'=>'发送客服消息','description'=>'公众号可以在粉丝最后发送消息的48小时内无限制发送消息','author'=>'WeEngine Team','isrulefields'=>1,'issystem'=>1,'wxapp_support'=>1,'welcome_support'=>1,'oauth_type'=>1,'webapp_support'=>1,'phoneapp_support'=>1,'account_support'=>2,'xzapp_support'=>1,'aliapp_support'=>1,'baiduapp_support'=>1,'toutiaoapp_support'=>1],
                ['name'=>'wxcard','application_type'=>0,'type'=>'system','title'=>'微信卡券回复','version'=>'1.0','ability'=>'微信卡券回复','description'=>'微信卡券回复','author'=>'WeEngine Team','isrulefields'=>1,'issystem'=>1,'wxapp_support'=>1,'welcome_support'=>1,'oauth_type'=>1,'webapp_support'=>1,'phoneapp_support'=>1,'account_support'=>2,'xzapp_support'=>1,'aliapp_support'=>1,'baiduapp_support'=>1,'toutiaoapp_support'=>1],
                ['name'=>'default','application_type'=>2,'type'=>'system','title'=>'微站默认模板','version'=>'1.0','ability'=>'微站默认模板','description'=>'微站默认模板','author'=>'WeEngine Team','isrulefields'=>1,'issystem'=>1,'wxapp_support'=>1,'welcome_support'=>1,'oauth_type'=>1,'webapp_support'=>1,'phoneapp_support'=>1,'account_support'=>2,'xzapp_support'=>1,'aliapp_support'=>1,'baiduapp_support'=>1,'toutiaoapp_support'=>1],
                ['name'=>'store','application_type'=>0,'type'=>'business','title'=>'站内商城','version'=>'1.0','ability'=>'站内商城','description'=>'站内商城','author'=>'WeEngine Team','isrulefields'=>1,'issystem'=>1,'wxapp_support'=>1,'welcome_support'=>1,'oauth_type'=>1,'webapp_support'=>1,'phoneapp_support'=>1,'account_support'=>2,'xzapp_support'=>1,'aliapp_support'=>1,'baiduapp_support'=>1,'toutiaoapp_support'=>1]
            ));
        }
        return true;
    }

    static function install($identity,$path='addons',$from='cloud'){
        $installpath = base_path("public/$path/{$identity}/");
        $manifestfile = $installpath . "Manifest.php";
        if(!file_exists($manifestfile)) return error(-1,'无法解析模块安装包');
        $ManiFest = require_once $manifestfile;
        if ($ManiFest->installed) return true;
        //执行安装脚本
        if (method_exists($ManiFest,'installer')){
            try {
                $ManiFest->installer();
            } catch (\Exception $exception){
                return error(-1,'安装失败：运行脚本出现错误');
            }
        }
        //写入模块数据表
        $application = $ManiFest->application;
        $subscribes = method_exists($ManiFest,'subscribes') ? $ManiFest->subscribes : array();
        $handles = method_exists($ManiFest,'handles') ? $ManiFest->handles : array();
        $module = self::ModuleData($application,$subscribes,$handles);
        $module['from'] = $from;
        DB::table('modules')->insert($module);
        //写入服务组件表
        if (isset(self::$coremodules[$identity])){
            $comdata = array(
                'name'=>$module['title'],
                'modulename'=>$identity,
                'type'=>1,
                'logo'=>$module['logo'],
                'website'=>$module['url'],
                'version'=>$application['version'],
                'addtime'=>TIMESTAMP,
                'dateline'=>TIMESTAMP
            );
            if ($from=='cloud'){
                $comdata['updatetime'] = TIMESTAMP;
                $comdata['releasedate'] = $application['releasedate'];
            }
            DB::table('gxswa_cloud')->updateOrInsert(array(
                'identity'=>"laravel_module_{$identity}",
                'rootpath'=>"public/$path/{$identity}/"
            ),$comdata);
        }
        return true;
    }

    static function upgrade($identity){
        $module = DB::table('modules')->where('name',$identity)->first();
        if (empty($module)) return error(-1,'该模块尚未安装');
        $installpath = base_path("public/addons/{$identity}/");
        if (isset(self::$coremodules[$identity])){
            $component = DB::table('gxswa_cloud')->where('identity',"laravel_module_{$identity}")->first();
            if (!empty($component)){
                $installpath = base_path($component['rootpath']);
            }
        }
        $manifestfile = $installpath . "Manifest.php";
        if(!file_exists($manifestfile)) return error(-1,'无法解析模块安装包');
        $ManiFest = require_once $manifestfile;
        if (!$ManiFest->installed) return error(-1,'该模块尚未安装');
        $application = $ManiFest->application;
        if (!empty($component) && $component['releasedate']>=$application['releasedate']){
            //已经是最新版本
            return true;
        }
        //执行升级脚本
        if (method_exists($ManiFest,'upgrader')){
            try {
                $ManiFest->upgrader();
            } catch (\Exception $exception){
                return error(-1,'升级失败：运行脚本出现错误');
            }
        }
        //更新模块数据表
        $subscribes = method_exists($ManiFest,'subscribes') ? $ManiFest->subscribes : array();
        $handles = method_exists($ManiFest,'handles') ? $ManiFest->handles : array();
        $moduledata = self::ModuleData($application,$subscribes,$handles);
        DB::table('modules')->where('name',$module['name'])->update($moduledata);
        //更新模块数据表
        if (!empty($component)){
            DB::table('gxswa_cloud')->where('id',$component['id'])->update(array(
                'name'=>$module['title'],
                'logo'=>$module['logo'],
                'website'=>$module['url'],
                'version'=>$application['version'],
                'updatetime'=>TIMESTAMP,
                'releasedate'=>$application['releasedate']
            ));
        }
        return true;
    }

    static function fetch($name, $enabled = true) {
        global $_W;
        $cachekey = CacheService::system_key('module_info', array('module_name' => $name));
        $module = Cache::get($cachekey,array());
        if (empty($module)) {
            $module_info = Module::where('name',$name)->first();
            if (empty($module_info)) {
                return array();
            }
            $module_info['isdisplay'] = 1;
            $module_info['logo'] = tomedia($module_info['logo']);
            $module_info['preview'] = tomedia(IA_ROOT . '/addons/' . $module_info['name'] . '/preview.jpg', '', true);
            if (file_exists(IA_ROOT . '/addons/' . $module_info['name'] . '/preview-custom.jpg')) {
                $module_info['preview'] = tomedia(IA_ROOT . '/addons/' . $module_info['name'] . '/preview-custom.jpg', '', true);
            }
            $module_receive_ban = (array)SettingService::Load('module_receive_ban');
            if (is_array($module_receive_ban['module_receive_ban']) && in_array($name, $module_receive_ban['module_receive_ban'])) {
                $module_info['is_receive_ban'] = true;
            }
            $module_ban = (array)SettingService::Load('module_ban');
            if (is_array($module_ban['module_ban']) && in_array($name, $module_ban['module_ban'])) {
                $module_info['is_ban'] = true;
            }
            $module_upgrade = (array)SettingService::Load('module_upgrade');
            if (is_array($module_upgrade['module_upgrade']) && in_array($name, array_keys($module_upgrade['module_upgrade']))) {
                $module_info['is_upgrade'] = true;
            }

            $module_info['recycle_info'] = array();
            $recycle_info = DB::table('modules_recycle')->where('name',$name)->first();
            if (!empty($recycle_info)) {
                $is_delete = true;
                $account_support = array(
                    'account_support' => array(
                        'type' => 'account',
                        'type_name' => '公众号',
                        'support' => 2,
                        'not_support' => 1,
                        'store_type' => 1,
                    )
                );
                foreach ($account_support as $support => $value) {
                    if (!empty($recycle_info[2][$support])) {
                        $module_info['recycle_info'][$support] = 2; 				} else {
                        $module_info['recycle_info'][$support] = empty($recycle_info[1][$support]) ? 0 : 1;
                    }
                    if ($module_info[$support] == $value['support'] && empty($module_info['recycle_info'][$support])) {
                        $is_delete = false;
                    }
                }
                $module_info['is_delete'] = $is_delete; 		}

            $module = $module_info;
            Cache::put($cachekey, $module_info, 86400*7);
        }

        if (!empty($enabled)) {
            if (!empty($module['is_delete'])) {
                return array();
            }
        }

        if (!empty($module) && !empty($_W['uniacid'])) {
            $setting_cachekey = CacheService::system_key('module_setting', array('module_name' => $name, 'uniacid' => $_W['uniacid']));
            $setting = Cache::get($setting_cachekey,array());
            if (empty($setting)) {
                $setting = DB::table('uni_account_modules')->where(array('module'=>$name,'uniacid'=>$_W['uniacid']))->first();
                $setting = empty($setting) ? array('module' => $name) : $setting;
                Cache::put($setting_cachekey, $setting, 86400*7);
            }
            $module['config'] = $setting['settings'];
            $module['enabled'] = $module['issystem'] || !isset($setting['enabled']) ? 1 : $setting['enabled'];
            $module['displayorder'] = $setting['displayorder'];
            $module['shortcut'] = $setting['shortcut'];
            $module['module_shortcut'] = $setting['module_shortcut'];
        }
        return $module;
    }

    static function ModuleData($application,$subscribes=array(),$handles=array()){
        return array(
            'name'=>$application['identifie'],
            'application_type'=>1,
            'type'=>$application['type'],
            'title'=>$application['name'],
            'version'=>$application['version'],
            'ability'=>$application['ability'],
            'description'=>$application['description'],
            'author'=>$application['author'],
            'url'=>$application['url'],
            'logo'=>$application['logo'],
            'subscribes'=>serialize($subscribes),
            'handles'=>serialize($handles),
            'isrulefields'=>0,
            'issystem'=>0,
            'title_initial'=>'W',
            'wxapp_support'=>1,
            'welcome_support'=>1,
            'oauth_type'=>1,
            'webapp_support'=>1,
            'phoneapp_support'=>1,
            'account_support'=>2,
            'xzapp_support'=>1,
            'aliapp_support'=>1,
            'baiduapp_support'=>1,
            'toutiaoapp_support'=>1
        );
    }

}