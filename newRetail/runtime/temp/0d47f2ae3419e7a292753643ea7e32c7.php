<?php if (!defined('THINK_PATH')) exit(); /*a:2:{s:81:"/Users/jk/Desktop/obj/newRetail/public/../application/admin/view/Login/index.html";i:1535080857;s:75:"/Users/jk/Desktop/obj/newRetail/application/admin/view/Public/head_top.html";i:1532945889;}*/ ?>
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
<style>
  .row {
    width:100%;
  }

</style>

<link href="/admin_file/static/h-ui.admin/css/H-ui.login.css" rel="stylesheet" type="text/css" />

<body>
<input type="hidden" id="TenantId" name="TenantId" value="" />
<div class="header"></div>
<div class="loginWraper">
  <div id="loginform" class="loginBox">
    <form class="form form-horizontal" id="form-admin-login">
      <div class="row cl">
        <label class="form-label col-xs-3"><i class="Hui-iconfont">&#xe60d;</i></label>
        <div class="formControls col-xs-8">
          <input id="user_name" name="user_name" type="text" placeholder="账户" class="input-text size-L">
        </div>
      </div>
      <div class="row cl">
        <label class="form-label col-xs-3"><i class="Hui-iconfont">&#xe60e;</i></label>
        <div class="formControls col-xs-8">
          <input id="password" name="password" type="password" placeholder="密码" class="input-text size-L">
        </div>
      </div>
      <div class="row cl">
        <div class="formControls col-xs-8 col-xs-offset-3">
          <input class="input-text size-L" style="width:205px;" name="captcha" type="text" placeholder="验证码" onblur="if(this.value==''){this.value='验证码:'}" onclick="if(this.value=='验证码:'){this.value='';}" style="width:150px;">
          <img src="" id="newCode">
        </div>
      </div>
      <!--<div class="row cl">
        <div class="formControls col-xs-8 col-xs-offset-3">
          <label for="online">
            <input type="checkbox" name="online" id="online" value="">
            使我保持登录状态</label>
        </div>
      </div>-->
      <div class="row cl">
        <div class="formControls col-xs-8 col-xs-offset-3">
          <input name="" type="submit" class="btn btn-success radius size-L" style="width:175px" value="&nbsp;登&nbsp;&nbsp;&nbsp;&nbsp;录&nbsp;">
          <input name="" type="reset" class="btn btn-default radius size-L" style="width:175px" value="&nbsp;取&nbsp;&nbsp;&nbsp;&nbsp;消&nbsp;">
        </div>
      </div>
    </form>
  </div>
</div>
<div class="footer">Copyright 你的公司名称 by H-ui.admin v3.1</div>
<script type="text/javascript" src="/admin_file/lib/jquery/1.9.1/jquery.min.js"></script>
<script type="text/javascript" src="/admin_file/static/h-ui/js/H-ui.min.js"></script>
<script type="text/javascript" src="/admin_file/lib/jquery.validation/1.14.0/jquery.validate.js"></script>
<script type="text/javascript" src="/admin_file/lib/jquery.validation/1.14.0/validate-methods.js"></script>
<script type="text/javascript" src="/admin_file/lib/jquery.validation/1.14.0/messages_zh.js"></script>
<script type="text/javascript" src="/admin_file/lib/layer/2.4/layer.js"></script>
<!--此乃百度统计代码，请自行删除-->
<script>
  window.onload=function () {
      newCode();
  };
  document.getElementById("newCode").onclick = function () { newCode(); };
  function newCode() {
      var timestamp = Date.parse(new Date());
      document.getElementById("newCode").src = "/admin/Login/getCode?mm="+timestamp;
  }

  $(function () {
      $("#form-admin-login").validate({
          rules:{
              user_name:{
                  required:true
              },
              password:{
                  required:true
              }
          },
          onkeyup:false,
          focusCleanup:true,
          success:"valid",
          submitHandler:function(form){
              $(form).ajaxSubmit({
                  type: 'post',
                  url: "/admin/Login/login" ,
                  success: function(data){
                      if(data.code == 0){
                          var timestamp = Date.parse(new Date());
                          document.getElementById("newCode").src = "/admin/Login/getCode?mm="+timestamp;
                          layer.msg(data.msg,{icon:2,time:2000});
                      }else if(data.code == 1){
                          layer.msg('登录成功!',{icon:1,time:2000});
                          window.location.href="/admin/Index/index";
                      }else{
                          var timestamp = Date.parse(new Date());
                          document.getElementById("newCode").src = "/admin/Login/getCode?mm="+timestamp;
                          layer.msg(data.msg,{icon:4,time:2000});
                      }
                  },
                  error: function(XmlHttpRequest, textStatus, errorThrown){
                      var timestamp = Date.parse(new Date());
                      document.getElementById("newCode").src = "/admin/Login/getCode?mm="+timestamp;
                      layer.msg('error!',{icon:1,time:2000});
                  }
              });
          }
      });
  })

</script>
<!--/此乃百度统计代码，请自行删除
</body>
</html>