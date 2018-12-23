<?php

/*
 * This file is part of the halobear/tencent-im.
 *
 * (c) guansq <94600115@qq.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace TencentIm\User;

use TencentIm\Kernel\BaseClient;

/**
 * Class Client.
 *
 * @author guansq <94600115@qq.com>
 */
class Client extends BaseClient
{
    /**
     * 获取用户资料
     * @param string $account_id 获取哪个用户的资料
     * @return array 通过解析REST接口json返回包得到的关联数组，包含成功与否、拉取到的用户信息(如果成功)，及错误提示等字段
     */

    public function profile_portrait_get($account_id)
    {
        #构造高级接口所需参数
        $account_list = [];
        array_push($account_list, $account_id);
        $tag_list = [
            "Tag_Profile_IM_Nick",
            "Tag_Profile_IM_AllowType",
        ];
        $ret      = $this->profile_portrait_get2($account_list, $tag_list);

        return $ret;
    }

    /**
     * 获取用户资料(高级接口)
     * @param array $account_list
     *                            需要获取资料的帐号id集合, php构造示例:
     *
     *     $account_list = array();
     *     array_push($account_list, $account_id);  //$account_id为用户id，需要用户传递
     *
     * @param array $tag_list
     *                            需要拉取的字段,目前可拉取的字段:
     *                            1.昵称:"Tag_Profile_IM_Nick
     *                            2.加好友设置"Tag_Profile_IM_AllowType", php构造示例:
     *
     *     $tag_list = array(
     *         "Tag_Profile_IM_Nick",                  //昵称
     *     );
     *
     * @return array 通过解析REST接口json返回包得到的关联数组，包含成功与否、拉取到的用户信息(如果成功)，及错误提示等字段
     */
    public function profile_portrait_get2($account_list, $tag_list)
    {

        #构造高级接口所需参数
        $msg = [
            'From_Account'         => $this->app['config']->get('identifier'),
            'To_Account'           => $account_list,
            'TagList'              => $tag_list,
            'LastStandardSequence' => 0,
        ];
        $ret = $this->httpGetJson('v4/profile/portrait_get', $msg);

        return $ret;
    }

    /**
     * 设置用户名称
     * @param string $account_id 需要设置的用户
     * @param string $new_name   要设置为的用户名
     * @return array 通过解析REST接口json返回包得到的关联数组，包含成功与否、错误提示等字段
     */
    public function profile_portrait_set($account_id, $new_name)
    {
        #构造高级接口所需参数
        $profile_list = [];
        $profile_nick = [
            "Tag"   => "Tag_Profile_IM_Nick",
            "Value" => $new_name,
        ];
        //加好友验证方式
        $profile_allow = [
            "Tag"   => "Tag_Profile_IM_AllowType",
            "Value" => "NeedPermission",
        ];
        array_push($profile_list, $profile_nick);
        //array_push($profile_list, $profile_allow);
        $ret = $this->profile_portrait_set2($account_id, $profile_list);

        return $ret;
    }

    /**
     * 设置用户资料(高级接口)
     * @param string $account_id   需要设置的用户
     * @param array  $profile_list 设置选项集合，用户账号设置内容选项, 比如昵称, php构造示例:
     *
     *   //创建array $profile_list
     *     $profile_list = array();
     *     //创建昵称选项
     *     $profile_nick = array(
     *         "Tag" => "Tag_Profile_IM_Nick",             //用户昵称
     *         "Value" => "new_name"                        //"new_name"要设置成的用户名
     *         );
     *    //加好友验证方式
     *    $profile_allow = array(
     *        "Tag" => "Tag_Profile_IM_AllowType",
     *        "Value" => "AllowType_Type_NeedConfirm"
     *    );
     *    array_push($profile_list, $profile_nick);
     *    array_push($profile_list, $profile_allow);
     *
     * @return array 通过解析REST接口json返回包得到的关联数组，包含成功与否、错误提示等字段
     */
    public function profile_portrait_set2($account_id, $profile_list)
    {
        #构造新消息
        $msg = [
            'From_Account' => $account_id,
            'ProfileItem'  => $profile_list,
        ];

        $ret = $this->httpPostJson('v4/profile/portrait_set', $msg);

        return $ret;
    }
}
