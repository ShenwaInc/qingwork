<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Support\Facades\Cache;

class SettingService{

    static function Load($key = '', $nocache=false) {
        global $_W;
        $cachekey = CacheService::system_key('setting');
        if($nocache){
            Cache::forget($cachekey);
            $settings = array();
        }else{
            //从缓存中读取
            $settings = Cache::get($cachekey, array());
        }
        if (empty($settings)) {
            //如果找不到缓存则从数据库中读取
            $_settings = Setting::get()->keyBy('key');
            if (!empty($_settings)) {
                foreach ($_settings as $k => $v) {
                    $settings[$k] = $v['value'] ? unserialize($v['value']) : array();
                }
            }
            if (empty($key)){
                //写入缓存
                Cache::put($cachekey, $settings, 86400*7);
            }
            unset($_settings);
        }
        $_W['setting'] = array_merge($settings, (array)$_W['setting']);
        if (!empty($key)) {
            return array($key => $settings[$key]);
        } else {
            return $settings;
        }
    }

    static function uni_load($name = '', $uniacid = 0){
        global $_W;
        $uniacid = empty($uniacid) ? $_W['uniacid'] : $uniacid;
        $cachekey = CacheService::system_key('unisetting', array('uniacid' => $uniacid));
        $unisetting = Cache::get($cachekey,array());
        if (empty($unisetting) || ($name == 'remote' && empty($unisetting['remote']))) {
            $unisetting = Setting::getUni($uniacid);
            if (!empty($unisetting)) {
                $serialize = array('site_info', 'stat', 'oauth', 'passport', 'notify',
                    'creditnames', 'default_message', 'creditbehaviors', 'payment',
                    'recharge', 'tplnotice', 'mcplugin', 'statistics', 'bind_domain', 'remote');
                foreach ($unisetting as $key => &$row) {
                    if (in_array($key, $serialize) && !empty($row)) {
                        $row = (array)unserialize($row);
                    }
                }
            } else {
                $unisetting = array();
            }
            Cache::put($cachekey, $unisetting,86400*7);
        }
        if (empty($unisetting)) {
            return array();
        }
        if (empty($name)) {
            return $unisetting;
        }
        if (!is_array($name)) {
            $name = array($name);
        }
        return array_elements($name, $unisetting);
    }

    static function check_php_ext($extension) {
        return extension_loaded($extension) ? true : false;
    }
}
