<?php if (!defined('THINK_PATH')) exit(); /*a:2:{s:83:"/Users/jk/Desktop/obj/newRetail/public/../application/admin/view/Index/welcome.html";i:1535080857;s:75:"/Users/jk/Desktop/obj/newRetail/application/admin/view/Public/head_top.html";i:1532945889;}*/ ?>
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
<div class="page-container">
	<p class="f-20 text-success">欢迎使用新零售管理后台！</p>
	<p>登录次数：<?php echo Session("login_count"); ?> </p>
	<p>上次登录IP：<?php echo Session("last_ip"); ?>  上次登录时间：<?php echo Session("last_login_time"); ?></p>
	<table class="table table-border table-bordered table-bg">
		<thead>
			<tr>
				<th colspan="7" scope="col">信息统计</th>
			</tr>
		</thead>
		<tbody>
			<tr class="text-c">
				<td>积分商城统计</td>
				<td>今日订单总数：<?php echo $order_count; ?></td>
				<td>今日销售总额：<?php echo $order_money; ?></td>
				<td>昨日销售总额：<?php echo $order_money_yestoday; ?></td>
				<td>近七天销售总额：<?php echo $order_money_seven; ?></td>
			</tr>
			<tr class="text-c">
				<td>店铺统计</td>
				<td>今日订单总数：<?php echo $store_order_count; ?></td>
				<td>今日销售总额：<?php echo $store_order_money; ?></td>
				<td>昨日销售总额：<?php echo $store_order_money_yestoday; ?></td>
				<td>近七天销售总额：<?php echo $store_order_money_seven; ?></td>
			</tr>
		</tbody>
	</table>

	<table class="table table-border table-bordered table-bg" style="margin-top: 20px;">
		<thead>
			<tr>
				<th colspan="7" scope="col">待处理事务</th>
			</tr>
		</thead>
		<tbody>
			<tr class="text-c">
				<td>待审核店铺：<?php echo $order_money; ?></td>
				<td>即将到期广告：<?php echo $order_money_yestoday; ?></td>
			</tr>
		</tbody>
	</table>

	<table class="table table-border table-bordered table-bg" style="margin-top: 20px;">
		<thead>
		<tr>
			<th colspan="7" scope="col">快捷操作</th>
		</tr>
		</thead>
		<tbody>
		<tr class="text-c">
			<td width="20%">
				<a href="/admin/Integralshop/orderList" style="text-decoration:none;">
					<i class="Hui-iconfont" style="padding-top: 10px;font-size: 30px;">&#xe616;</i></br>
					<span>积分商城订单列表</span>
				</a>
			</td>
			<td width="20%">
				<a href="/admin/Report/storefororderlist" style="text-decoration:none">
					<i class="Hui-iconfont" style="padding-top: 10px;font-size: 30px;">&#xe66a;</i> </br>店铺订单列表
				</a>
			</td>
			<td width="20%">
				<a href="/admin/User/userList" style="text-decoration:none">
					<i class="Hui-iconfont" style="padding-top: 10px;font-size: 30px;">&#xe62c;</i></br> 会员列表
				</a>
			</td>
			<td width="20%">
				<a href="/admin/Report/storeClear" style="text-decoration:none">
					<i class="Hui-iconfont" style="padding-top: 10px;font-size: 30px;">&#xe620;</i> </br>店铺账单
				</a>
			</td>
			<td width="20%">
				<a href="/admin/Banner/bannerlist" style="text-decoration:none">
					<i class="Hui-iconfont" style="padding-top: 10px;font-size: 30px;">&#xe627;</i> </br>广告管理
				</a>
			</td>
		</tr>
		</tbody>
	</table>

	<table class="table table-border table-bordered table-bg" style="margin-top: 20px;">
		<thead>
		<tr>
			<th colspan="7" scope="col">登录日志</th>
		</tr>
		<tr class="text-c">
			<th>昵称</th>
			<th>操作</th>
			<th>登录IP</th>
			<th>时间</th>
		</tr>
		</thead>
		<tbody>
		<?php if(is_array($adminLog) || $adminLog instanceof \think\Collection || $adminLog instanceof \think\Paginator): $i = 0; $__LIST__ = $adminLog;if( count($__LIST__)==0 ) : echo "" ;else: foreach($__LIST__ as $key=>$log): $mod = ($i % 2 );++$i;?>
		<tr class="text-c">
			<td><?php echo $log['admin_nickname']; ?></td>
			<td><?php echo $log['content']; ?></td>
			<td><?php echo $log['ip']; ?></td>
			<td><?php echo $log['create_time']; ?></td>
		</tr>
		<?php endforeach; endif; else: echo "" ;endif; ?>
		</tbody>
	</table>

	<!--<table class="table table-border table-bordered table-bg mt-20">-->
		<!--<thead>-->
			<!--<tr>-->
				<!--<th colspan="2" scope="col">服务器信息</th>-->
			<!--</tr>-->
		<!--</thead>-->
		<!--<tbody>-->
			<!--<tr>-->
				<!--<th width="30%">服务器计算机名</th>-->
				<!--<td><span id="lbServerName">http://127.0.0.1/</span></td>-->
			<!--</tr>-->
			<!--<tr>-->
				<!--<td>服务器IP地址</td>-->
				<!--<td><?php echo $serverInfo['serverIp']; ?></td>-->
			<!--</tr>-->
			<!--<tr>-->
				<!--<td>服务器域名</td>-->
				<!--<td><?php echo $serverInfo['serverHost']; ?></td>-->
			<!--</tr>-->
			<!--<tr>-->
				<!--<td>服务器端口 </td>-->
				<!--<td>80</td>-->
			<!--</tr>-->
			<!--<tr>-->
				<!--<td>服务器IIS版本 </td>-->
				<!--<td>Microsoft-IIS/6.0</td>-->
			<!--</tr>-->
			<!--<tr>-->
				<!--<td>本文件所在文件夹 </td>-->
				<!--<td>D:\WebSite\HanXiPuTai.com\XinYiCMS.Web\</td>-->
			<!--</tr>-->
			<!--<tr>-->
				<!--<td>服务器操作系统 </td>-->
				<!--<td>Microsoft Windows NT 5.2.3790 Service Pack 2</td>-->
			<!--</tr>-->
			<!--<tr>-->
				<!--<td>系统所在文件夹 </td>-->
				<!--<td>C:\WINDOWS\system32</td>-->
			<!--</tr>-->
			<!--<tr>-->
				<!--<td>服务器脚本超时时间 </td>-->
				<!--<td>30000秒</td>-->
			<!--</tr>-->
			<!--<tr>-->
				<!--<td>服务器的语言种类 </td>-->
				<!--<td>Chinese (People's Republic of China)</td>-->
			<!--</tr>-->
			<!--<tr>-->
				<!--<td>.NET Framework 版本 </td>-->
				<!--<td>2.050727.3655</td>-->
			<!--</tr>-->
			<!--<tr>-->
				<!--<td>服务器当前时间 </td>-->
				<!--<td>2014-6-14 12:06:23</td>-->
			<!--</tr>-->
			<!--<tr>-->
				<!--<td>服务器IE版本 </td>-->
				<!--<td>6.0000</td>-->
			<!--</tr>-->
			<!--<tr>-->
				<!--<td>服务器上次启动到现在已运行 </td>-->
				<!--<td>7210分钟</td>-->
			<!--</tr>-->
			<!--<tr>-->
				<!--<td>逻辑驱动器 </td>-->
				<!--<td>C:\D:\</td>-->
			<!--</tr>-->
			<!--<tr>-->
				<!--<td>CPU 总数 </td>-->
				<!--<td>4</td>-->
			<!--</tr>-->
			<!--<tr>-->
				<!--<td>CPU 类型 </td>-->
				<!--<td>x86 Family 6 Model 42 Stepping 1, GenuineIntel</td>-->
			<!--</tr>-->
			<!--<tr>-->
				<!--<td>虚拟内存 </td>-->
				<!--<td>52480M</td>-->
			<!--</tr>-->
			<!--<tr>-->
				<!--<td>当前程序占用内存 </td>-->
				<!--<td>3.29M</td>-->
			<!--</tr>-->
			<!--<tr>-->
				<!--<td>Asp.net所占内存 </td>-->
				<!--<td>51.46M</td>-->
			<!--</tr>-->
			<!--<tr>-->
				<!--<td>当前Session数量 </td>-->
				<!--<td>8</td>-->
			<!--</tr>-->
			<!--<tr>-->
				<!--<td>当前SessionID </td>-->
				<!--<td>gznhpwmp34004345jz2q3l45</td>-->
			<!--</tr>-->
			<!--<tr>-->
				<!--<td>当前系统用户名 </td>-->
				<!--<td>NETWORK SERVICE</td>-->
			<!--</tr>-->
		<!--</tbody>-->
	<!--</table>-->
</div>
<footer class="footer mt-20">
	<div class="container">
		<p>感谢jQuery、layer、laypage、Validform、UEditor、My97DatePicker、iconfont、Datatables、WebUploaded、icheck、highcharts、bootstrap-Switch<br>
			Copyright &copy;2015-2017 H-ui.admin v3.1 All Rights Reserved.<br>
			本后台系统由<a href="http://www.h-ui.net/" target="_blank" title="H-ui前端框架">H-ui前端框架</a>提供前端技术支持</p>
	</div>
</footer>
<script type="text/javascript" src="/admin_file/lib/jquery/1.9.1/jquery.min.js"></script>
<script type="text/javascript" src="/admin_file/static/h-ui/js/H-ui.min.js"></script>
<!--此乃百度统计代码，请自行删除-->
<script>
var _hmt = _hmt || [];
(function() {
  var hm = document.createElement("script");
  hm.src = "https://hm.baidu.com/hm.js?080836300300be57b7f34f4b3e97d911";
  var s = document.getElementsByTagName("script")[0]; 
  s.parentNode.insertBefore(hm, s);
})();
</script>
<!--/此乃百度统计代码，请自行删除-->
</body>
</html>