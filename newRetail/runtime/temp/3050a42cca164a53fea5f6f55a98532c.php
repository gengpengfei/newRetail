<?php if (!defined('THINK_PATH')) exit(); /*a:1:{s:88:"/Users/jk/Desktop/obj/newRetail/public/../application/api/view/User/invitation_info.html";i:1535617619;}*/ ?>
﻿<!DOCTYPE html>
<html>

<head>
	<meta charset="utf-8">
	<meta name="renderer" content="webkit|ie-comp|ie-stand">
	<meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">
	<meta name="viewport" content="width=device-width,initial-scale=1,minimum-scale=1.0,maximum-scale=1.0,user-scalable=no" />
</head>
<style>
	a {
		text-decoration: none;
	}

	.content {
		width: 100%;
		height: auto;
	}
</style>
<script type="text/javascript">
	setTimeout(function () {
		window.postMessage(JSON.stringify({ 'type': 'setHeight', 'height': document.body.scrollHeight }))
	}, 300);
</script>

<body style="margin: 0;padding:20px;">
	<div class="content">
		<div style="height:60px;width:100%; ">
			<div style="float:left;width:20%;">
				<img src="/admin_file/api/log.png" alt="" style="width:60px;height:60px;border-radius:10px;margin:0 auto;">
			</div>
			<div style="float:right;width:75%;">
				<div style="line-height: 30px;font-size: 18px;">约惠多APP</div>
				<div style="line-height: 30px;font-size: 14px;color:#e25e31;">邀请好友填写邀请码消费得返利</div>
			</div>
		</div>
		<div style="float:none;"></div>
		<div style="background-color: #fdf4e5;margin-top:20px;text-align: center;height:200px;">
			<div style="height: 60px;line-height: 80px;">我的邀请码</div>
			<div style="height: 60px;line-height: 60px;font-size:26px;color:#d53e35;font-family:'微软雅黑' "><?php echo $code; ?></div>
			<div style="height: 40px;line-height: 40px;color:#817f7e">下载约惠多app与我一起约惠吧</div>
		</div>
		<div>
			<img src="/admin_file/api/logbg.png" alt="" style="width:100%;height:auto;">
		</div>
		<div style="margin-top:15px;height:50px;" onclick="alert('约惠多紧急筹备中')">
			<a href="javascript:void(0)" style="display:block;line-height:50px;background-color:#d53e35;margin:0 auto;color:white;font-size: 20px;text-align: center;border-radius: 5px;">下载约惠多app</a>
		</div>
		<div style="margin-top:10px;height:50px;" onclick="alert('约惠多紧急筹备中')">
			<a href="javascript:void(0)" style="display:block;line-height:50px;background-color:#fae3e4;margin:0 auto;color:#c44a3d;font-size: 20px;text-align: center;border-radius: 5px;">打开约惠多app</a>
		</div>
	</div>
</body>

</html>