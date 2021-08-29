@include('common.header')

@if(!$_W['isajax'])
<div class="main-content">
    <h2 class="weui-desktop-page__title">创建新平台</h2>
    <div class="fui-card layui-card">
        <div class="layui-card-body">
            <div class="un-padding">
                @else
                <div class="padding">
                @endif
                <form action="{{ wurl('account/create') }}" method="post" class="layui-form">
                    @csrf
                    <div class="layui-form-item must">
                        <label class="layui-form-label">平台名称</label>
                        <div class="layui-input-block">
                            <input type="text" required lay-verify="required" name="data[name]" value="" placeholder="请输入平台名称" autocomplete="off" class="layui-input" />
                        </div>
                    </div>
                    <div class="layui-form-item">
                        <label class="layui-form-label">平台简介</label>
                        <div class="layui-input-block">
                            <input type="text" name="data[description]" value="" placeholder="请输入平台简介" class="layui-input" />
                        </div>
                    </div>
                    <div class="layui-form-item must">
                        <label class="layui-form-label">平台LOGO</label>
                        <div class="layui-input-block fui-upload">
                            <div class="layui-input-inline">
                                <input type="text" name="data[logo]" readonly required lay-verify="required" value="" placeholder="请选择图片上传" autocomplete="off" class="layui-input">
                            </div>
                            <div class="layui-btn-group">
                                <a title="站点LOGO" href="javascript:;" layadmin-event="previewimg" data-src="{{ asset('static/images/nopic.jpg') }}" class="layui-btn layui-btn-normal"><span class="layui-icon layui-icon-picture"></span></a>
                                <button type="button" class="layui-btn js-uploadimg">上传图片</button>
                            </div>
                        </div>
                    </div>
                    <div class="layui-form-item">
                        <div class="layui-input-block">
                            <button class="layui-btn layui-btn-normal" lay-submit type="submit" value="true" name="savedata">创建</button>
                            <button type="reset" class="layui-btn layui-btn-primary">重填</button>
                        </div>
                    </div>
                </form>
            </div>
            @if(!$_W['isajax'])
        </div>
    </div>
</div>
@endif

@include('common.footer')
