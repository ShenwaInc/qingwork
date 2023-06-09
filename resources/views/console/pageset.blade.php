@include('common.header')

@if(!$_W['isajax'])
    <div class="main-content">
        <h2>编辑站点信息</h2>
        <div class="fui-card layui-card">
            <div class="layui-card-body">
@else
                <div class="padding">
@endif

                    <div class="fui-form">
                        <form action="{{ url('console/setting') }}" method="post" class="layui-form">
                            @csrf
                            <input type="hidden" name="op" value="pageset">
                            <div class="layui-form-item must">
                                <label class="layui-form-label">站点名称</label>
                                <div class="layui-input-block">
                                    <input type="text" name="data[title]" required lay-verify="required" value="{{ $_W['setting']['page']['title'] }}" placeholder="请输入站点名称" autocomplete="off" class="layui-input">
                                </div>
                            </div>
                            <div class="layui-form-item must">
                                <label class="layui-form-label">站点LOGO</label>
                                {!! serv('storage')->tpl_form_image('data[logo]', $_W['setting']['page']['logo'],array('required'=>true,'placeholder'=>'请选择图片上传')); !!}
                            </div>
                            <div class="layui-form-item must">
                                <label class="layui-form-label">站点标志</label>
                                {!! serv('storage')->tpl_form_image('data[icon]', $_W['setting']['page']['icon'],array('required'=>true,'placeholder'=>'请选择图片上传')); !!}
                            </div>
                            <div class="layui-form-item">
                                <label class="layui-form-label">SEO关键字</label>
                                <div class="layui-input-block">
                                    <input type="text" name="data[keywords]" value="{{ $_W['setting']['page']['keywords'] }}" placeholder="请输入SEO关键字" autocomplete="off" class="layui-input">
                                </div>
                            </div>
                            <div class="layui-form-item">
                                <label class="layui-form-label">SEO描述</label>
                                <div class="layui-input-block">
                                    <input type="text" name="data[description]" value="{{ $_W['setting']['page']['description'] }}" placeholder="请输入SEO描述" autocomplete="off" class="layui-input">
                                </div>
                            </div>
                            <div class="layui-form-item">
                                <label class="layui-form-label">底部版权信息</label>
                                <div class="layui-input-block">
                                    <input type="text" name="data[copyright]" value="{{ $_W['setting']['page']['copyright'] }}" placeholder="请输入站点底部版权信息" autocomplete="off" class="layui-input">
                                </div>
                            </div>
                            <div class="layui-form-item">
                                <label class="layui-form-label">底部导航连接</label>
                                <div class="layui-input-block">
                                    <textarea class="layui-textarea" name="data[links]">{{ $_W['setting']['page']['links'] }}</textarea>
                                </div>
                            </div>
                            <div class="layui-form-item">
                                <div class="layui-input-block">
                                    <button class="layui-btn layui-btn-normal" lay-submit type="submit" value="true" name="savedata">保存</button>
                                    <button type="reset" class="layui-btn layui-btn-primary">重填</button>
                                </div>
                            </div>
                        </form>
                    </div>

    @if(!$_W['isajax'])
        </div>
    </div>
    @endif

</div>
@include('common.footer')
