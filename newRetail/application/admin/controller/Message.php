<?php
namespace app\admin\controller;
use app\admin\model\MailMessageModel;
use app\admin\model\MobileMessageModel;
use app\admin\model\RankModel;
use think\Request;
use think\Session;

class Message extends Common
{
    use \app\admin\traits\SendSMS;

    /**
     * 发送短信列表
     * @param Request $request
     * @param MobileMessageModel $mobileMessageModel
     * @return \think\response\View
     */
    public function mobileMessageList(Request $request, MobileMessageModel $mobileMessageModel) {
        $mobileMessageModel->data($request->param());
        if (!empty($mobileMessageModel->show_count)){
            $show_count = $mobileMessageModel->show_count;
        }else{
            $show_count = 10;
        }

        $where = " 1=1 ";
        if(!empty($mobileMessageModel->keywords)){
            $keywords = $mobileMessageModel->keywords;
            $where .= " and (admin_user_nickname like '%" . $keywords . "%' or content like '%" . $keywords . "%')";
        }

        //排序条件
        if(!empty($mobileMessageModel->orderBy)){
            $orderBy = $mobileMessageModel->orderBy;
        }else{
            $orderBy = 'id';
        }
        if(!empty($mobileMessageModel->orderByUpOrDown)){
            $orderByUpOrDown = $mobileMessageModel->orderByUpOrDown;
        }else{
            $orderByUpOrDown = 'Desc';
        }
        $mobile_message_list = $mobileMessageModel->where($where)->order($orderBy.' '.$orderByUpOrDown)->paginate($show_count,false, [
            'query' => Request::instance()->param(),//不丢失已存在的url参数
        ]);
        // 获取分页显示
        $page = $mobile_message_list->render();

        //权限按钮
        $action_code_list = $this->getChileAction('mobilemessagelist');

        // 模板变量赋值
        $this->assign('mobile_message_list', $mobile_message_list);
        $this->assign('where', $mobileMessageModel->toArray());
        $this->assign('page', $page);
        $this->assign('orderBy', $orderBy);
        $this->assign('show_count', $show_count);
        $this->assign('orderByUpOrDown', $orderByUpOrDown);
        $this->assign('action_code_list', $action_code_list);
        // 模板输出
        return view("Message/mobile_message_list");
    }

    /**
     * 发送短信
     * @param MobileMessageModel $mobileMessageModel
     * @param Request $request
     * @return \think\response\View
     */
    public function mobileMessageAdd(MobileMessageModel $mobileMessageModel,RankModel $rankModel, Request $request)
    {
        $mobileMessageModel->data($request->param());
        //如果是提交
        if(!empty($mobileMessageModel->is_ajax)){
            //中文逗号替换为英文
            $mobileArray = $mobileMessageModel->to_user_mobile;
            $mobile_count = 0;
            $error_count = 0;
            $success_count = 0;
            $mobileStr = '';
            $errorStr = '';

            foreach ($mobileArray as $item){
                $mobile_count ++ ;
                if($this->is_mobile($item)){
                    $success_count ++ ;
                    $mobileStr = empty($mobileStr)?$item:$mobileStr.",".$item;
                }else{
                    $error_count ++ ;
                    $errorStr = empty($errorStr)?$item:$errorStr.",".$item;
                }
            }
            if(!empty($mobileStr)){
                //发送短信
                $message_result = $this->sendSMSQuery($mobileStr,$mobileMessageModel->content);
                $mobileMessageModel->msgid = $message_result[1];
            }
            $mobileMessageModel->mobile = $mobileStr.$errorStr;
            $mobileMessageModel->seccess_mobile = $mobileStr;
            $mobileMessageModel->error_mobile = $errorStr;
            $mobileMessageModel->mobile_count = $mobile_count;
            $mobileMessageModel->success_count = $success_count;
            $mobileMessageModel->error_count = $error_count;
            $mobileMessageModel->admin_user_id = session::get("admin_user_id");
            $mobileMessageModel->admin_user_nickname = session::get("admin_nickname");

            $result = $mobileMessageModel->allowField(true)->save($mobileMessageModel);
            if($result){
                $msgid = $mobileMessageModel->getLastInsID();
                $this->setAdminUserLog("新增","添加短信：" . $msgid ,$mobileMessageModel->table,$msgid);
                $this->success("添加成功");
            }else{
                $this->error("添加失败");
            }
        }else{
            //获取会员等级列表
            $rank_list = $rankModel->select();
            $this->assign("rank_list",$rank_list);
            // 模板输出
            return view("Message/mobile_message_info");
        }
    }

    /**
     * 删除短信记录
     * @param MobileMessageModel $mobileMessageModel
     * @param Request $request
     */
    public function mobileMessageDel(MobileMessageModel $mobileMessageModel, Request $request){
        $mobileMessageModel->data($request->param());
        if(!empty($mobileMessageModel->id)){
            $mobile_message_id_list = explode(",",$mobileMessageModel->id);
        }

        $result = $mobileMessageModel->destroy($mobile_message_id_list);
        if($result){
            $this->setAdminUserLog("删除","删除短信消息：" . $mobileMessageModel->id ,$mobileMessageModel->table,$mobileMessageModel->id);
            $this->success("删除成功");
        }else{
            $this->error("删除失败");
        }

    }

    /**
     * 批量删除短信记录
     * @param MobileMessageModel $mobileMessageModel
     * @param Request $request
     */
    public function mobileMessageQueryDel(MobileMessageModel $mobileMessageModel, Request $request){
        $mobileMessageModel->data($request->param());
        if(!empty($mobileMessageModel->id)){
            $mobile_message_id_list = explode(",",$mobileMessageModel->id);
        }

        $result = $mobileMessageModel->destroy($mobile_message_id_list);
        if($result){
            $this->setAdminUserLog("删除","批量删除短信消息：" . $mobileMessageModel->id ,$mobileMessageModel->table,$mobileMessageModel->id);
            $this->success("删除成功");
        }else{
            $this->error("删除失败");
        }

    }

    /**
     * 站内信列表
     * @param Request $request
     * @param MailMessageModel $mailMessageModel
     * @return \think\response\View
     */
    public function mailMessageList(Request $request, MailMessageModel $mailMessageModel) {
        $mailMessageModel->data($request->param());
        if (!empty($mailMessageModel->show_count)){
            $show_count = $mailMessageModel->show_count;
        }else{
            $show_count = 10;
        }

        $where = " 1=1 ";
        if(!empty($mailMessageModel->keywords)){
            $keywords = $mailMessageModel->keywords;
            $where .= " and (fu.nick_name like '%" . $keywords . "%' 
                        or tu.nick_name like '%" . $keywords . "%' 
                        or fa.nickname like '%" . $keywords . "%' 
                        or ta.nickname like '%" . $keywords . "%'
                        or a.content like '%" . $keywords . "%')";
        }
        if(!empty($mailMessageModel->datemin)){
            $where .= " and a.create_time >= '" . $mailMessageModel->datemin . "'";
        }
        if(!empty($mailMessageModel->datemax)){
            $where .= " and a.create_time <= '" . $mailMessageModel->datemax . "'";
        }

        //排序条件
        if(!empty($mailMessageModel->orderBy)){
            $orderBy = $mailMessageModel->orderBy;
        }else{
            $orderBy = 'id';
        }
        if(!empty($mailMessageModel->orderByUpOrDown)){
            $orderByUpOrDown = $mailMessageModel->orderByUpOrDown;
        }else{
            $orderByUpOrDown = 'Desc';
        }
        $mail_message_list = $mailMessageModel->where($where)
            ->alias('a')
            ->join('new_users fu','fu.user_id=a.from_user_id','left')
            ->join('new_users tu','tu.user_id=a.to_user_id','left')
            ->join(['admin_user fa'],'fa.admin_id=a.from_user_id','left')
            ->join(['admin_user ta'],'ta.admin_id=a.to_user_id','left')
            ->field("fu.nick_name as fu_nickname,tu.nick_name as tu_nickname,fa.nickname as fa_nickname,ta.nickname as ta_nickname,a.*")
            ->order($orderBy.' '.$orderByUpOrDown)->paginate($show_count,false, [
            'query' => Request::instance()->param(),//不丢失已存在的url参数
        ]);
        // 获取分页显示
        $page = $mail_message_list->render();

        //权限按钮
        $action_code_list = $this->getChileAction('mailmessagelist');

        // 模板变量赋值
        $this->assign('mail_message_list', $mail_message_list);
        $this->assign('where', $mailMessageModel->toArray());
        $this->assign('page', $page);
        $this->assign('orderBy', $orderBy);
        $this->assign('show_count', $show_count);
        $this->assign('orderByUpOrDown', $orderByUpOrDown);
        $this->assign('action_code_list', $action_code_list);
        // 模板输出
        return view("Message/mail_list");
    }

    /**
     * 发送站内信
     * @param MailMessageModel $mailMessageModel
     * @param Request $request
     * @return \think\response\View
     */
    public function mailAdd(MailMessageModel $mailMessageModel, Request $request)
    {
        $mailMessageModel->data($request->param());
        //如果是提交
        if(!empty($mailMessageModel->is_ajax)){
            $to_user_type = $mailMessageModel->to_user_type;
            $to_user_List = $mailMessageModel->to_user_id;
            if(!empty($to_user_List)){
                foreach ($to_user_List as $item){
                    $data['to_user_type'] = $mailMessageModel->to_user_type;
                    $data['to_user_id'] = $item;
                    $data['from_user_type'] = 0;
                    $data['from_user_id'] = session::get("admin_user_id");
                    $data['status'] = 0;
                    $mailMessageModel->save($data);
                    $msgid = $mailMessageModel->getLastInsID();
                    $this->setAdminUserLog("新增","添加站内信：" . $msgid ,$mailMessageModel->table,$msgid);
                }
            }

            $this->success("添加成功");
        }else{
            // 模板输出
            return view("Message/mail_info");
        }
    }

    /**
     * 删除站内信
     * @param MailMessageModel $mailMessageModel
     * @param Request $request
     */
    public function mailDel(MailMessageModel $mailMessageModel, Request $request){
        $mailMessageModel->data($request->param());
        if(!empty($mailMessageModel->id)){
            $mail_message_id_list = explode(",",$mailMessageModel->id);
        }

        $result = $mailMessageModel->destroy($mail_message_id_list);
        if($result){
            $this->setAdminUserLog("删除","删除短信消息：" . $mailMessageModel->id ,$mailMessageModel->table,$mailMessageModel->id);
            $this->success("删除成功");
        }else{
            $this->error("删除失败");
        }

    }

    /**
     * 批量删除站内信
     * @param MailMessageModel $mailMessageModel
     * @param Request $request
     */
    public function mailQueryDel(MailMessageModel $mailMessageModel, Request $request){
        $mailMessageModel->data($request->param());
        if(!empty($mailMessageModel->id)){
            $mail_message_id_list = explode(",",$mailMessageModel->id);
        }

        $result = $mailMessageModel->destroy($mail_message_id_list);
        if($result){
            $this->setAdminUserLog("删除","批量删除短信消息：" . $mailMessageModel->id ,$mailMessageModel->table,$mailMessageModel->id);
            $this->success("删除成功");
        }else{
            $this->error("删除失败");
        }

    }







}