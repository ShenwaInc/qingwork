<?php

namespace App\Services;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\Process\Process;

class MSService
{
    public static $tableName = 'microserver';

    public static function setup(){
        if (!Schema::hasTable("microserver")){
            Schema::create('microserver', function (Blueprint $table) {
                $table->increments('id');
                $table->string('identity', 20);
                $table->string('name', 20);
                $table->string('cover', 255)->default("");
                $table->text("summary")->nullable();
                $table->string("version",10)->default("");
                $table->string("releases",20)->default("");
                $table->string("drive", 10)->default("php");
                $table->string("entrance", 255)->default("");
                $table->mediumtext("datas")->nullable();
                $table->mediumtext("configs")->nullable();
                $table->boolean('status')->default(1);
                $table->integer("addtime")->default(0)->unsigned();
                $table->integer("dateline")->default(0)->unsigned();
            });
        }
        if (!Schema::hasTable('microserver_unilink')){
            Schema::create('microserver_unilink', function (Blueprint $table) {
                $table->increments('id');
                $table->string('name', 20);
                $table->string('title', 20);
                $table->string('cover', 255)->default("");
                $table->string("summary", 255)->default("");
                $table->string("entry", 255)->default("");
                $table->mediumtext("perms")->nullable();
                $table->boolean('status')->default(1);
                $table->integer("addtime")->default(0)->unsigned();
                $table->integer("dateline")->default(0)->unsigned();
            });
        }
    }

    public static function getmanifest($identity, $app=false){
        $manifest = MICRO_SERVER.$identity."/manifest.json";
        $inextra = false;
        if (!file_exists($manifest) && defined('MSERVER_EXTRA')){
            $manifest = MSERVER_EXTRA."/manifest.json";
            $inextra = true;
        }
        if (!file_exists($manifest)) return error(-1,'找不到安装文件');
        $service = json_decode(@file_get_contents($manifest), true);
        if (!isset($service['application']) || !isset($service['drive'])) return error(-1,'安装包解析失败');
        if ($app) return $service['application'];
        $service['inextra'] = $inextra;
        return $service;
    }

    /**
     * @param array $keys
     * @param $manifest
     * @return array
     */
    public function getApplication($keys, $manifest)
    {
        $application = post_var($keys, $manifest['application']);
        $application['drive'] = $manifest['drive'];
        $application['entrance'] = $manifest['entrance'];
        $datas = post_var(array('apis', 'methods', 'components', 'resources', 'events'), $manifest);
        if (!empty($datas)) {
            $application['datas'] = serialize($datas);
        }
        return $application;
    }

    public static function getservers($status=1){
        return pdo_getall(self::$tableName, array('status'=>intval($status)));
    }

    public static function getone($identity, $simple=true){
        $fields = array('id','identity','name','version','drive','status','releases');
        if (!$simple){
            $fields = array_merge($fields, array("cover","summary","entrance","datas","configs"));
        }
        $service = pdo_get(self::$tableName, array('identity'=>$identity), $fields);
        if (!empty($service) && !$simple){
            $service['datas'] = empty($service['datas']) ? array() : unserialize($service['datas']);
            $service['configs'] = empty($service['configs']) ? array() : unserialize($service['configs']);
        }
        return $service;
    }

    public function cloudUpdate($identity){
        $service = $this->cloudInfo($identity);
        if (is_error($service)) return $service;
        //获取线上文件结构
        $cloudIdentity = "microserver_".$identity;
        $cachekey = "cloud:structure:$cloudIdentity{$service['release']['releasedate']}";
        $cloudInfo = Cache::get($cachekey, array());
        if (empty($cloudInfo)){
            $cloudInfo = CloudService::CloudApi('structure',array(
                'identity'=>$cloudIdentity
            ));
            if (is_error($cloudInfo)) return $cloudInfo;
            Cache::put($cachekey, $cloudInfo, 7*86400);
        }
        //对比文件结构
        $serverpath = MICRO_SERVER.$identity."/";
        $structures = json_decode(base64_decode($cloudInfo['structure']), true);
        $difference = CloudService::CloudCompare($structures, $serverpath);
        if (!empty($difference)){
            //文件存在差异，获取补丁包
            $cloudUpdate = CloudService::CloudUpdate($cloudIdentity, $serverpath);
            if (is_error($cloudUpdate)) return $cloudUpdate;
        }
        return $this->upgrade($identity);
    }

    public function cloudInfo($identity){
        //获取应用信息
        $service = self::cloudserver($identity, true);
        if (is_error($service)){
            return $service;
        }
        //验证是否收费
        if ($service['product']['price']>0){
            //验证授权是否生效
            if (is_error($service['authorize'])){
                if (!empty($service['product']['buyurl'])){
                    header("location:{$service['product']['buyurl']}");
                    session_exit();
                }else{
                    return $service['authorize'];
                }
            }
        }
        return $service;
    }

    public function cloudInstall($identity){
        $service = $this->cloudInfo($identity);
        if (is_error($service)) return $service;
        $cloudIdentity = "microserver_".$identity;
        $require = CloudService::CloudRequire($cloudIdentity, MICRO_SERVER.$identity."/");
        if (is_error($require)) return $require;
        return $this->install($identity, true);
    }

    public static function cloudserver($identity, $nocache=false){
        //获取云端服务
        $cloudInfo = $nocache ? array() : Cache::get("microserver".$identity, array());
        if (!empty($cloudInfo)) return $cloudInfo;
        $data = array(
            'r'=>'cloud.package',
            'identity'=>"microserver_".$identity,
            'frompage'=>'list'
        );
        if (self::isexist($identity)){
            $data['frompage'] = 'local';
        }
        $res = CloudService::CloudApi("", $data);
        if(!is_error($res) && !isset($res['application'])){
            $res = error(-1, "应用解析失败");
        }
        Cache::put("microserver".$identity, $res, 86400);
        return $res;
    }

    public static function cloudservers($page=1, $keyword=""){
        $cachekey = "cloud:microserver_list";
        $res = Cache::get($cachekey, array());
        if (empty($res)){
            $data = array(
                'r'=>'cloud.packages',
                'compate'=>'laravel',
                'page'=>$page,
                'keyword'=>$keyword
            );
            $res = CloudService::CloudApi("", $data);
            Cache::put($cachekey, $res, 1800);
        }
        if (is_error($res)) return [];
        $servers = array();
        if (!empty($res['servers'])){
            foreach ($res['servers'] as $value){
                $identity = str_replace("microserver_","",$value['identity']);
                if (self::localExist($identity, DEVELOPMENT)) continue;
                $service = array(
                    'cover'=>$value['icon'],
                    'identity'=>$identity,
                    'name'=>$value['name'],
                    'isdelete'=>false,
                    'summary'=>$value['summary'],
                    'upgrade'=>[],
                    'entry'=>'',
                    'version'=>$value['release']['version'],
                    'releases'=>$value['release']['releasedate']
                );
                $service['actions'] = '<a class="layui-btn layui-btn-sm layui-btn-normal js-terminal" data-text="确定要安装该服务？" href="'.wurl('server', array("op"=>"cloudinst", "nid"=>$identity)).'">安装</a>';
                $servers[] = $service;
            }
        }
        return $servers;
    }

    public static function isexist($identity){
        return DB::table(self::$tableName)->where('identity', trim($identity))->count() > 0;
    }

    public static function localExist($identity, $manifest=true){
        $localPath = MICRO_SERVER.$identity."/";
        $service = $localPath.ucfirst($identity)."Service.php";
        if (!file_exists($service)){
            if(!defined('MSERVER_EXTRA')) return false;
            $localPath = MSERVER_EXTRA.$identity."/";
            $extraServer = dirname($localPath.ucfirst($identity)."Service.php");
            if (!file_exists($extraServer)) return false;
        }
        if (!$manifest){
            return true;
        }
        $manifest = MICRO_SERVER.$identity."/manifest.json";
        if (!file_exists($manifest)){
            if(!defined('MSERVER_EXTRA')) return false;
            $extraPath = dirname(MSERVER_EXTRA.$identity."/manifest.json");
            if (!file_exists($extraPath)) return false;
        }
        return $localPath;
    }

    public static function InitService($status=1){
        $servers = self::getservers($status);
        if ($status!=1) return $servers;
        if (empty($servers)) return array();
        foreach ($servers as &$server){
            $server['actions'] = '';
            if($server['status']!=1) continue;
            $server['entry'] = serv($server['identity'])->getEntry();
            if (!empty($server['entry']) && !is_error($server['entry'])){
                $server['actions'] .= '<a class="layui-btn layui-btn-sm layui-btn-normal" target="_blank" href="'.$server['entry'].'">管理</a>';
            }
            $server['upgrade'] = array();
            $upgradeAction = '<a class="layui-btn layui-btn-sm layui-btn-danger js-upgrade js-terminal layui-hide" data-text="升级前请做好数据备份" lay-tips="该服务可升级至最新版本" data-nid="'.$server['identity'].'" href="'.wurl('server', array('op'=>'cloudup', 'nid'=>$server['identity'])).'">升级</a>';
            if (DEVELOPMENT){
                if (!empty(serv($server['identity'])->getMethods())){
                    $server['actions'] .= '<a class="layui-btn layui-btn-sm" target="_blank" href="'.wurl("server/methods/{$server['identity']}").'">调用方法</a>';
                }
                $apis = serv($server['identity'])->getApis();
                if (!empty($apis['wiki']) || !empty($apis['schemas'])){
                    $server['actions'] .= '<a class="layui-btn layui-btn-sm" href="'.wurl("server/apis/{$server['identity']}").'" target="_blank">接口</a>';
                }
                $manifest = self::getmanifest($server['identity'], true);
                if (!is_error($manifest)){
                    if(version_compare($manifest['version'], $server['version'], '>')){
                        //本地可升级
                        $server['upgrade'] = array('version'=>$manifest['version'],'canup'=>true);
                        $upgradeAction = '<a class="layui-btn layui-btn-sm layui-btn-danger js-terminal" data-text="升级前请做好数据备份" lay-tips="该服务可升级至V'.$manifest['version'].'版本" href="'.wurl('server', array("op"=>"upgrade", "nid"=>$server['identity'])).'">升级</a>';
                    }
                }
                if (mb_strlen($server['summary'],'utf8')>30){
                    $server['summary'] = mb_substr($server['summary'], 0, 30, 'utf8') . '...';
                }
            }
            if (empty($server['upgrade'])){
                $cloudServer = Cache::get("microserver".$server['identity'], array());
                if (is_error($cloudServer)){
                    $upgradeAction = "";
                }elseif (!empty($cloudServer)){
                    $release = $cloudServer['release'];
                    if (version_compare($release['version'], $server['version'], '>') || $release['releasedate']>$server['releases']){
                        $upgradeAction = '<a class="layui-btn layui-btn-sm layui-btn-danger js-terminal" data-text="升级前请做好数据备份" lay-tips="该服务可升级至V'.$release['version'].'Release'.$release['releasedate'].'" href="'.wurl('server', array('op'=>'cloudup', 'nid'=>$server['identity'])).'">升级</a>';
                        $server['upgrade'] = array('version'=>$release['version'],'canup'=>true);
                    }else{
                        $upgradeAction = "";
                    }
                }
            }
            $server['actions'] .= $upgradeAction;
            $server['isdelete'] = false;
            $serverPath = self::localExist($server['identity'], DEVELOPMENT);
            if (!$serverPath){
                $server['isdelete'] = true;
            }elseif(file_exists($serverPath . "composer.error")){
                $server['actions'] .= '<a class="layui-btn layui-btn-sm layui-btn-danger js-terminal" href="'.wurl('server', array('op'=>'composer', 'nid'=>$server['identity'])).'">修复</a>';
            }
        }
        return $servers;
    }

    public function checkRequire($requires){
        if (empty($requires)) return true;
        $servers = [];
        $this->TerminalSend(['mode'=>'info', 'message'=>'即将安装相关依赖服务...']);
        foreach ($requires as $value){
            $identity = is_array($value) ? $value['id'] : $value;
            $servers[] = $identity;
            $service = self::getone($identity);
            if (!empty($service)){
                //已安装，版本检测
                $version = is_array($value) ? $value['version'] : "";
                if (!empty($version) && version_compare($version, $service['version'], '>')){
                    //提示更新版本
                    return error(-1, "该应用依赖的服务【{$service['name']}($identity)】的版本不低于V{$version}，请升级服务后重试");
                }
                continue;
            }
            if ($this->localExist($identity)){
                //未安装，但是本地存在则直接安装
                $install = $this->install($identity);
                if (is_error($install)) return error(-1, "安装依赖的服务({$identity})时发生异常：{$install['message']}");
            }else{
                //本地不存在则从云端安装
                $installCloud = $this->cloudInstall($identity);
                if (is_error($installCloud)) return error(-1, "安装依赖的服务({$identity})时发生异常：{$installCloud['message']}");
            }
        }
        $this->TerminalSend(['mode'=>'success', 'message'=>'相关依赖服务安装完成！']);
        return $servers;
    }

    public static function checkDepend($identity, $return=false){
        //判断服务依赖
        $servers = DB::table(self::$tableName)->select(array('id','identity','name','configs'))->where('configs', 'LIKE', "%$identity%")->get()->toArray();
        if (!empty($servers)){
            $depends = array();
            foreach ($servers as $value){
                $configs = $value['configs'] ? unserialize($value['configs']) : [];
                if (!empty($configs['require']) && in_array($identity, $configs['require'], true)){
                    $depends[$value['identity']] = $value['name'];
                }
            }
            if ($return) return $depends;
            if (!empty($depends)){
                $message = "操作失败：该服务正在被其它服务依赖（".implode('、', $depends)."），如需继续卸载，请先卸载对应服务。";
                self::TerminalSend(["mode"=>"err", "message"=>$message]);
                return error(-1, $message);
            }
        }
        return [];
    }

    public function autoinstall(){
        $servers = $this->InitService();
        $return = array("upgrade"=>0, "install"=>0, "faild"=>0, "servers"=>0);
        $installed = [];
        if (!empty($servers)){
            $return['servers'] = count($servers);
            foreach ($servers as $value){
                $installed[] = $value['identity'];
                if (!empty($value['upgrade'])){
                    try {
                        $res = $this->upgrade($value['identity']);
                        if (!is_error($res)){
                            $return['upgrade'] += 1;
                            continue;
                        }
                    }catch (\Exception $exception){
                        //Todo something
                    }
                    $return['faild'] += 1;
                }
            }
        }
        $locals = $this->getlocal();
        if (!empty($locals)){
            $return['servers'] += count($locals);
            foreach ($locals as $value){
                try {
                    $res = $this->install($value['identity']);
                    if (!is_error($res)){
                        $installed[] = $value['identity'];
                        $return['install'] += 1;
                        continue;
                    }
                }catch (\Exception $exception){
                    //Todo something
                }
                $return['faild'] += 1;
            }
        }
        $requires = array("websocket");
        foreach ($requires as $serve){
            try {
                if (in_array($serve, $installed) || self::isexist($serve)){
                    continue;
                }
                if (self::localExist($serve)){
                    $res = $this->install($serve);
                }else{
                    $res = $this->cloudInstall($serve);
                }
                if (!is_error($res)){
                    $return['install'] += 1;
                    continue;
                }
            }catch (\Exception $exception){
                //Todo something
            }
            $return['faild'] += 1;
        }
        return $return;
    }

    public function install($identity, $fromCloud=false, $autoInstall=false){
        if ($this->isexist($identity)) return true;
        $service = $this->getmanifest($identity);
        if (is_error($service)) return $service;
        //构造服务信息
        $keys = array('identity','name','version','cover','summary','releases');
        $application = $this->getApplication($keys, $service);
        $this->TerminalSend(["mode"=>"info", "message"=>"正在安装微服务【{$application['name']}^{$application['version']}】"]);
        //判断依赖服务
        $requires = $this->checkRequire($service['require']);
        if (is_error($requires)){
            return $requires;
        }
        $configs = post_var(array('uninstall'), $service);
        $configs['require'] = (array)$requires;
        if ($fromCloud){
            $configs['packagefrom'] = 'cloud';
        }
        if (!empty($configs)){
            $application['configs'] = serialize($configs);
        }
        //运行安装脚本
        if (!empty($service['install'])){
            try {
                $this->TerminalSend(["mode"=>"info", "message"=>"正在运行服务安装脚本..."]);
                script_run($service['install'], MICRO_SERVER.$identity);
            }catch (\Exception $exception){
                if (!DEVELOPMENT){
                    //删除服务安装包
                    FileService::rmdirs(MICRO_SERVER.$identity."/");
                }
                return error(-1,"安装失败：".$exception->getMessage());
            }
        }
        //操作入库
        $application['status'] = 1;
        $application['addtime'] = $application['dateline'] = TIMESTAMP;
        if (!pdo_insert(self::$tableName, $application)){
            try {
                script_run($configs['uninstall'], MICRO_SERVER.$identity);
                if (!DEVELOPMENT){
                    //删除服务安装包
                    FileService::rmdirs(MICRO_SERVER.$identity."/");
                }
            }catch (\Exception $exception){
                //Todo something
            }
            return error(-1,'安装失败，请重试');
        }
        $this->getEvents(true);
        $this->uniLink($service);
        if (!DEVELOPMENT){
            if ($service['inextra'] && defined('MSERVER_EXTRA')){
                CloudService::MoveDir(MSERVER_EXTRA.$identity, MICRO_SERVER.$identity);
            }
            @unlink(MICRO_SERVER.$identity."/manifest.json");
        }
        //加载Composer依赖
        if (file_exists(MICRO_SERVER.$identity."/composer.json")){
            $ComposerName = "microserver/$identity";
            $this->TerminalSend(["mode"=>"info", "message"=>"即将安装Composer依赖【{$ComposerName}】"]);
            $res = $this->ComposerRequire(MICRO_SERVER.$identity."/", $ComposerName);
            if (is_error($res)) return $res;
            if (!$res){
                $composerUrl = wurl("server", array('op'=>'composer', 'nid'=>$identity), true);
                $this->TerminalSend(["mode"=>"err", "message"=>"Composer依赖安装失败，请打开该网址手动安装：$composerUrl"]);
                return error(-102, "Composer依赖安装失败，请手动安装");
            }
        }
        return true;
    }

    public function upgrade($identity){
        //判断依赖服务
        $service = $this->getone($identity, false);
        if (empty($service)) return $this->install($identity);
        $manifest = $this->getmanifest($identity);
        if (is_error($manifest)) return $manifest;
        if($manifest['application']['identity']!=$service['identity']){
            return error(-1, "安装包的Identity不匹配");
        }
        //构造服务信息
        $keys = array('name','version','cover','summary','releases');
        $application = $this->getApplication($keys, $manifest);
        $this->TerminalSend(["mode"=>"info", "message"=>"正在升级微服务【{$application['name']}^{$application['version']}】"]);
        if(version_compare($application['version'],$service['version'],'>') || $application['releases']>$service['releases']){
            //判断依赖服务
            $requires = $this->checkRequire($service['require']);
            if (is_error($requires)){
                return $requires;
            }
            $service['configs']['uninstall'] = $manifest['uninstall'];
            $service['configs']['require'] = $requires;
            $application['configs'] = serialize($service['configs']);
            //运行升级脚本
            if (!empty($manifest['upgrade'])){
                try {
                    $this->TerminalSend(["mode"=>"info", "message"=>"正在运行服务升级脚本..."]);
                    script_run($manifest['upgrade'], MICRO_SERVER.$identity);
                }catch (\Exception $exception){
                    return error(-1,"安装失败：".$exception->getMessage());
                }
            }
            //操作入库
            $application['status'] = 1;
            $application['dateline'] = TIMESTAMP;
            if (!pdo_update(self::$tableName, $application, array('identity'=>$service['identity']))){
                return error(-1,'更新失败，请重试');
            }
            $this->getEvents(true);
            $this->uniLink($manifest);
            if (file_exists(MICRO_SERVER.$identity."/composer.json")){
                $ComposerName = "microserver/$identity";
                $this->TerminalSend(["mode"=>"info", "message"=>"即将安装Composer依赖【{$ComposerName}】"]);
                $res = $this->ComposerRequire(MICRO_SERVER.$identity."/", $ComposerName);
                if (is_error($res)) return $res;
                if (!$res){
                    $composerUrl = wurl("server", array('op'=>'composer', 'nid'=>$identity), true);
                    $this->TerminalSend(["mode"=>"err", "message"=>"Composer依赖安装失败，请打开该网址手动安装：$composerUrl"]);
                    return error(-102, "Composer依赖安装失败，请手动安装");
                }
            }
            if (!DEVELOPMENT){
                //删除安装包文件
                @unlink(MICRO_SERVER.$identity."/manifest.json");
            }
            return true;
        }
        return error(-1,"当前服务已经是最新版本");
    }

    public function uniLink($manifest){
        if (isset($manifest['uniLink'])){
            $uniLink = post_var(array('title','entry','summary','cover'), $manifest['uniLink']);
            if (empty($uniLink['cover'])) $uniLink['cover'] = $manifest['application']['cover'];
            if (empty($uniLink['entry'])) $uniLink['entry'] = $manifest['entrance'];
            if (empty($uniLink['title'])) $uniLink['title'] = $manifest['application']['name'];
            if (empty($uniLink['summary'])) $uniLink['summary'] = $manifest['application']['summary'];
            if (!empty($manifest['uniLink']['perms'])){
                $uniLink['perms'] = serialize($manifest['uniLink']['perms']);
            }
            $uniLink['status'] = 1;
            $uniLink['dateline'] = TIMESTAMP;
            $isExists = (int)pdo_getcolumn("microserver_unilink", array('name'=>$manifest['application']['identity']), 'id');
            if (empty($isExists)){
                $uniLink['addtime'] = TIMESTAMP;
                $uniLink['name'] = $manifest['application']['identity'];
                return pdo_insert("microserver_unilink", $uniLink);
            }
            return pdo_update("microserver_unilink", $uniLink, array('id'=>$isExists));
        }
        return pdo_delete("microserver_unilink", array("name"=>$manifest['application']['identity']));
    }

    public function uninstall($identity){
        $service = self::getone($identity, false);
        if (!empty($service)){
            $depends = self::checkDepend($identity);
            if (is_error($depends)) return $depends;
            try {
                script_run($service['configs']['uninstall'], MICRO_SERVER.$identity);
            }catch (\Exception $exception){
                $this->TerminalSend(["mode"=>"err", "message"=>$exception->getMessage()]);
                return error(-1,"卸载失败：".$exception->getMessage());
            }
            if (!pdo_delete(self::$tableName,array('id'=>$service['id']))){
                return error(-1,'卸载失败，请重试');
            }
            $this->getEvents(true);
            pdo_delete("microserver_unilink", array("name"=>$identity));
            $composerExists = file_exists(MICRO_SERVER.$identity."/composer.json");
            if ($composerExists){
                $res = self::ComposerRemove("microserver/".$identity);
                if (is_error($res)) return $res;
            }
        }
        if (!DEVELOPMENT){
            //删除服务安装包
            FileService::rmdirs(MICRO_SERVER.$identity."/");
        }
        return true;
    }

    public static function TerminalSend($data, $finish=false){
        global $_W;
        $data['type'] = 'terminal';
        $data['finish'] = $finish;
        $userIds = md5($_W['config']['setting']['authkey'].":terminal:{$_W['uid']}");
        $swaSocket = serv('websocket');
        if ($swaSocket->enabled && method_exists($swaSocket, 'Send')){
            return $swaSocket->Send($data, $userIds, 0);
        }
        $sendData = array(
            'message'=>json_encode($data),
            'userIds'=>$userIds,
            'fromId'=>0,
            'token'=>$_W['token'],
            'siteRoot'=>$_W['siteroot']
        );
        $res =  HttpService::ihttp_post('https://socket.whotalk.com.cn/api/message/sendMessageToUser', $sendData);
        if(is_error($res)) return $res;
        return json_decode($res['content'],true);
    }

    /**
     * 自动安装Composer依赖
     * @param string $basePath composer.json路径
     * @param string $name 包名称
     * @return bool 安装结果
    */
    public static function ComposerRequire($basePath, $name){
        $composer = $basePath."composer.json";
        $startTime = time();
        if (!file_exists($composer)) return true;
        $WorkingDirectory = base_path() . "/";
        if (DEVELOPMENT){
            if (file_exists($basePath."composer.lock")){
                return self::ComposerUpdate($basePath, $name);
            }
            $WorkingDirectory = $basePath;
            $command = ['composer', 'update'];
        }else{
            $JSON = file_get_contents($composer);
            $composerObj = json_decode($JSON, true);
            $composerVer = trim($composerObj['version']);
            $LOCK = file_get_contents($WorkingDirectory."composer.lock");
            $lockObj = json_decode($LOCK, true);
            if (!empty($lockObj['packages'])){
                foreach ($lockObj['packages'] as $package){
                    if ($package['name']==$name){
                        if (!empty($composerVer) && version_compare($composerVer, $package['version'], '>')){
                            return self::ComposerUpdate($basePath, $name, $composerVer);
                        }
                        return true;
                    }
                }
            }
            $command = ['composer', 'require', $name];
            if (!empty($composerVer)){
                $command[] = $composerVer;
            }
        }
        try {
            $process = new Process($command);
            $process->setWorkingDirectory($WorkingDirectory);
            $process->setEnv(['COMPOSER_HOME'=>self::ComposerHome()]);
            $process->setTimeout(ini_get('max_execution_time')-5);
            $process->run(function ($type, $buffer) {
                self::TerminalSend(["mode"=>str_replace('err', 'warm', $type), "message"=>$buffer]);
            });
            $process->wait();
            if ($process->isSuccessful()) {
                $stopTime = time();
                self::TerminalSend(["mode"=>"success", "message"=>"Composer依赖【{$name}】安装成功！耗时".($stopTime-$startTime)."秒"]);
                return true;
            }else{
                self::ComposerFail($name, $process->getOutput());
            }
        }catch (\Exception $exception){
            //Todo something
            $message = $exception->getMessage();
            self::TerminalSend(["mode"=>"err", "message"=>$message]);
            if (strexists($message, 'exceeded the timeout')){
                self::TerminalSend(["mode"=>"err", "message"=>"Composer安装耗时大于程序最大运行时间(".ini_get('max_execution_time')."秒)，请适当调整该数值后再重试"]);
            }
            self::ComposerFail($name, $message);
        }
        return false;
    }

    public static function ComposerUpdate($basePath, $name, $composerVer=''){
        $startTime = time();
        if (DEVELOPMENT){
            $WorkingDirectory = $basePath;
            $command = ['composer', 'update'];
        }else{
            $WorkingDirectory = base_path("/");
            if (empty($composerVer)){
                $composerVer = "";
                $composer = $basePath."composer.json";
                $JSON = file_get_contents($composer);
                $composerObj = json_decode($JSON, true);
                if (isset($composerObj['version'])){
                    $composerVer = $composerObj['version'];
                }
            }
            $command = ['composer', 'require', $name];
            if (!empty($composerVer)){
                $command[] = $composerVer;
            }
        }
        try {
            $process = new Process($command);
            $process->setWorkingDirectory($WorkingDirectory);
            $process->setEnv(['COMPOSER_HOME'=>self::ComposerHome()]);
            $process->setTimeout(ini_get('max_execution_time')-5);
            $process->run(function ($type, $buffer) {
                self::TerminalSend(["mode"=>str_replace('err', 'warm', $type), "message"=>$buffer]);
            });
            $process->wait();
            if ($process->isSuccessful()) {
                $stopTime = time();
                self::TerminalSend(["mode"=>"success", "message"=>"Composer依赖【{$name}】更新成功！耗时".($stopTime-$startTime)."秒"]);
                return true;
            }else{
                self::ComposerFail($name, $process->getOutput(), $command);
            }
        }catch (\Exception $exception){
            //Todo something
            $message = $exception->getMessage();
            self::TerminalSend(["mode"=>"err", "message"=>$message]);
            if (strexists($message, 'exceeded the timeout')){
                self::TerminalSend(["mode"=>"err", "message"=>"Composer安装耗时大于程序最大运行时间(".ini_get('max_execution_time')."秒)，请适当调整该数值后再重试"]);
            }
            self::ComposerFail($name, $message);
        }
        return false;
    }

    public static function ComposerRemove($require){
        if (DEVELOPMENT){
            self::TerminalSend(["mode"=>"warm", "message"=>"请手动删除微服务的Composer依赖包"]);
            return true;
        }
        $startTime = time();
        $WorkingDirectory = base_path()."/";
        try {
            $process = new Process(["composer", "remove", $require]);
            $process->setWorkingDirectory($WorkingDirectory);
            $process->setEnv(['COMPOSER_HOME'=>self::ComposerHome()]);
            $process->setTimeout(ini_get('max_execution_time'));
            $process->run(function ($type, $buffer) {
                self::TerminalSend(["mode"=>str_replace('err', 'warm', $type), "message"=>$buffer]);
            });
            $process->wait();
            if ($process->isSuccessful()) {
                $stopTime = time();
                self::TerminalSend(["mode"=>"success", "message"=>"Composer依赖【{$require}】卸载完成！耗时".($stopTime-$startTime)."秒"]);
                return true;
            }
        }catch (\Exception $exception){
            //Todo something
        }
        self::TerminalSend(["mode"=>"warm", "message"=>"Composer依赖卸载失败，请使用宝塔终端或其它ssh依次运行如下指令（执行完后请刷新此页面）"]);
        self::TerminalSend(["mode"=>"cmd", "message"=>"cd ".$WorkingDirectory]);
        self::TerminalSend(["mode"=>"cmd", "message"=>"composer remove $require"]);
        $path = str_replace(array('addons', 'microserver'), array('public/addons', 'servers'), $require);
        self::TerminalSend(["mode"=>"cmd", "message"=>"rm -rf ".str_replace('\\', "/", base_path($path))]);
        return error(-1, "Composer依赖【{$require}】卸载失败");
    }

    public static function ComposerFail($name, $output, $command=[]){
        $logPath = MICRO_SERVER . str_replace("microserver/", "", $name) . "/composer.error";
        if (!empty($command)){
            $output = implode(" ", $command) . "：" . $output;
        }
        file_put_contents($logPath, $output);
    }

    public static function ComposerHome(){
        $php_uname = php_uname();
        if (strexists($php_uname, "Windows")){
            return "C:\Users\<user>\AppData\Roaming\Composer";
        }elseif (strexists($php_uname, "Linux")){
            return "/root/.composer";
        }elseif (strexists($php_uname, "nux")){
            return "/home/<user>/.composer";
        }elseif (strexists($php_uname, 'OSX')){
            return '/Users/<user>/.composer';
        }
        return "";
    }

    public static function disable($identity){
        $depends = self::checkDepend($identity);
        if (is_error($depends)){
            return $depends;
        }
        if (pdo_update(self::$tableName, array('status'=>0,'dateline'=>TIMESTAMP), array('identity'=>trim($identity)))){
            pdo_update('microserver_unilink', array('status'=>0,'dateline'=>TIMESTAMP), array('name'=>trim($identity)));
            return true;
        }
        return false;
    }

    public static function restore($identity){
        if (pdo_update(self::$tableName, array('status'=>1,'dateline'=>TIMESTAMP), array('identity'=>trim($identity)))){
            pdo_update('microserver_unilink', array('status'=>1,'dateline'=>TIMESTAMP), array('name'=>trim($identity)));
            return true;
        }
        return false;
    }

    public static function showparams($params=array(),$inuse=false){
        if (empty($params)) return '';
        $data = array();
        foreach ($params as $key=>$value){
            $param = '$'.$key;
            if ($inuse && !empty($value[1]) && strpos($value[1],'null')!==false){
                $format = explode('|', $value[1])[0];
                switch ($format){
                    case 'string':{
                        $param .= "=''";
                        break;
                    }
                    case 'numeric':{
                        $param .= "=0";
                        break;
                    }
                    case 'array':{
                        $param .= "=[]";
                        break;
                    }
                    default:{
                        break;
                    }
                }
            }
            $data[] = $param;
        }
        return empty($data) ? '' : implode(", ",$data);
    }

    public static function getlocal($path=''){
        $servers = array();
        $serverpath = MICRO_SERVER;
        if(!empty($path)){
            $serverpath = $path;
        }
        $manifests = FileService::file_tree($serverpath,array('*/manifest.json'));
        if ($manifests){
            foreach ($manifests as $manifest){
                $service = json_decode(@file_get_contents($manifest), true);
                if (!empty($service) && isset($service['application'])){
                    if (self::isexist($service['application']['identity'])) continue;
                    $serv = $service['application'];
                    $serv['actions'] = '<a class="layui-btn layui-btn-sm layui-btn-normal js-terminal" data-text="确定要安装该服务？" href="'.wurl('server', array("op"=>"install", "nid"=>$serv['identity'])).'">安装</a>';
                    $serv['status'] = -1;
                    $serv['isdelete'] = false;
                    $servers[$serv['identity']] = $serv;
                }
            }
        }
        if (empty($path) && defined('MSERVER_EXTRA')){
            $extraserver = self::getlocal(MSERVER_EXTRA);
            return array_merge($extraserver, $servers);
        }
        return $servers;
    }

    public static function getEvents($rebuild=false){
        $events = array();
        $servers = self::getservers();
        if (!empty($servers)){
            foreach ($servers as $serv){
                $service = serv($serv['identity']);
                if (!method_exists($service, "getMethods")) continue;
                $event = $service->service['events'];
                if (!empty($event)){
                    foreach ($event as $ev){
                        if (!isset($events[$ev])){
                            $events[$ev] = array($serv['identity']);
                        }else{
                            $events[$ev][] = $serv['identity'];
                        }
                    }
                }
            }
        }
        if ($rebuild){
            $cachekey = 'GLOBALS_EVENTS';
            $globalEvents = Cache::get($cachekey, array());
            $globalEvents['microserver'] = $events;
            $globalEvents['dateline'] = TIMESTAMP;
            Cache::put($cachekey, $globalEvents, 7*86400);
        }
        return $events;
    }

}

class MSS extends MSService {}
