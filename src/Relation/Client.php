<?php

/*
 * This file is part of the mingyoung/dingtalk.
 *
 * (c) mingyoung <mingyoungcheung@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace TencentIm\Relation;

use TencentIm\Kernel\BaseClient;

/**
 * Class Client.
 *
 * @author guansq <94600115@qq.com>
 */
class Client extends BaseClient
{

    /**
     * 建立双方好友关系
     * @param string $account_id 发起者id
     * @param string $receiver   添加的用户，完成之后两者互为好友
     * @return array 通过解析REST接口json返回包得到的关联数组，包含成功与否、错误提示等字段
     */
    public function sns_friend_import($account_id, $receiver)
    {
        #构造新消息
        $msg          = [
            'From_Account'  => $account_id,
            'AddFriendItem' => [],
        ];
        $receiver_arr = [
            'To_Account' => $receiver,
            'Remark'     => "",
            'AddSource'  => "AddSource_Type_Unknow",
            'AddWording' => "",
        ];
        array_push($msg['AddFriendItem'], $receiver_arr);
        $ret = $this->httpPostJson('v4/sns/friend_import', $msg);

        return $ret;
    }

    /**
     * 解除双方好友关系
     * @param string $account_id 用户id,即需要删除好友的用户
     * @param string $frd_id     需要删除的好友
     * @return array 通过解析REST接口json返回包得到的关联数组，包含成功与否、错误提示等字段
     */
    public function sns_friend_delete($account_id, $frd_id)
    {
        #构造新消息
        $frd_list = [];
        //要添加的好友用户
        array_push($frd_list, $frd_id);
        $msg = [
            'From_Account' => $account_id,
            'To_Account'   => $frd_list,
            'DeleteType'   => "Delete_Type_Both",
        ];
        $ret = $this->httpPostJson('v4/sns/friend_delete', $msg);

        return $ret;
    }

    /**
     * 解除所有好友关系
     * @param string $account_id 用户id,即需要解除所有好友关系的用户
     * @return array 通过解析REST接口json返回包得到的关联数组，包含成功与否、错误提示等字段
     */
    public function sns_friend_delete_all($account_id)
    {
        #构造新消息
        $msg = [
            'From_Account' => $account_id,
        ];
        $ret = $this->httpPostJson('v4/sns/friend_delete_all', $msg);

        return $ret;
    }

    /**
     * 校验好友关系(默认双向严重)
     * @param string $account_id 需要校验好友的用户id
     * @param string $to_account 校验是否为好友的id
     * @return array 通过解析REST接口json返回包得到的关联数组，包含成功与否、错误提示、验证结果等字段
     */
    public function sns_friend_check($account_id, $to_account)
    {
        #构造高级接口所需参数
        $to_account_list = [];
        //要添加的好友用户
        array_push($to_account_list, $to_account);
        $msg = [
            'From_Account' => $account_id,
            'To_Account'   => $to_account_list,
        ];

        $ret = $this->sns_friend_check2($account_id, $to_account_list, "CheckResult_Type_Both");

        return $ret;
    }

    /**
     * 校验好友关系
     * @param string $account_id      需要校验好友的用户id
     * @param array  $to_account_list 校验是否为好友的id集合
     * @param string $check_type      校验类型，目前支持：单向校验"CheckResult_Type_Singal"，双向校验"CheckResult_Type_Both"
     * @return array 通过解析REST接口json返回包得到的关联数组，包含成功与否、错误提示、验证结果等字段
     */
    public function sns_friend_check2($account_id, $to_account_list, $check_type)
    {
        #构造新消息
        $msg = [
            'From_Account' => $account_id,
            'To_Account'   => $to_account_list,
            'CheckType'    => $check_type,
        ];
        $ret = $this->httpPostJson('v4/sns/friend_check', $msg);

        return $ret;
    }

    /**
     * 拉取好友
     * @param string $account_id 需要获取好友的用户id
     * @return array 通过解析REST接口json返回包得到的关联数组，包含拉取到的好友信息、成功与否及错误提示等字段
     */
    function sns_friend_get_all($account_id)
    {
        #构造高级接口所需参数
        $tag_list = [
            "Tag_Profile_IM_Nick",
            "Tag_SNS_IM_Remark",
        ];

        $ret = $this->sns_friend_get_all2($account_id, $tag_list);

        return $ret;
    }

    /**
     * 拉取好友(高级接口)
     * @param string $account_id 需要获取好友的用户id
     * @param array  $tag_list   需要拉取的字段，该拉取协议是一条整合数据的协议，可以指定拉取自己好友的昵称
     *                           加好友设置以及对用户的备注等字段，如果需要拉取昵称字段，则这里就需要在Json数组中填入Tag_Profile_IM_Nick.
     *                           php构造示例:
     *
     *     $tag_list = array(
     *         "Tag_Profile_IM_Nick",      //昵称选项
     *         "Tag_SNS_IM_Remark"         //备注选项
     *     );
     *
     * @return array 通过解析REST接口json返回包得到的关联数组，包含拉取到的好友信息、成功与否及错误提示等字段
     */
    function sns_friend_get_all2($account_id, $tag_list)
    {
        #构造新消息
        $msg = [
            'From_Account'         => $account_id,
            'TimeStamp'            => 0,
            'TagList'              => $tag_list,
            'LastStandardSequence' => 1,
        ];
        $ret = $this->httpPostJson('v4/sns/friend_get_all', $msg);

        return $ret;
    }

    /**
     * 拉取指定好友的信息
     * @param string $account_id 需要拉取好友的帐号
     * @param string $frd_id     需要被拉取的好友
     * @return array 通过解析REST接口json返回包得到的关联数组，包含拉取到的好友信息、成功与否及错误提示等字段
     */
    function sns_friend_get_list($account_id, $frd_id)
    {
        #构造高级接口所需参数
        $frd_list = [];
        array_push($frd_list, $frd_id);
        $tag_list = [
            "Tag_Profile_IM_Nick",
            "Tag_SNS_IM_Remark",
        ];

        $ret = $this->sns_friend_get_list2($account_id, $frd_list, $tag_list);

        return $ret;
    }

    /**
     * 拉取特定好友(高级接口)
     * @param string $account_id 需要拉取好友的帐号
     * @param array  $frd_list   拉取好友对象, php构造示例:
     *
     *     $frd_list = array();
     *     array_push($frd_list, "leckie");  //"leckie" 为需要被拉取的好友id
     *
     * @param array  $tag_list   需要拉取属性的选项字段, 该拉取协议是一条整合数据的协议，可以指定拉取自己好友的昵称
     *                           、加好友设置以及对用户的备注等字段，如果需要拉取昵称字段，则这里就需要在Json数组中填入Tag_Profile_IM_Nick
     *
     *    $tag_list = array(
     *         "Tag_Profile_IM_Nick",   //昵称选项
     *         "Tag_SNS_IM_Remark"      //备注选项
     *     );
     *
     * @return array 通过解析REST接口json返回包得到的关联数组，包含拉取到的好友信息、成功与否及错误提示等字段
     */
    function sns_friend_get_list2($account_id, $frd_list, $tag_list)
    {
        #构造新消息
        $msg = [
            'From_Account' => $account_id,
            'To_Account'   => $frd_list,
            'TagList'      => $tag_list,
        ];

        $ret = $this->httpPostJson('v4/sns/friend_get_list', $msg);

        return $ret;
    }
}
