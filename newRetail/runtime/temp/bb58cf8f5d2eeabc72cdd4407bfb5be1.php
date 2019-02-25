<?php if (!defined('THINK_PATH')) exit(); /*a:4:{s:81:"/Users/jk/Desktop/obj/newRetail/public/../application/admin/view/Index/index.html";i:1535080857;s:75:"/Users/jk/Desktop/obj/newRetail/application/admin/view/Public/head_top.html";i:1532945889;s:76:"/Users/jk/Desktop/obj/newRetail/application/admin/view/Public/head_menu.html";i:1535080857;s:76:"/Users/jk/Desktop/obj/newRetail/application/admin/view/Public/left_menu.html";i:1535080857;}*/ ?>
﻿<!--html头-->
<!DOCTYPE HTML>
<html>
<head>
    <meta charset="utf-8">
    <meta name="renderer" content="webkit|ie-comp|ie-stand">
    <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">
    <meta name="viewport" content="width=device-width,initial-scale=1,minimum-scale=1.0,maximum-scale=1.0,user-scalable=no" />
    <meta http-equiv="Cache-Control" content="no-siteapp" />
    <link rel="Bookmark" href="/admin_file/favicon.ico" >
    <link rel="Shortcut Icon" href="/admin_file/favicon.ico" />
    <!--[if lt IE 9]>
    <script type="text/javascript" src="/admin_file/lib/html5shiv.js"></script>
    <script type="text/javascript" src="/admin_file/lib/respond.min.js"></script>
    <![endif]-->
    <!--[if IE 6]>
    <script type="text/javascript" src="/admin_file/lib/DD_belatedPNG_0.0.8a-min.js" ></script>
    <script>DD_belatedPNG.fix('*');</script>
    <![endif]-->
    <title>新零售管理后台</title>
    <meta name="keywords" content="H-ui.admin v3.1,H-ui网站后台模版,后台模版下载,后台管理系统模版,HTML后台模版下载">
    <meta name="description" content="H-ui.admin v3.1，是一款由国人开发的轻量级扁平化网站后台模板，完全免费开源的网站后台管理系统模版，适合中小型CMS后台系统。">

    <link rel="stylesheet" type="text/css" href="/admin_file/static/h-ui/css/H-ui.min.css" />
    <link rel="stylesheet" type="text/css" href="/admin_file/static/h-ui.admin/css/H-ui.admin.css" />
    <link rel="stylesheet" type="text/css" href="/admin_file/lib/Hui-iconfont/1.0.8/iconfont.css" />
    <link rel="stylesheet" type="text/css" href="/admin_file/static/h-ui.admin/skin/default/skin.css" id="skin" />
    <link rel="stylesheet" type="text/css" href="/admin_file/static/h-ui.admin/css/style.css" />

    <style>
        /*分页样式*/
        .pagination{text-align:center;margin-top:20px;margin-bottom: 20px;}
        .pagination li{border:1px solid #e6e6e6;padding: 5px 15px;display: inline-block;}
        .pagination .active{background-color: #46A3FF;color: #fff;border:1px solid #46A3FF;}
        .pagination .disabled{color:#aaa;}
        .input-text, .textarea{
            border-radius: 4px;
            width: 50%;
        }
        .Hui-iconfont{
            font-size: 18px;
            width: 30px;
            display: inline-block;
        }
        label.error {
            position: absolute;
            left: 52%;
            top: 4px;
            color: #ef392b;
            font-size: 12px;
        }
    </style>
</head>

<body>
<!--顶部标题栏-->
<header class="navbar-wrapper">
    <div class="navbar navbar-fixed-top">
        <div class="container-fluid cl">
            <a class="logo navbar-logo f-l mr-10 hidden-xs" href="/admin/index/index">新零售管理后台</a>
            <!--<nav class="nav navbar-nav">-->
                <!--<ul class="cl">-->
                    <!--<li class="dropDown dropDown_hover">-->
                        <!--<a href="javascript:;" class="dropDown_A">-->
                            <!--快捷管理-->
                            <!--<i class="Hui-iconfont">&#xe6d5;</i>-->
                        <!--</a>-->
                        <!--<ul class="dropDown-menu menu radius box-shadow">-->
                            <!--<li><a href="/admin/integralshop/orderList"><i class="Hui-iconfont">&#xe616;</i> 积分商城订单列表</a></li>-->
                            <!--<li><a href="javascript:;"><i class="Hui-iconfont">&#xe66a;</i> 店铺订单列表</a></li>-->
                            <!--<li><a href="javascript:;"><i class="Hui-iconfont">&#xe613;</i> 会员列表</a></li>-->
                            <!--<li><a href="javascript:;"><i class="Hui-iconfont">&#xe620;</i> 交易统计</a></li>-->
                            <!--<li><a href="javascript:;"><i class="Hui-iconfont">&#xe6c5;</i> 短信营销</a></li>-->
                            <!--<li><a href="javascript:;"><i class="Hui-iconfont">&#xe627;</i> 广告管理</a></li>-->
                        <!--</ul>-->
                    <!--</li>-->
                <!--</ul>-->
            <!--</nav>-->
            <nav id="Hui-userbar" class="nav navbar-nav navbar-userbar hidden-xs">
                <ul class="cl">
                    <!--<li>超级管理员</li>-->
                    <li class="dropDown dropDown_hover">

                        <a href="#" class="dropDown_A"><?php echo Session('admin_nickname'); ?> <i class="Hui-iconfont">&#xe6d5;</i></a>
                        <ul class="dropDown-menu menu radius box-shadow">
                            <li>
                                <a data-href="/admin/login/repassword" data-title="修改密码" onClick="Hui_admin_tab(this)">修改密码</a>
                            </li>
                            <li><a href="/admin/login/logout">切换账户</a></li>
                            <li><a href="/admin/login/logout">退出</a></li>
                        </ul>
                    </li>
                    <!--<li id="Hui-msg">-->
                        <!--<a href="#" title="消息">-->
                            <!--<span class="badge badge-danger">1</span>-->
                            <!--<i class="Hui-iconfont" style="font-size:18px">&#xe68a;</i>-->
                        <!--</a>-->
                    <!--</li>-->
                    <li id="Hui-skin" class="dropDown right dropDown_hover">
                        <a href="javascript:;" class="dropDown_A" title="换肤">
                            <i class="Hui-iconfont" style="font-size:18px">&#xe62a;</i>
                        </a>
                        <ul class="dropDown-menu menu radius box-shadow">
                            <li><a href="javascript:;" data-val="default" title="默认（黑色）">默认（黑色）</a></li>
                            <li><a href="javascript:;" data-val="blue" title="蓝色">蓝色</a></li>
                            <li><a href="javascript:;" data-val="green" title="绿色">绿色</a></li>
                            <li><a href="javascript:;" data-val="red" title="红色">红色</a></li>
                            <li><a href="javascript:;" data-val="yellow" title="黄色">黄色</a></li>
                            <li><a href="javascript:;" data-val="orange" title="橙色">橙色</a></li>
                        </ul>
                    </li>
                </ul>
            </nav>
        </div>
    </div>
</header>

<!--左侧菜单栏-->

<aside class="Hui-aside">
    <div class="menu_dropdown bk_2">
        <?php if(is_array($leftMenu) || $leftMenu instanceof \think\Collection || $leftMenu instanceof \think\Paginator): $i = 0; $__LIST__ = $leftMenu;if( count($__LIST__)==0 ) : echo "" ;else: foreach($__LIST__ as $key=>$f): $mod = ($i % 2 );++$i;?>
            <dl id="menu-product">
                <dt style="line-height: 40px;">
                    <i class="Hui-iconfont" style="color: #666;width: 18px;">&#<?php echo $f['icone']; ?>;</i> <?php echo $f['action_name']; ?>
                <dd>
                    <ul>
                        <?php if(is_array($f['children']) || $f['children'] instanceof \think\Collection || $f['children'] instanceof \think\Paginator): $i = 0; $__LIST__ = $f['children'];if( count($__LIST__)==0 ) : echo "" ;else: foreach($__LIST__ as $key=>$c): $mod = ($i % 2 );++$i;?>
                            <li><a data-href="/admin/<?php echo $c['action_path']; ?>/<?php echo $c['action_code']; ?>" data-title="<?php echo $c['action_name']; ?>" href="javascript:void(0)"><?php echo $c['action_name']; ?></a></li>
                        <?php endforeach; endif; else: echo "" ;endif; ?>
                    </ul>
                </dd>
            </dl>
        <?php endforeach; endif; else: echo "" ;endif; ?>
    </div>
</aside>
<!--<style type="text/css">-->
    <!--#Parent{-->
        <!--position: absolute;-->
        <!--top: 44px;-->
        <!--bottom: 0;-->
        <!--left: 0;-->
        <!--padding-top: 10px;-->
        <!--width: 199px;-->
        <!--z-index: 99;-->
        <!--overflow: auto;-->
        <!--background-color: rgba(238,238,238,0.98);-->
        <!--_background-color: rgb(238,238,238);-->
        <!--border-right: 1px solid #e5e5e5;-->
    <!--}-->
    <!--#nav{-->
        <!--width: 199px;-->
        <!--font-size: 14px;-->
        <!--line-height: 40px;-->
    <!--}-->
    <!--#nav li{-->
        <!--width: 199px;-->
        <!--padding-bottom: 1px;-->
        <!--list-style-image: none;-->
        <!--list-style-type: none;-->
        <!--background-color: #FFFFFF;-->
    <!--}-->
    <!--#nav a{-->
        <!--padding-left: 20px;-->
        <!--width:199px;-->
        <!--background-color: #EAEAEA;-->
        <!--display: block;-->
        <!--text-decoration: none;-->
    <!--}-->
    <!--#nav li ul{-->
        <!--padding-top: 1px;-->
        <!--list-style-image: none;-->
        <!--list-style-type: none;-->
    <!--}-->
    <!--.cx {-->
        <!--display:none;-->
        <!--visibility:hidden;-->
    <!--}-->
    <!--.ex {-->
        <!--display:inherit;-->
        <!--visibility:inherit;-->
    <!--}-->
<!--</style>-->
<!--<div id="Parent">-->
    <!--<ul id="nav">-->
        <!--<?php if(is_array($leftMenu) || $leftMenu instanceof \think\Collection || $leftMenu instanceof \think\Paginator): $i = 0; $__LIST__ = $leftMenu;if( count($__LIST__)==0 ) : echo "" ;else: foreach($__LIST__ as $key=>$f): $mod = ($i % 2 );++$i;?>-->
        <!--<li>-->
            <!--<a href="javascript:void(0);" onclick="doMenu(this,'1')"><i class="Hui-iconfont" style="color: #666;width: 18px;">&#<?php echo $f['icone']; ?>;</i> <?php echo $f['action_name']; ?></a>-->
            <!--<ul class="cx">-->
                <!--<?php if(is_array($f['children']) || $f['children'] instanceof \think\Collection || $f['children'] instanceof \think\Paginator): $i = 0; $__LIST__ = $f['children'];if( count($__LIST__)==0 ) : echo "" ;else: foreach($__LIST__ as $key=>$c): $mod = ($i % 2 );++$i;?>-->
                <!--<?php if($c['is_children'] == 1): ?>-->
                <!--<li><a href="javascript:void(0);" onclick="doMenu(this,'2')" > <i class="Hui-iconfont" style="color: #666;width: 18px;">&#<?php echo $f['icone']; ?>;</i> <?php echo $c['action_name']; ?></a>-->
                    <!--<?php if(is_array($c['children']) || $c['children'] instanceof \think\Collection || $c['children'] instanceof \think\Paginator): $i = 0; $__LIST__ = $c['children'];if( count($__LIST__)==0 ) : echo "" ;else: foreach($__LIST__ as $key=>$cc): $mod = ($i % 2 );++$i;?>-->
                    <!--<ul class="cx">-->
                        <!--<li><a href="/admin/<?php echo $f['action_code']; ?>/<?php echo $c['action_code']; ?>"><?php echo $cc['action_name']; ?></a></li>-->
                        <!--<li><a href="/admin/<?php echo $f['action_code']; ?>/<?php echo $c['action_code']; ?>" ><?php echo $cc['action_name']; ?></a></li>-->
                    <!--</ul>-->
                    <!--<?php endforeach; endif; else: echo "" ;endif; ?>-->
                <!--</li>-->
                <!--<?php else: ?>-->
                <!--<li>-->
                    <!--<a href="/admin/<?php echo $f['action_code']; ?>/<?php echo $c['action_code']; ?>" onclick="doMenu(this,'2')" >-->
                        <!--<?php echo $c['action_name']; ?>-->
                    <!--</a>-->
                <!--</li>-->
                <!--<?php endif; ?>-->
                <!--<?php endforeach; endif; else: echo "" ;endif; ?>-->
            <!--</ul>-->
        <!--</li>-->
        <!--<?php endforeach; endif; else: echo "" ;endif; ?>-->
    <!--</ul>-->
<!--</div>-->
<!--<script>-->
    <!--window.onload=function(){-->
        <!--statUp();-->
    <!--}-->
    <!--function doMenu(obj,strDeep){-->
        <!--var items=obj.parentNode.getElementsByTagName("ul");-->
        <!--//获取a 对象你节点li 下包含的 所有ul集合-->
        <!--var itmUl;-->
        <!--var deeps=strDeep; //strDeep 为当前菜单的级数-->
        <!--if(items.length>0){-->
            <!--itmUl=items[0];-->
        <!--}-->
        <!--if(itmUl.className!="ex"){-->
            <!--cxAll();//当前节点为关闭状态时,先执行关闭所有ul子菜单-->
            <!--if(deeps=='2'){ //若要展开三级菜单当,还要将其二级父菜单展开-->
                <!--itmUl.parentNode.parentNode.className="ex";-->

            <!--}-->
            <!--itmUl.className="ex"; //展开下级菜单-->
        <!--}else{-->
            <!--itmUl.className="cx";-->
        <!--}-->
    <!--}-->
    <!--function statUp(){-->
        <!--cxAll();-->
        <!--var ulDom=document.getElementById("nav");-->
        <!--var items=ulDom.getElementsByTagName("ul");-->
    <!--}-->
    <!--function cxAll(){-->
        <!--var ulDom=document.getElementById("nav");-->
        <!--var items=ulDom.getElementsByTagName("ul");-->
        <!--for (var i=0;i<items.length;i++)-->
        <!--{-->
            <!--items[i].className="cx";-->
        <!--}-->
    <!--}-->
<!--</script>-->

<div class="dislpayArrow hidden-xs"><a class="pngfix" href="javascript:void(0);" onClick="displaynavbar(this)"></a>
</div>
<section class="Hui-article-box">
    <div id="Hui-tabNav" class="Hui-tabNav hidden-xs">
        <div class="Hui-tabNav-wp">
            <ul id="min_title_list" class="acrossTab cl">
                <li class="active">
                    <span title="我的桌面" data-href="welcome.html">我的桌面</span>
                    <em></em>
                </li>
            </ul>
        </div>
        <div class="Hui-tabNav-more btn-group">
            <a id="js-tabNav-prev" class="btn radius btn-default size-S" href="javascript:;">
                <i class="Hui-iconfont">&#xe6d4;</i>
            </a>
            <a id="js-tabNav-next" class="btn radius btn-default size-S" href="javascript:;">
                <i class="Hui-iconfont">&#xe6d7;</i>
            </a>
        </div>
    </div>
    <div id="iframe_box" class="Hui-article">
        <div class="show_iframe">
            <div style="display:none" class="loading"></div>
            <iframe scrolling="yes" frameborder="0" src="/admin/index/welcome.html"></iframe>
        </div>
    </div>
</section>

<div class="contextMenu" id="Huiadminmenu">
    <ul>
        <li id="closethis">关闭当前</li>
        <li id="closeall">关闭全部</li>
    </ul>
</div>
<!--_footer 作为公共模版分离出去-->
<script type="text/javascript" src="/admin_file/lib/jquery/1.9.1/jquery.min.js"></script>
<script type="text/javascript" src="/admin_file/lib/layer/2.4/layer.js"></script>
<script type="text/javascript" src="/admin_file/static/h-ui/js/H-ui.min.js"></script>
<script type="text/javascript" src="/admin_file/static/h-ui.admin/js/H-ui.admin.js"></script>
<!--/_footer 作为公共模版分离出去-->

<!--请在下方写此页面业务相关的脚本-->
<script type="text/javascript" src="/admin_file/lib/jquery.contextmenu/jquery.contextmenu.r2.js"></script>
<script type="text/javascript">
    $(function () {
        /*$("#min_title_list li").contextMenu('Huiadminmenu', {
         bindings: {
         'closethis': function(t) {
         console.log(t);
         if(t.find("i")){
         t.find("i").trigger("click");
         }
         },
         'closeall': function(t) {
         alert('Trigger was '+t.id+'\nAction was Email');
         },
         }
         });*/
    });
    /*个人信息*/
    function myselfinfo() {
        layer.open({
            type: 1,
            area: ['300px', '200px'],
            fix: false, //不固定
            maxmin: true,
            shade: 0.4,
            title: '查看信息',
            content: '<div>管理员信息</div>'
        });
    }

    /*资讯-添加*/
    function article_add(title, url) {
        var index = layer.open({
            type: 2,
            title: title,
            content: url
        });
        layer.full(index);
    }
    /*图片-添加*/
    function picture_add(title, url) {
        var index = layer.open({
            type: 2,
            title: title,
            content: url
        });
        layer.full(index);
    }
    /*产品-添加*/
    function product_add(title, url) {
        var index = layer.open({
            type: 2,
            title: title,
            content: url
        });
        layer.full(index);
    }
    /*用户-添加*/
    function member_add(title, url, w, h) {
        layer_show(title, url, w, h);
    }


</script>

<!--此乃百度统计代码，请自行删除-->
<script>
    var _hmt = _hmt || [];
    (function () {
        var hm = document.createElement("script");
        hm.src = "https://hm.baidu.com/hm.js?080836300300be57b7f34f4b3e97d911";
        var s = document.getElementsByTagName("script")[0];
        s.parentNode.insertBefore(hm, s);
    })();
</script>
<!--/此乃百度统计代码，请自行删除-->
</body>
</html>