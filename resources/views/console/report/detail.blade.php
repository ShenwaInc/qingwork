@include('common.header')
<div class="layui-fluid">
    <div class="@if(empty($_GPC['inajax'])) main-content @endif">
        <div class="fui-card layui-card unpadding">
            @if(!$_W['isajax'])
            <div class="layui-card-header nobd">
                <a href="" class="fr text-blue ajaxshow" title="编辑站点信息"><i class="fa fa-edit"></i></a>
                <span class="title">{{ $title }}</span>
            </div>
            @endif
            <div class="layui-card-body">
                <div class="un-padding">
                    <div class="layui-tab layui-tab-brief" lay-filter="docDemoTabBrief">
                        <ul class="layui-tab-title">
                            <li @if(empty($feedbacks)) class="layui-this" @endif >工单详情</li>
                            <li @if(!empty($feedbacks)) class="layui-this" @endif >在线沟通</li>
                            <li>工单日志</li>
                        </ul>
                        <div class="layui-tab-content">
                            <div class="layui-tab-item @if(empty($feedbacks)) layui-show @endif ">
                                <table class="layui-table fui-table lines" lay-skin="nob">
                                    <colgroup>
                                        <col width="120"/>
                                        <col/>
                                    </colgroup>
                                    <tbody>
                                    <tr>
                                        <td><span class="fui-table-lable">工单号</span></td>
                                        <td class="soild-after">{{ $orderInfo['ordersn'] }}&nbsp;<span class="text-blue js-clip" data-url="{{ $orderInfo['ordersn'] }}" style="cursor: pointer;">复制</span></td>
                                    </tr>
                                    <tr>
                                        <td><span class="fui-table-lable">工单内容</span></td>
                                        <td class="soild-after" style="white-space: pre-wrap">{{ $orderInfo['content'] }}</td>
                                    </tr>
                                    @if(!empty($orderInfo['secret']))
                                        <tr>
                                            <td><span class="fui-table-lable">机密信息</span></td>
                                            <td class="soild-after" id="orderSecret">
                                                <div class="order-secret">{{ $orderInfo['secret'] }}</div>
                                                <span class="text-blue js-secret" style="cursor: pointer;">展开</span>
                                            </td>
                                        </tr>
                                    @endif
                                    <tr>
                                        <td><span class="fui-table-lable">相关附件</span></td>
                                        <td class="soild-after">
                                            @if(!empty($orderInfo['fileList']))
                                                @foreach($orderInfo['fileList'] as $value)
                                                    <p><a href="{{ $value['url'] }}" class="text-blue" target="_blank">{{ $value['name'] }}</a></p>
                                                @endforeach
                                            @else
                                                暂无
                                            @endif
                                        </td>
                                    </tr>
                                    <tr>
                                        <td><span class="fui-table-lable">提交时间</span></td>
                                        <td class="soild-after">{{ $orderInfo['created_at'] }}</td>
                                    </tr>
                                    <tr>
                                        <td><span class="fui-table-lable">工单状态</span></td>
                                        <td class="soild-after">{{ $orderInfo['statusName'] }}</td>
                                    </tr>
                                    </tbody>
                                </table>
                            </div>
                            <div class="layui-tab-item order-log @if(!empty($feedbacks)) layui-show @endif ">
                                <div id="orderDialog">
                                    @if(empty($feedbacks))
                                        <div class="fui-empty">
                                            <span>暂无沟通记录</span>
                                        </div>
                                    @else
                                    <div class="dialog-list">
                                    @foreach($feedbacks as $key=>$value)
                                        <div class="dialog-item dialog-from{{ $value['my'] }}">
                                            <div class="dialog-avatar">
                                                <img height="48" alt="{{ $value['my'] ? $_W['user']['username'] : $value['name'] }}" src="{{ $value['my'] ? tomedia($_W['user']['avatar']) : $value['avatar'] }}" />
                                            </div>
                                            <div class="dialog-content">
                                                <p><span class="dialog-nick">{{ $value['my'] ? $_W['user']['username'] : $value['name'] }}</span>&nbsp;&nbsp;&nbsp;{{ $value['created_at'] }}</p>
                                                @if($value['type']=='img')
                                                    <div class="dialog-message msg-img"><img src="{{ $value['content'] }}" alt="沟通图片" /></div>
                                                @elseif($value['type']=='text')
                                                    <div class="dialog-message msg-text">{!! $value['content'] !!}</div>
                                                @else
                                                    <div class="dialog-message msg-file"><a href="{{ $value['content'] }}" class="text-blue" target="_blank">下载附件</a></div>
                                                @endif
                                            </div>
                                        </div>
                                    @endforeach
                                    </div>
                                    @endif
                                </div>
                            </div>
                            <div class="layui-tab-item order-log">
                                @if(empty($logs))
                                    <div class="fui-empty">
                                        <span>暂无记录</span>
                                    </div>
                                @else
                                    @foreach($logs as $key=>$value)
                                    <div class="row">
                                        <div class="layui-col-md8 text-cut">{{ $value['content'] }}</div>
                                        <div class="layui-col-md4 text-right"><span class="text-gray">{{ $value['created_at'] }}</span></div>
                                    </div>
                                    @endforeach
                                @endif
                            </div>
                        </div>
                    </div>
                    <div class="text-right">
                        @if($orderInfo['status']<6)
                            <a class="layui-btn layui-btn-normal ajaxshow" href="{{ wurl('report/feedback', array('id'=>$orderInfo['id'])) }}">补充反馈</a>
                            <a class="layui-btn confirm ajaxshow" data-text="确定此工单已完成验收吗？" href="{{ wurl('report/Complete', array('id'=>$orderInfo['id'])) }}">完成工单</a>
                            <a class="layui-btn layui-btn-warm confirm ajaxshow layui-hide" data-text="确定要关闭该工单吗？" href="{{ wurl('report/closeOrder', array('id'=>$orderInfo['id'])) }}">关闭工单</a>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<style>
    .order-secret{display: none;}
    .layui-show > .order-secret{display: block; white-space: pre-wrap}
    .fui-empty{min-height: 300px; padding-top: 100px;}
    .order-log{min-height: 400px;}
    #orderDialog{background-color: #f1f1f1; height: 480px; overflow: hidden; overflow-y: auto;}
    .dialog-item{padding: 12px 0 12px 72px; position: relative; display: flex;}
    .dialog-avatar{position: absolute; top: 15px; left: 12px; width: 48px; height: 48px; overflow: hidden; border-radius: 50%; text-align: center; background-color: #ffffff;}
    .dialog-avatar img{min-width: 48px; max-width: 72px;}
    .dialog-item p{color: #aaaaaa;}
    .dialog-content{flex: 1;}
    .dialog-message{display: inline-flex; align-items: center; max-width: calc(100% - 200px); line-height: 20px; margin-top: 5px; position: relative; padding: 10px; border-radius: 3px; background-color: #ffffff; white-space: pre-wrap;}
    .dialog-message img{max-height: 180px; max-width: 100%;}
    .dialog-message:before{content: ""; top: 15px; transform: rotate(45deg);position: absolute; display: inline-block;overflow: hidden;width: 12px;height: 12px;left: -6px;right: auto;background-color: inherit;}
    .dialog-message:after{content: ""; top: 15px; transform: rotate(45deg);position: absolute; z-index: -1;display: inline-block;overflow: hidden;width: 12px;height: 12px; right: -6px;background-color: inherit;}
    .dialog-from1{padding-left: 0; padding-right: 72px; text-align: right; justify-content: flex-end;}
    .dialog-from1 .dialog-message{justify-content: flex-end; background-color: #5FB878;}
    .dialog-from1 .dialog-message:after{z-index: 0;}
    .dialog-from1 .dialog-message:before{z-index: -1;}
    .dialog-from1 .dialog-avatar{right: 12px; left: unset;}
    .dialog-from1 .dialog-nick{float: right; margin-left: 15px;}
</style>
<script type="text/javascript">
    $(function () {
        let orderDialog = $('#orderDialog');
        let DialogList = orderDialog.find('.dialog-list');
        if(DialogList.length>0){
            setTimeout(function () {
                orderDialog.scrollTop(DialogList.height());
            }, 500)
        }
        $('.js-secret').click(function () {
            let Elem = $('#orderSecret');
            if(Elem.hasClass('layui-show')){
                Elem.removeClass('layui-show');
                $(this).text('展开');
            }else{
                Elem.addClass('layui-show');
                $(this).text('收起');
            }
        })
    });
</script>
@include('common.footer')
