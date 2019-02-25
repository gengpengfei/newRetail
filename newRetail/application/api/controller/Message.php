<?php
namespace app\api\controller;

use app\api\model\MailMessageModel;
use app\api\model\SystemConfigModel;
use app\api\service\ClientService;
use think\Request;

class Message extends Common {
    use \app\api\traits\BuildParam;
    use \app\api\traits\GetConfig;

    /*
     * explain:我要合作
     * params :null
     * authors:Mr.Geng
     * addTime:2018/4/3 13:40
     */
    public function cooperation(SystemConfigModel $systemConfigModel){
        $myCooperate = $systemConfigModel->where(['code'=>'myCooperate'])->find();
        $contacts = $systemConfigModel->where(['parent_id'=>$myCooperate->id])->select();
        $cooperate = array();
        foreach ($contacts as $value) {
            $cooperate[$value['code']] = array('desc' =>$value['desc'],'value'=>$value['value']);
        }
        $this->assign('cooperate',$cooperate);
        return view("Message/cooperation");
    }

    /**
     * 改变状态
     *
     * @param Request $request
     * @param MailMessageModel $mailMessageModel
     * @return string
     * @Author: guanyl
     * @Date: 2018/8/13
     */
    function messageStatus(Request $request, MailMessageModel $mailMessageModel)
    {
        $message = $request->param();
        $mail_message_list = $mailMessageModel->where(['id'=>$message['message_id']])->find();
        if (!empty($mail_message_list)) {
            $mailMessageModel->update(['status'=>1],['id'=>$message['message_id']]);
        }
        $this->jkReturn(1,'改变状态');
    }

    /**
     * 站内信列表
     *
     * @param Request $request
     * @param MailMessageModel $mailMessageModel
     * @Author: guanyl
     * @Date: 2018/8/10
     */
    public function mailMessageList(Request $request, MailMessageModel $mailMessageModel){
        $param = $request->param();
        $mail_message_list = $mailMessageModel->where(['to_user_id'=>$param['user_id'],'to_user_type'=>1])->select();
        $this->jkReturn(1,'站内信',$mail_message_list);
    }
    //极光推送
    public function jPush(){
        header("Content-type: text/html; charset=utf-8");
        $clientService = new ClientService();
        //$storeUser = new StoreUserModel();
        //$users = $storeUser->where(['store_id'=>84,'is_boss'<>0,])->select();
        $content =  '祝，天天开心哦！';
        $receiver['alias'] = array('store84');//接收者
        $messages = $clientService->push($content,$receiver,'123',json_encode(['123'=>123]));


        print_r($messages);die;
    }
    public function jPushReport(){
        header("Content-type: text/html; charset=utf-8");
        $clientService = new ClientService();
        $result = $clientService->clientReport();
        print_r($result);die;
    }

}
