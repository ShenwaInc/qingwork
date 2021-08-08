<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="renderer" content="webkit">
    <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
    <meta name="apple-mobile-web-app-status-bar-style" content="black">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="format-detection" content="telephone=no">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="shortcut icon" href="/favicon.ico" />
    <title>Whotalk安装向导</title>
    <link rel="stylesheet" href="/static/layui/css/layui.css?v={{ $_W['config']['release'] }}" />
    <link rel="stylesheet" href="/static/fului/fului-for-lay.css?v={{ $_W['config']['release'] }}" />
    <link rel="stylesheet" href="/static/installer/style.css?v={{ $_W['config']['release'] }}" />
    <script type="text/javascript" src="/static/layui/layui.js?v={{ $_W['config']['release'] }}"></script>
    <script type="text/javascript" src="/static/js/jquery-1.11.1.min.js?v={{ $_W['config']['release'] }}"></script>
    <script type="text/javascript" src="/static/js/core.jquery.js?v={{ $_W['config']['release'] }}"></script>
</head>

<body class="layui-layout-admin">
    <div class="layui-body" style="left: 0; top: 0">
        <div class="main-panel">