<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class CloudService
{

    static $identity = 'swa_framework_laravel';
    static $cloudapi = 'https://chat.gxit.org/app/index.php?i=4&c=entry&m=swa_supersale&do=api';
    static $apilist = array('rmcom'=>'cloud.vendor.remove','require'=>'cloud.install','structure'=>'cloud.structure','upgrade'=>'cloud.makepatch');

    static function RequireModule($identity,$path='addons'){
        $modulePre = ModuleService::SysPrefix();
        $moduleName = str_replace($modulePre, "", $identity);
        $targetpath = base_path("public/$path/$moduleName");
        $from = 'local';
        if (!is_dir($targetpath)){
            MSService::TerminalSend(['mode'=>'info', 'message'=>'即将从云端获取模块']);
            $result = self::CloudRequire($identity,$targetpath);
            if(is_error($result)) return $result;
            $from = 'cloud';
        }
        //进入模块安装流程
        return ModuleService::install($moduleName,$path,$from);
    }

    static function getPlugins(){
        $plugins = [];
        $condition = array('type'=>1);
        //获取已安装模块(从云端表中)
        $components = DB::table('gxswa_cloud')->where($condition)->orderByRaw("`id` desc")->get()->toArray();
        if (!empty($components)){
            foreach ($components as $com){
                $com['cloudinfo'] = !empty($com['online']) ? unserialize($com['online']) : array();
                $com['cloudinfo']['isLocal'] = false;
                $com['cloudinfo']['isnew'] = (bool)$com['cloudinfo']['isnew'];
                if (!empty($com['modulename'])){
                    $local = ModuleService::installCheck($com['modulename']);
                    if (is_error($local)){
                        $com['isDelete'] = true;
                    }elseif(DEVELOPMENT && empty($com['cloudinfo']['isnew'])){
                        //判断是否可以升级（本地升级）
                        $application = $local->application;
                        if (version_compare($application['version'], $com['version'], '>') || $application['releasedate']>$com['releasedate']){
                            $com['cloudinfo'] = array(
                                'version'=>$application['version'],
                                'releasedate'=>$application['releasedate'],
                                'isLocal'=>true,
                                'isnew'=>true
                            );
                        }
                    }
                }
                $com['logo'] = asset($com['logo']);
                $com['lastupdate'] = $com['updatetime'] ? date('Y/m/d H:i',$com['updatetime']) : '初始安装';
                $com['installtime'] = date('Y/m/d H:i',$com['addtime']);
                $com['action'] = '<div class="layui-btn-group">';
                if (!empty($com['cloudinfo']) && $com['cloudinfo']['isnew']){
                    //从云端升级
                    $com['action'] .= '<a href="'.wurl('module/update', array('nid'=>$com['modulename'])).'" class="layui-btn layui-btn-sm layui-btn-danger js-terminal" data-text="升级前请做好源码和数据备份，避免升级故障导致系统无法正常运行">升级</a>';
                }
                $com['action'] .= '<a href="'.wurl('setting/comcheck', array('cid'=>$com['id'])).'" class="layui-btn layui-btn-sm layui-btn-normal ajaxshow">检测更新</a>';
                $com['action'] .= '<a href="'.wurl('module/remove', array('nid'=>$com['modulename'])).'" class="layui-btn layui-btn-sm layui-btn-primary js-terminal" data-text="即将卸载该应用并删除应用产生的所有数据，是否确定要卸载？">卸载</a></div>';
                $plugins[$com['modulename']] = $com;
            }
        }
        //获取本地模块
        $modules = FileService::file_tree(public_path('addons'), array('*/manifest.json'));
        if (!empty($modules)){
            foreach ($modules as $value){
                $identity = str_replace(array(public_path('addons/'),"/manifest.json"),'', $value);
                if (empty($identity)) continue;
                try {
                    $ManiFest = ModuleService::getManifest($identity);
                    if (is_error($ManiFest)) continue;
                    $com = $ManiFest['application'];
                }catch (\Exception $exception){
                    continue;
                }
                $com['logo'] = asset($com['logo']);
                $com['website'] = $com['url'];
                $com['cloudinfo'] = array();
                $com['installtime'] = '<span class="layui-badge">未安装</span>';
                $com['addtime'] = 0;
                $com['action'] = '';
                //已安装
                if ($ManiFest['installed']){
                    if (isset($plugins[$identity])){
                        $com['installtime'] = $plugins[$identity]['installtime'];
                        $com['lastupdate'] = $plugins[$identity]['lastupdate'];
                    }else{
                        $com['installtime'] = '本地安装';
                        $com['lastupdate'] = '-';
                    }
                    $com['addtime'] = $com['releasedate'];
                    if (DEVELOPMENT){
                        $Module = ModuleService::fetch($com['identifie']);
                        if (!empty($Module) && !is_error($Module)){
                            if (version_compare($com['version'], $Module['version'], '>')){
                                $com['cloudinfo'] = array(
                                    'version'=>$com['version'],
                                    'releasedate'=>$com['releasedate'],
                                    'isnew'=>true
                                );
                                //从本地升级
                                $com['action'] .= '<a href="'.wurl('module/upgrade', array('nid'=>$Module['name'])).'" class="layui-btn layui-btn-sm layui-btn-danger js-terminal" data-text="升级前请做好源码和数据备份，避免升级故障导致系统无法正常运行">升级</a>';
                                $com['version'] = $Module['version'];
                            }
                        }
                    }
                    $com['action'] .= '<a href="'.wurl('module/remove', array('nid'=>$identity)).'" class="layui-btn layui-btn-sm layui-btn-primary js-terminal" data-text="即将卸载该应用并删除应用产生的所有数据，是否确定要卸载？">卸载</a></div>';
                }else{
                    $com['lastupdate'] = '-';
                    if(DEVELOPMENT){
                        $com['action'] = '<a href="'.wurl('module/install', array('nid'=>$identity)).'" class="layui-btn layui-btn-sm layui-btn-normal js-terminal" data-text="确定要安装该应用？">安装</a>';
                    }
                }
                $plugins[$identity] = $com;
            }
        }
        //获取云端未安装组件
        $cachekey = "cloud:module_list:1";
        $res = Cache::get($cachekey, array());
        if (empty($res)){
            $data = array(
                'r'=>'cloud.packages',
                'pidentity'=>config('system.identity'),
                'page'=>1,
                'category'=>1
            );
            $res = CloudService::CloudApi("", $data);
            Cache::put($cachekey, $res, 600);
        }
        if (!is_error($res) && !empty($res['servers'])){
            $modulePre = ModuleService::SysPrefix();
            foreach ($res['servers'] as $value){
                $identify = str_replace($modulePre, "", $value['identity']);
                if (empty($identify)) continue;
                $releaseDate = intval($value['release']['releasedate']);
                if (isset($plugins[$identify])){
                    $local = $plugins[$identify];
                    if ($local['addtime']==0) continue;
                    $cloudinfo = array('isnew'=>false,'version'=>$value['release']['version'],'releasedate'=>$releaseDate);
                    if (version_compare($local['version'], $value['release']['version'], '<') || $local['releasedate']<$releaseDate){
                        $cloudinfo['isnew'] = true;
                        if (empty($local['cloudinfo']) || !$local['cloudinfo']['isnew']){
                            $local['action'] = '<a href="'.wurl('module/update', array('nid'=>$identity)).'" class="layui-btn layui-btn-sm layui-btn-danger js-terminal" data-text="升级前请做好源码和数据备份，避免升级故障导致系统无法正常运行">升级</a>'.$local['action'];
                        }
                    }
                    $local['cloudinfo'] = $cloudinfo;
                    $plugins[$identify] = $local;
                }else{
                    $com = array(
                        'id'=>0,
                        'name'=>$value['name'],
                        'identify'=>$identify,
                        'version'=>$value['release']['version'],
                        'releasedate'=>$releaseDate,
                        'ability'=>$value['name'],
                        'description'=>$value['summary'],
                        'author'=>$value['author'],
                        'website'=>$value['website'],
                        'logo'=>$value['icon']
                    );
                    $com['lastupdate'] = '-';
                    $com['cloudinfo'] = array(
                        'version'=>$value['release']['version'],
                        'releasedate'=>$releaseDate
                    );
                    $com['installtime'] = '<span class="layui-badge">未安装</span>';
                    $com['action'] = '<a href="'.wurl('module/require', array('nid'=>$value['identity'])).'" class="layui-btn layui-btn-sm layui-btn-normal js-terminal" data-text="确定要安装该应用？">安装</a>';
                    $plugins[$identify] = $com;
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
            'fp'=>config('system.identity')
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
        if (!file_put_contents($patch . $filename, $zipcontent)) {
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
        MSService::TerminalSend(['mode'=>'info', 'message'=>'从云端对比程序源码...']);
        $data = array(
            'identity'=>$identity,
            'fp'=>config('system.identity')
        );
        $ugradeinfo = self::CloudApi('structure',$data);
        if (is_error($ugradeinfo)) return $ugradeinfo;
        MSService::TerminalSend(['mode'=>'info', 'message'=>'获取到云端程序信息：V'.$ugradeinfo['version']]);
        $structures = json_decode(base64_decode($ugradeinfo['structure']), true);
        $difference = self::CloudCompare($structures,$targetpath);
        if (empty($difference)) return true;
        MSService::TerminalSend(['mode'=>'info', 'message'=>'从云端同步应用程序...']);
        $data = array(
            'identity'=>$identity,
            'fp'=>config('system.identity'),
            'releasedate'=>$ugradeinfo['releasedate'],
            'difference'=>base64_encode(json_encode($difference))
        );
        $zipcontent = self::CloudApi('upgrade',$data,true);
        if (is_error($zipcontent)) return $zipcontent;
        if (empty($zipcontent)){
            MSService::TerminalSend(['mode'=>'err', 'message'=>'云端程序同步失败，请更新缓存后再试']);
            return error(-1,'补丁获取失败');
        }
        if (!$patch){
            $patch = base_path('storage/patch/');
        }
        if (!is_dir($patch)){
            FileService::mkdirs($patch);
        }
        $filename = FileService::file_random_name($patch,'zip');
        $fullname = $patch.$filename;
        if (!file_put_contents($fullname, $zipcontent)) {
            MSService::TerminalSend(['mode'=>'err', 'message'=>'云端程序同步失败，请检查文件权限']);
            return error(-1,'补丁下载失败：请检查文件权限');
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
            MSService::TerminalSend(['mode'=>'err', 'message'=>'补丁包解压失败，请检查文件夹权限']);
            return error(-1,'补丁解压失败，请重试');
        }
        MSService::TerminalSend(['mode'=>'info', 'message'=>'更新程序源码...']);
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
                $ignores = array(".", "..", ".svn", ".git", ".gitignore");
                if (in_array($entry, $ignores)){
                    continue;
                }
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
            $data['fp'] = config('system.identity');
        }
        $data['t'] = TIMESTAMP;
        $data['siteroot'] = $_W['siteroot'];
        $data['siteid'] = $_W['config']['site']['id'];
        $data['devmode'] = env('APP_DEVELOPMENT',0);
        $data['sign'] = self::GetSignature($data['appsecret'],$data);
        $CloudApi = env('APP_CLOUD_API', self::$cloudapi);
        $res = HttpService::ihttp_post($CloudApi,$data);
        if (is_error($res)) return $res;
        $result = json_decode($res['content'],true);
        if(empty($result) && $return) return $res['content'];
        if (isset($result['message']) && isset($result['type'])){
            if ($result['type']!='success' && !is_array($result['message'])){
                $respone = error(-1,$result['message']);
                if (!empty($result['redirect'])){
                    $respone['redirect'] = $result['redirect'];
                }
                return $respone;
            }
        }
        return $result;
    }

    static function CloudActive(){
        global $_W;
        $default = array('state'=>'未开始激活','siteid'=>0,'siteroot'=>$_W['siteroot'],'expiretime'=>0,'status'=>0,'uid'=>0,'mobile'=>"",'name'=>$_W['setting']['page']['title']);
        $cachekey = CacheService::system_key('HingWork:Authorize:Active');
        $authorize = Cache::get($cachekey,$default);
        $res = self::CloudApi('',array('r'=>'cloud.active.state', 'siteName'=>$authorize['name'],'identity'=>config('system.identity')));
        if (!empty($res['redirect'])){
            $authorize['redirect'] = trim($res['redirect']);
        }
        if (is_error($res)){
            $authorize['state'] = $res['message'];
            return $authorize;
        }
        if (!isset($res['siteinfo'])){
            $authorize['state'] = '激活状态查询失败';
            return $authorize;
        }
        if ($res['siteinfo']['status']==1){
            $authorize['name'] = $res['siteinfo']['name'];
        }

        $authorize['siteid'] = $res['siteinfo']['id'];
        $authorize['uid'] = $res['siteinfo']['uid'];
        $authorize['mobile'] = $res['siteinfo']['mobile'];
        $authorize['status'] = $res['siteinfo']['status'];
        if ($res['siteinfo']['status']==1){
            $authorize['state'] = '已激活';
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
        $envdata = str_replace($search, $replace, $envdata);
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
