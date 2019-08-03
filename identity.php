<?php
/**
 * Created by PhpStorm.
 * User: gzq
 * Date: 2019/8/3
 * Time: 10:01
 * 身份认证
 */
namespace Promoting\Cooperation;
class Identity{
    //身份认证信息驳回
    public static function reject($_this){
        try{
            if(!IsAjax){
                throw new \Exception('请求数据出错');
            }
            $approve_id = $_this->method_post_value('approve_id',1);
            if(empty($approve_id)){
                throw new \Exception('获取数据出错');
            }
            $_this->getmodel("Promuser_approve_model");
            $promuser_addon_arr=$_this->Promuser_approve_model->get_have_data(array("approve_id='{$approve_id}'"));
            if(empty($promuser_addon_arr)){
                throw new \Exception('当前身份认证信息不存在');
            }
            if(1 != $promuser_addon_arr['examine_state']){
                throw new \Exception('不在审核通过的数据无法处理');
            }
            $update_arr = array(
                'examine_state' => -1,
                'last_examine_time' => time(),
                'examine_pomuser_id'=>$_this->get_admin_session("promuser_id"),
            );
            $insert_log_data=array_merge($promuser_addon_arr,$update_arr,
                array(
                    'add_time'=>time(),
                    'last_submit_time' => $promuser_addon_arr['last_edit_time'],
                    'proaddon_id' => $promuser_addon_arr['approve_id'],
                    'examine_state' => 2,//驳回
                )
            );
            unset($insert_log_data['last_edit_time'],$insert_log_data['approve_id'],$insert_log_data['alipay']);
            list($errorno,$errormess)=$_this->Promuser_approve_model->updateBatch($update_arr,$insert_log_data,$approve_id);
            if(0 != $errorno){
                throw new \Exception('驳回失败');
            }
            return array(0,'驳回成功');
        }catch (\Exception $exception){
            return array(-1,$exception->getMessage());
        }
    }
    //首页html
    public static function indexHtml($data){
        $out_html = '';
        foreach ($data as $key => $value) {
            $out_html .= '<tr>';
            $out_html .= "<td height=\"30\" align=\"center\" valign=\"middle\">{$value['promuser_name']}</td>";
            $out_html .= "<td height=\"30\" align=\"center\" valign=\"middle\" style='color: {$value['examine_state_color']}'>";
            if ($value['examine_state'] == 1) {
                $out_html .= "<a href='" . UrlRecombination('cooperation/identity_examine_detail', array('approve_id' => $value['approve_id'])) . "' style='color: {$value['examine_state_color']}'>{$value['examine_state_html']}</a>";
            } else {
                $out_html .= $value['examine_state_html'];
            }
            $out_html .= "</td>";
            $out_html .= "<td height=\"30\" align=\"center\" valign=\"middle\">{$value['last_submit_time']}</td>";
            $out_html .= "<td height=\"30\" align=\"center\" valign=\"middle\">";
            switch ($value['examine_state']) {
                case -2:
                    $out_html .= "<a href='" . UrlRecombination('cooperation/identity_examine_detail', array('approve_id' => $value['approve_id'])) . "' class='kuai_x_qing02a'>审核</a>";
                    break;
                case 1:
                    $out_html .= "<a href='javascript:;' onclick='identity_reject(this)' doid='{$value['approve_id']}' class='kuai_x_qing02a' dourl='" . UrlRecombination('cooperation/identity_reject') . "'>驳回</a>";
                    break;
                default:
                    $out_html .= '---';
                    break;
            }
            $out_html .= "</td></tr>";
        }
        $out_html .= <<<SCRIPT
<script type="text/javascript">
function identity_reject(_this) {
  var approve_id = $(_this).attr('doid'),dourl=$(_this).attr('dourl');
  $.ajax({
        'url':dourl,
        'type':"POST",
        'dataType':"JSON",
        'data':"approve_id="+approve_id,
        'success':function (returnData){
            layer.close(loadIndex);
            layer.msg(returnData.data1);
            if(returnData.errorno!=0){
                return false;
            }
            window.location.href = window.location.href;
           
        },
        'beforeSend':function () {
            loadIndex = layer.load(2,{shade:0.3});
            return true;
        }
    });

}
</script>
SCRIPT;
        return $out_html;

    }
}