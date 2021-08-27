@if(!$_W['isajax'])
@include('common.headerbase')
<link rel="stylesheet" href="{{ asset('/static/css/console.css') }}?v={{ $_W['config']['release'] }}" />
<body layadmin-themealias="ocean-header" class="layui-layout-body" style="position:inherit !important;">
<div class="layui-layout layui-layout-admin">
    <div class="layui-header">
        <div class="layui-logo layui-hide-xs">
            <a href="{{ url('console') }}">{{ $_W['config']['name'] }}</a>
        </div>

        <ul class="layui-nav layui-layout-left layui-hide">
        </ul>

        <ul class="layui-nav layui-layout-right">
            <li class="layui-nav-item layui-hide layui-show-md-inline-block">
                <a href="javascript:;">
                    {{$_W['user']['username']}}
                </a>
                <dl id="layui-admin-usermenu" class="layui-nav-child layui-anim layui-anim-upbit">
                    <dd><a href="{{ wurl('user/profile') }}">账户管理</a></dd>
                    @if($_W['isfounder'])
                        @if($_W['config']['site']['id']==0)
                        <dd>
                            <a href="{{url('console/active')}}">系统激活<span class="layui-badge-dot"></span></a>
                        </dd>
                        @else
                        <dd><a href="{{url('console/setting')}}">站点设置</a></dd>
                        @endif
                    @endif
                    <dd><a href="javascript:Core.logout();">退出账户</a></dd>
                </dl>
            </li>
            <li class="layui-nav-item layui-hide-xs js-fullscreen" lay-unselect>
                <a href="javascript:;" layadmin-event="fullscreen">
                    <i class="layui-icon layui-icon-screen-full"></i>
                </a>
            </li>
        </ul>
    </div>
    <div class="layui-body">
@endif
