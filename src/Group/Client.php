<?php

/*
 * This file is part of the halobear/tencent-im.
 *
 * (c) guansq <94600115@qq.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace TencentIm\Group;

use TencentIm\Kernel\BaseClient;

/**
 * Class Client.
 *
 * @author guansq <94600115@qq.com>
 */
class Client extends BaseClient
{
    /**
     * 获取app中所有群组, 如果APP中的总群数量超过10000个，最多只会返回10000个(如果需要获取完整必须使用高级接口)
     * @return array 通过解析REST接口json返回包得到的关联数组，包含拉取到的群组信息、成功与否及错误提示等字段
     */
    function group_get_appid_group_list()
    {
        #构造高级接口所需参数
        $ret = $this->group_get_appid_group_list2(50, null, null);

        return $ret;
    }

    /**
     * 获取app中所有群组(高级接口)
     * @param int    $limit      最多获取多少个群，不得超过10000, 如果不填，获取能获取的最大数量的群.
     * @param int    $offset     控制从整个群组列表中的第多少个开始读取(从0开始). 对于分页请求（页码数字从1开始），每
     *                           一页的Offset值应当为：（页码数-1）×每页展示的群组数量, 如果不填从0开始.
     * @param string $group_type 如果仅需要返回特定群组形态的群组，可以通过GroupType进行过滤，但此时返回的TotalCount
     *                           的含义就变成了APP中该群组形态的群组总数. 例如：假设APP旗下总共50000个群组，其中有20000个为公开群组，如
     *                           果将请求包体中的GroupType设置为Public，那么不论limit和offset怎样设置，应答包体中的TotalCount都为20000，
     *                           且GroupIdList中的群组全部为公开群组.
     * @return array 通过解析REST接口json返回包得到的关联数组，包含拉取到的群组信息、成功与否及错误提示等字段
     */
    function group_get_appid_group_list2($limit, $offset, $group_type)
    {
        #构造新消息
        $msg = [
            'Limit'     => $limit,
            'Offset'    => $offset,
            'GroupType' => $group_type,
        ];
        $ret = $this->httpPostJson('v4/group_open_http_svc/get_appid_group_list', $msg);

        return $ret;
    }

    /**
     * 创建群
     * @param string $group_type 群类型, 包括Public(公开群), Private(私密群), ChatRoom(聊天室)
     * @param string $group_name 群名称
     * @param string $owner_id   群主id, 自动添加到群成员中.如果不填，则群没有群主
     * @return array 通过解析REST接口json返回包得到的关联数组，包含新建的群号、成功与否、错误提示等字段
     */
    function group_create_group($group_type, $group_name, $owner_id)
    {
        #构造高级接口所需参数
        $info_set = [
            'group_id'       => null,
            'introduction'   => null,
            'notification'   => null,
            'face_url'       => null,
            'max_member_num' => 500,
        ];
        $mem_list = [];

        $ret = $this->group_create_group2($group_type, $group_name, $owner_id, $info_set, $mem_list);

        return $ret;
    }

    /**
     * 创建群(高级接口)
     * @param string $group_type 群类型(包括Public(公开群), Private(私密群), ChatRoom(聊天室))
     * @param string $group_name 群名称
     * @param string $owner_id   群主id, 自动添加到群成员中.如果不填，群没有群主
     * @param array  $info_set   存储群组基本信息的字典，内容包括用introduction 群简介, group_id 自定义群组显示出来的id,
     *                           notification 群公告, face_url 群头像url地址, max_member_num 最大群成员数量, apply_join 申请加群处理方式
     *                           (比如FreeAccess 自由加入). php构造示例:
     *
     *    $info_set = array(
     *        'introduction' => "群简介"(string),
     *        'group_id' => "自定义群组id"(string),
     *        'notificatoin' => "群公告"(string),
     *        'face_url' => "群头像url地址"(string),
     *        'max_member_num' => 最大群成员数量(int),
     *        'apply_join' => "申请加群的处理方式"(string)
     *        );
     *
     * @param array  $mem_list   初始群成员列表，最多500个，每个群成员由Member_Account(用户id), Role(角色, 比如管理员Admin,
     *                           普通成员Member)组成. php构造示例:
     *
     *     $mem_list = array();
     *     $mem_account = array()(
     *         "Member_Account" => "bob",   // 成员id
     *         "Role" => "Admin"   // 赋予该成员的身份，目前备选项只有Admin
     *     );
     *     array_push($account_list, $mem_account);  //$mem_account为用户id，需要用户传递
     *
     * @return array 通过解析REST接口json返回包得到的关联数组，包含新建的群号、成功与否、错误提示等字段
     */
    function group_create_group2($group_type, $group_name, $owner_id, $info_set, $mem_list)
    {
        #构造新消息
        $msg = [
            'Type'           => $group_type,
            'Name'           => $group_name,
            'Owner_Account'  => $owner_id,
            'GroupId'        => $info_set['group_id'],
            'Introduction'   => $info_set['introduction'],
            'Notification'   => $info_set['notification'],
            'FaceUrl'        => $info_set['face_url'],
            'MaxMemberCount' => $info_set['max_member_num'],
            //	'ApplyJoinOption' => $info_set['apply_join'],
            'MemberList'     => $mem_list,
        ];
        $ret = $this->httpPostJson('v4/group_open_http_svc/create_group', $msg);

        return $ret;
    }

    /**
     * 转让群组
     * @param string $group_id  需要转让的群组id
     * @param string $new_owner 需要设置的新群主id
     * @return array 通过解析REST接口json返回包得到的关联数组，包含成功与否、错误提示等字段
     */
    function group_change_group_owner($group_id, $new_owner)
    {
        #构造新消息
        $msg = [
            'GroupId'          => $group_id,
            'NewOwner_Account' => $new_owner,
        ];
        $ret = $this->httpPostJson('v4/group_open_http_svc/change_group_owner', $msg);

        return $ret;
    }

    /**
     * 获取群组详细信息
     * @param string $group_id 需要获取信息的群组id
     * @return array 通过解析REST接口json返回包得到的关联数组，包含拉取到的群组信息(如果成功)、成功与否、错误提示等字段
     */
    function group_get_group_info($group_id)
    {

        #构造高级接口所需参数
        $group_list = [];
        array_push($group_list, $group_id);
        $base_info_filter   = [
            "Type",               //群类型(包括Public(公开群), Private(私密群), ChatRoom(聊天室))
            "Name",               //群名称
            "Introduction",       //群简介
            "Notification",       //群公告
            "FaceUrl",            //群头像url地址
            "CreateTime",         //群组创建时间
            "Owner_Account",      //群主id
            "LastInfoTime",       //最后一次系统通知时间
            "LastMsgTime",        //最后一次消息发送时间
            "MemberNum",          //群组当前成员数目
            "MaxMemberNum",       //群组内最大成员数目
            "ApplyJoinOption"     //加群处理方式(比如FreeAccess 自由加入)
        ];
        $member_info_filter = [
            "Account",         // 成员ID
            "Role",            // 成员身份
            "JoinTime",        // 成员加入时间
            "LastSendMsgTime", // 该成员最后一次发送消息时间
            "ShutUpUntil"      // 该成员被禁言直到某时间
        ];
        $app_define_filter  = [
            "GroupTestData1",  //自定义数据
        ];

        $ret = $this->group_get_group_info2($group_list, $base_info_filter, $member_info_filter, $app_define_filter);

        return $ret;
    }

    /**
     * 获取群组详细信息(高级接口)
     * @param array $group_list         群组集合. php构造示例:
     *
     *   $group_list = array();
     *   array_push($group_list, "group_id"); //group_id 为群组号码
     *
     * @param array $base_info_filter   基础信息字段过滤器. php构造示例:
     *
     *     $base_info_filter = array(
     *         "Type",               //群类型(包括Public(公开群), Private(私密群), ChatRoom(聊天室))
     *         "Name",               //群名称
     *         "Introduction",       //群简介
     *         "Notification",       //群公告
     *         "FaceUrl",            //群头像url地址
     *         "CreateTime",         //群组创建时间
     *         "Owner_Account",      //群主id
     *         "LastInfoTime",       //最后一次系统通知时间
     *         "LastMsgTime",        //最后一次消息发送时间
     *         "MemberNum",          //群组当前成员数目
     *         "MaxMemberNum",       //群组内最大成员数目
     *         "ApplyJoinOption"     //申请加群处理方式(比如FreeAccess 自由加入)
     *         );
     *
     * @param array $member_info_filter 成员信息字段过滤器, php构造示例:
     *
     *        $member_info_filter = array(
     *            "Account",           // 成员ID
     *            "Role",               // 成员身份
     *
     *            "JoinTime",        // 成员加入时间
     *            "LastSendMsgTime", // 该成员最后一次发送消息时间
     *            "ShutUpUntil"      // 该成员被禁言直到某时间
     *            );
     *
     * @param array $app_define_filter  群组维度的自定义字段过滤器, php构造示例:
     *
     *        $app_define_filter = array(
     *            "GroupTestData1",  //自定义数据
     *            );
     * @return array 通过解析REST接口json返回包得到的关联数组，包含拉取到的群组信息(如果成功)、成功与否、错误提示等字段
     */
    function group_get_group_info2($group_list, $base_info_filter, $member_info_filter, $app_define_filter)
    {
        #构造新消息
        $filter                             = new Filter();
        $filter->GroupBaseInfoFilter        = $base_info_filter;
        $filter->MemberInfoFilter           = $member_info_filter;
        $filter->AppDefinedDataFilter_Group = $app_define_filter;
        $msg                                = [
            'GroupIdList'    => $group_list,
            'ResponseFilter' => $filter,
        ];

        $ret = $this->httpPostJson('v4/group_open_http_svc/get_group_info', $msg);

        return $ret;
    }

    /**
     * 获取群组成员详细信息
     * @param string $group_id 群组id
     * @param int    $limit    最多获取多少个成员, 如果不填, 获取全部成员
     * @param int    $offset   从第几个成员开始获取, 如果不填, 从第一个成员开始获取
     * @return array 通过解析REST接口json返回包得到的关联数组，包含拉取到的群组成员详细信息(如果成功)、成功与否、错误提示等字段
     */
    function group_get_group_member_info($group_id, $limit, $offset)
    {
        #构造新消息
        $msg = [
            "GroupId" => $group_id,
            "Limit"   => $limit,
            "Offset"  => $offset,
        ];
        $ret = $this->httpPostJson('v4/group_open_http_svc/get_group_member_info', $msg);

        return $ret;
    }

    /**
     * 修改群组名字
     * @param string $group_id   群组id
     * @param string $group_name 将其作为群组名字
     * @return string 返回成功与否，及错误提示(如果有错误)
     * @return array 通过解析REST接口json返回包得到的关联数组，包含成功与否、错误提示等字段
     */
    function group_modify_group_base_info($group_id, $group_name)
    {

        #构造高级接口所需参数
        $info_set        = [
            'introduction'   => null,
            'notification'   => null,
            'face_url'       => null,
            'max_member_num' => null,
            //	'apply_join' => "NeedPermission"
        ];
        $app_define_list = [];

        $ret = $this->group_modify_group_base_info2($group_id, $group_name, $info_set, $app_define_list);

        return $ret;
    }

    /**
     * 修改群组信息(高级接口)
     * @param string $group_id        群组id
     * @param string $group_name      群组名字
     * @param array  $info_set        需要修改的群组基本信息的字典集合, 包括群简介，群公告， 群头像url地址，群成员最大数量,
     *                                申请加群方式. php构造示例:
     *
     *    $info_set = array(
     *        'introduction' => "群简介"(string),
     *        'notification' => "群公告"(string),
     *        'face_url' => "群头像url地址(string)",
     *        'max_member_num' => "群成员最大数量"(string),
     *        'apply_join' => "申请加入方式"(string)
     *        );
     *
     * @param array  $app_define_list 自定义字段. php构造示例:
     *
     *     $app_define_list = array();
     *     //定义自定义字段字典数组
     *     $app_define_one = array()(
     *         "Key": "GroupTestData1",  // 需要修改的自定义字段key
     *         "Value": "NewData"  // 自定义字段的新值
     *         );
     *    array_push($app_define_list, $app_define_one);
     *
     * @return array 通过解析REST接口json返回包得到的关联数组，包含成功与否、错误提示等字段
     */
    function group_modify_group_base_info2($group_id, $group_name, $info_set, $app_define_list)
    {

        #构造新消息
        $msg = [
            "GroupId"        => $group_id,
            "Name"           => $group_name,
            "Introduction"   => $info_set['introduction'],
            "Notification"   => $info_set['notification'],
            "FaceUrl"        => $info_set['face_url'],
            "MaxMemberNum"   => $info_set['max_member_num'],
            //	"ApplyJoinOption" => $info_set['apply_join'],
            "AppDefinedData" => $app_define_list,
        ];
        $ret = $this->httpPostJson('v4/group_open_http_svc/modify_group_base_info', $msg);

        return $ret;
    }

    /**
     * 增加群组成员
     * @param string $group_id  要操作的群组id
     * @param string $member_id 要加入的用户id
     * @param int    $silence   是否静默加入, 0为否， 1为是
     * @return array 通过解析REST接口json返回包得到的关联数组，包含成功与否、错误提示等字段
     */
    function group_add_group_member($group_id, $member_id, $silence)
    {

        #构造新消息
        $mem_list = [];
        $mem_elem = [
            "Member_Account" => $member_id,
        ];
        array_push($mem_list, $mem_elem);
        $msg = [
            "GroupId"    => $group_id,
            "MemberList" => $mem_list,
            "Silence"    => $silence,
        ];
        $ret = $this->httpPostJson('v4/group_open_http_svc/add_group_member', $msg);

        return $ret;
    }

    /**
     * 删除群组成员
     * @param string $group_id  要操作的群组id
     * @param string $member_id 要删除的成员id
     * @param int silence 是否静默删除, 0为否，1为是
     * @return array 通过解析REST接口json返回包得到的关联数组，包含成功与否、错误提示等字段
     */
    function group_delete_group_member($group_id, $member_id, $silence)
    {

        #构造新消息
        $mem_list = [];
        array_push($mem_list, $member_id);
        $msg = [
            "GroupId"             => $group_id,
            "MemberToDel_Account" => $mem_list,
            "Silence"             => $silence,
        ];

        $ret = $this->httpPostJson('v4/group_open_http_svc/delete_group_member', $msg);

        return $ret;
    }

    /**
     * 修改群成员身份
     * @param string $group_id   要操作的群组id
     * @param string $account_id 要操作的用户id
     * @param string $role       用户身份(Admin/Member)
     * @return array 通过解析REST接口json返回包得到的关联数组，包含成功与否、错误提示等字段
     */
    function group_modify_group_member_info($group_id, $account_id, $role)
    {

        #构造高级接口所需参数
        $ret = $this->group_modify_group_member_info2($group_id, $account_id, $role, "AcceptAndNotify", 0);

        return $ret;
    }

    /**
     * 修改群成员资料(高级接口)
     * @param string $group_id    要操作的群组id
     * @param string $account_id  用户id
     * @param string $role        Admin或者Member, 分别为设置/取消管理员, 为null则不改变成员身份
     * @param string $msg_flag    消息屏蔽类型,比如AcceptAndNotify(接收并提示), 为null则不改变屏蔽类型
     * @param int    $shutup_time 禁言时间
     * @return array 通过解析REST接口json返回包得到的关联数组，包含成功与否、错误提示等字段
     */
    function group_modify_group_member_info2($group_id, $account_id, $role, $msg_flag, $shutup_time)
    {

        #构造新消息
        $msg = [
            "GroupId"        => $group_id,
            "Member_Account" => $account_id,
            "Role"           => $role,
        ];
        $ret = $this->httpPostJson('v4/group_open_http_svc/modify_group_member_info', $msg);

        return $ret;
    }

    /**
     * 解散群
     * @param string $group_id 群组id
     * @return array 通过解析REST接口json返回包得到的关联数组，包含成功与否、错误提示等字段
     */
    function group_destroy_group($group_id)
    {

        #构造新消息
        $msg = [
            "GroupId" => $group_id,
        ];
        $ret = $this->httpPostJson('v4/group_open_http_svc/destroy_group', $msg);

        return $ret;
    }

    /**
     * 获取某一用户加入的群组
     * @param string $account_id 用户id
     * @return array 通过解析REST接口json返回包得到的关联数组，包含该用户加入的群的信息(如果成功), 成功与否、错误提示等字段
     */
    function group_get_joined_group_list($account_id)
    {

        #构造高级接口所需参数
        $base_info_filter = [
            "Type",               //群类型(包括Public(公开群), Private(私密群), ChatRoom(聊天室))
            "Name",               //群名称
            "Introduction",       //群简介
            "Notification",       //群公告
            "FaceUrl",            //群头像url地址
            "CreateTime",         //群组创建时间
            "Owner_Account",      //群主id
            "LastInfoTime",       //最后一次系统通知时间
            "LastMsgTime",        //最后一次消息发送时间
            "MemberNum",          //群组当前成员数目
            "MaxMemberNum",       //群组内最大成员数目
            "ApplyJoinOption"     //申请加群处理方式(比如FreeAccess 自由加入, NeedPermission 需要同意)
        ];
        $self_info_filter = [
            "Role",            //群内身份(Amin/Member)
            "JoinTime",        //入群时间
            "MsgFlag",         //消息屏蔽类型
            "UnreadMsgNum"     //未读消息数量
        ];
        $ret              = $this->group_get_joined_group_list2($account_id, null, $base_info_filter, $self_info_filter);

        return $ret;
    }

    /**
     * 获取某一用户加入的群组(高级接口)
     * @param string $account_id       用户id
     * @param string $group_type       拉取哪种群组形态(Pulic(公开群)/Private(私密群)/ChatRoom(聊天室)),不填为拉取所有
     * @param array  $base_info_filter 基础信息字段过滤器. php构造示例:
     *
     *    $base_info_filter = array(
     *         "Type",               //群类型(包括Public(公开群), Private(私密群), ChatRoom(聊天室))
     *         "Name",               //群名称
     *         "Introduction",       //群简介
     *         "Notification",       //群公告
     *         "FaceUrl",            //群头像url地址
     *         "CreateTime",         //群组创建时间
     *         "Owner_Account",      //群主id
     *         "LastInfoTime",       //最后一次系统通知时间
     *         "LastMsgTime",        //最后一次消息发送时间
     *         "MemberNum",          //群组当前成员数目
     *         "MaxMemberNum",       //群组内最大成员数目
     *         "ApplyJoinOption"     //申请加群处理方式(比如FreeAccess 自由加入, NeedPermission 需要同意)
     *         );
     *
     * @param array  $self_info_filter 自身在群内的消息过滤器. php构造示例:
     *
     *    $self_info_filter = array(
     *            "Role",            //群内身份(Amin/Member)
     *            "JoinTime",        //入群时间
     *            "MsgFlag",         //消息屏蔽类型
     *            "UnreadMsgNum"     //未读消息数量
     *         );
     * @return array 通过解析REST接口json返回包得到的关联数组，包含该用户加入的群的信息(如果成功), 成功与否、错误提示等字段
     */
    function group_get_joined_group_list2($account_id, $group_type, $base_info_filter, $self_info_filter)
    {

        #构造新消息
        $filter                      = new Filter();
        $filter->GroupBaseInfoFilter = $base_info_filter;
        $filter->SelfInfoFilter      = $self_info_filter;
        $msg                         = [
            "Member_Account" => $account_id,
            "ResponseFilter" => $filter,
        ];

        $ret = $this->httpPostJson('v4/group_open_http_svc/get_joined_group_list', $msg);

        return $ret;
    }

    /**
     * 查询用户在某个群组中的身份
     * @param string $group_id  群组id
     * @param string $member_id 要查询的用户
     * @return array 通过解析REST接口json返回包得到的关联数组，包含该用户在某个群的身份(如果成功), 成功与否、错误提示等字段
     */
    function group_get_role_in_group($group_id, $member_id)
    {

        #构造新消息
        $mem_list = [];
        array_push($mem_list, $member_id);
        $msg = [
            "GroupId"      => $group_id,
            "User_Account" => $mem_list,
        ];

        $ret = $this->httpPostJson('v4/group_open_http_svc/get_role_in_group', $msg);

        return $ret;
    }

    /**
     * 批量禁言/取消禁言
     * @param string $group_id  群组id
     * @param string $member_id 要禁言/取消禁言 的用户
     * @param int    $second    表示禁言多少秒, 0表示取消禁言
     * @return array 通过解析REST接口json返回包得到的关联数组，包含该用户在某个群的身份(如果成功), 成功与否、错误提示等字段
     */
    function group_forbid_send_msg($group_id, $member_id, $second)
    {

        #构造新消息
        $mem_list = [];
        array_push($mem_list, $member_id);
        $msg = [
            "GroupId"         => $group_id,
            "Members_Account" => $mem_list,
            "ShutUpTime"      => $second,
        ];
        $ret = $this->httpPostJson('v4/group_open_http_svc/forbid_send_msg', $msg);

        return $ret;
    }

    /**
     * 在某一群组里发普通消息
     * @param string $account_id   发送消息的用户
     * @param string $group_id     群组id
     * @param string $text_content 要发送的信息(均为文本消息)
     * @return array 通过解析REST接口json返回包得到的关联数组，包含成功与否、错误提示等字段
     */
    function group_send_group_msg($account_id, $group_id, $text_content)
    {
        #构造高级接口所需参数
        $msg_content = [];
        //创建array 所需元素
        $msg_content_elem = [
            'MsgType'    => 'TIMTextElem',       //文本类型
            'MsgContent' => [
                'Text' => $text_content,                //hello 为文本信息
            ],
        ];
        array_push($msg_content, $msg_content_elem);
        $ret = $this->group_send_group_msg2($account_id, $group_id, $msg_content);

        return $ret;
    }

    /**
     * 在某一群组里发送图片
     * @param string $account_id 发送消息的用户
     * @param string $group_id   群组id
     * @param string $pic_path   要发送图片的本地路径
     * @return array 通过解析REST接口json返回包得到的关联数组，包含成功与否、错误提示等字段
     */
    function group_send_group_msg_pic($account_id, $group_id, $pic_path)
    {

        #构造高级接口所需参数
        //上传图片并获取url
        $busi_type = 1; //表示群消息
        $ret       = $this->openpic_pic_upload($account_id, $group_id, $pic_path, $busi_type);
        $tmp       = $ret["URL_INFO"];

        $uuid    = $ret["File_UUID"];
        $pic_url = $tmp[0]["DownUrl"];

        $img_info = [];
        $img_tmp  = $ret["URL_INFO"][0];
        if ($img_tmp["PIC_TYPE"] == 4) {
            $img_tmp["PIC_TYPE"] = 3;
        }
        $img_info_elem1 = [
            "URL"    => $img_tmp["DownUrl"],
            "Height" => $img_tmp["PIC_Height"],
            "Size"   => $img_tmp["PIC_Size"],
            "Type"   => $img_tmp["PIC_TYPE"],
            "Width"  => $img_tmp["PIC_Width"],
        ];

        $img_tmp = $ret["URL_INFO"][1];
        if ($img_tmp["PIC_TYPE"] == 4) {
            $img_tmp["PIC_TYPE"] = 3;
        }
        $img_info_elem2 = [
            "URL"    => $img_tmp["DownUrl"],
            "Height" => $img_tmp["PIC_Height"],
            "Size"   => $img_tmp["PIC_Size"],
            "Type"   => $img_tmp["PIC_TYPE"],
            "Width"  => $img_tmp["PIC_Width"],
        ];

        $img_tmp = $ret["URL_INFO"][2];
        if ($img_tmp["PIC_TYPE"] == 4) {
            $img_tmp["PIC_TYPE"] = 3;
        }
        $img_info_elem3 = [
            "URL"    => $img_tmp["DownUrl"],
            "Height" => $img_tmp["PIC_Height"],
            "Size"   => $img_tmp["PIC_Size"],
            "Type"   => $img_tmp["PIC_TYPE"],
            "Width"  => $img_tmp["PIC_Width"],
        ];

        array_push($img_info, $img_info_elem1);
        array_push($img_info, $img_info_elem2);
        array_push($img_info, $img_info_elem3);
        $msg_content = [];
        //创建array 所需元素
        $msg_content_elem = [
            'MsgType'    => 'TIMImageElem',       //文本类型
            'MsgContent' => [
                'UUID'           => $uuid,
                'ImageInfoArray' => $img_info,
            ],
        ];
        //将创建的元素$msg_content_elem, 加入array $msg_content
        array_push($msg_content, $msg_content_elem);

        $ret = $this->group_send_group_msg2($account_id, $group_id, $msg_content);

        return $ret;
    }

    /**
     * 在某一群组里发普通消息(高级接口)
     * @param string $account_id  发送消息的用户
     * @param string $group_id    群组id
     * @param array  $msg_content 要发送的消息集合，这里包括文本消息和表情消息. php构造示例:
     *
     *     //创建array $msg_content
     *     $msg_content = array();
     *     //创建array 所需元素
     *     $msg_content_text = array(
     *         'MsgType' => 'TIMTextElem',       //文本类型
     *         'MsgContent' => array(
     *         'Text' => "hello",                //"hello" 为文本信息
     *        )
     *
     *   $msg_content_face = array(
     *         'MsgType' => 'TIMTextElem',       //表情类型
     *         'MsgContent' => array(
     *         'Data' => "abc\u0000\u0001",      //"abc\u0000\u0001" 为图片信息
     *        )
     *
     *     array_push($msg_content, $msg_content_text);
     *     array_push($msg_content, $msg_content_face);
     *     );
     * @return array 通过解析REST接口json返回包得到的关联数组，包含成功与否、错误提示等字段
     */
    function group_send_group_msg2($account_id, $group_id, $msg_content)
    {
        #构造新消息
        $msg = [
            "GroupId"      => $group_id,
            "From_Account" => $account_id,
            "Random"       => rand(1, 65535),
            "MsgBody"      => $msg_content,
        ];

        $ret = $this->httpPostJson('v4/group_open_http_svc/send_group_msg', $msg);

        return $ret;
    }

    /**
     * 在某一群组发系统消息
     * @param string $group_id    群组id
     * @param string $content     系统通知内容，支持二进制数组
     * @param string $receiver_id 接收者群成员id，为空表示全员下发
     * @return array 通过解析REST接口json返回包得到的关联数组，包含成功与否、错误提示等字段
     */
    function group_send_group_system_notification($group_id, $text_content, $receiver_id)
    {

        #构造高级接口所需参数
        $receiver_list = [];
        if ($receiver_id != null) {
            array_push($receiver_list, $receiver_id);
        }
        $ret = $this->group_send_group_system_notification2($group_id, $text_content, $receiver_list);

        return $ret;
    }

    /**
     * 在某一群组发系统消息(高级接口)
     * @param string $group_id      群组id
     * @param string $content       系统通知内容，支持二进制数组
     * @param array  $receiver_list 接收此系统提示的用户id集合, 为空表示发送给全员. php构造示例:
     *
     *   $receiver_list = array(
     *         "peter",
     *         "leckie"
     *     )
     * @return array 通过解析REST接口json返回包得到的关联数组，包含成功与否、错误提示等字段
     */
    function group_send_group_system_notification2($group_id, $content, $receiver_list)
    {
        #构造新消息
        $msg = [
            "GroupId"           => $group_id,
            "ToMembers_Account" => $receiver_list,
            "Content"           => $content,
        ];

        $ret = $this->httpPostJson('v4/group_open_http_svc/send_group_system_notification', $msg);

        return $ret;
    }

    /**
     * 导入群成员(只导入一个成员, 入群时间默认为当前)
     * @param string $group_id  要操作的群组id
     * @param string $member_id 要导入的用户id
     * @param string $role      要导入的用户的身份(现可填值只有Admin)，不填默认为Member
     * @return array 通过解析REST接口json返回包得到的关联数组，包含成功与否、错误提示等字段
     */
    function group_import_group_member($group_id, $member_id, $role)
    {
        #构造高级接口所需参数
        $member_list = [];
        $member_elem = [
            "Member_Account" => $member_id,
            "Role"           => $role,
        ];
        array_push($member_list, $member_elem);
        $ret = $this->group_import_group_member2($group_id, $member_list);

        return $ret;
    }

    /**
     * 导入群成员(批量导入)
     * @param string $group_id    要操作的群组id
     * @param string $member_list 要导入的用户id集合，构造示例:
     *
     *   $member_list = array();
     *   $member_elem = array(
     *      "Member_Account" => $member_id,
     *      "Role" => $role
     *   );
     *   array_push($member_list, $member_elem);
     *
     * @return array 通过解析REST接口json返回包得到的关联数组，包含成功与否、错误提示等字段
     */
    function group_import_group_member2($group_id, $member_list)
    {
        #构造新消息
        $msg = [
            "GroupId"    => $group_id,
            "MemberList" => $member_list,
        ];
        $ret = $this->httpPostJson('v4/group_open_http_svc/import_group_member', $msg);

        return $ret;
    }

    /**
     * 导入一条群文本消息
     * @param string $group_id     要操作的群组id
     * @param string $from_account 该消息发送者
     * @param int    $text         文本消息内容
     * @return array 通过解析REST接口json返回包得到的关联数组，包含成功与否、错误提示等字段
     */
    function group_import_group_msg($group_id, $from_account, $text)
    {

        #构造高级接口所需参数
        //构造MsgBody
        $msg_content   = [
            "Text" => $text,
        ];
        $msg_body_elem = [
            "MsgType"    => "TIMTextElem",
            "MsgContent" => $msg_content,
        ];
        $msg_body_list = [];
        array_push($msg_body_list, $msg_body_elem);
        //构造MsgList的一个元素
        $msg_list_elem = [
            "From_Account" => $from_account,
            "SendTime"     => time(),
            "Random"       => rand(1, 65535),
            "MsgBody"      => $msg_body_list,
        ];
        //构造MsgList
        $msg_list = [];
        array_push($msg_list, $msg_list_elem);
        $ret = $this->group_import_group_msg2($group_id, $msg_list);

        return $ret;
    }

    /**
     * 导入群消息(高级接口, 一次最多导入20条)
     * @param string $group_id 要操作的群组id
     * @param string $msg_list 消息集合, 构造方式如下：
     *
     *   //构造MsgBody
     *   $msg_content = array(
     *       "Text" => $text
     *   );
     *   $msg_body_elem = array(
     *       "MsgType" => "TIMTextElem",
     *       "MsgContent" => $msg_content,
     *   );
     *   $msg_body_list = array();
     *   array_push($msg_body_list, $msg_body_elem);
     *   //构造MsgList的一个元素
     *   $msg_list_elem = array(
     *       "From_Account" => $from_account,
     *       "SendTime" => time(),
     *       "Random" => rand(1, 65535),
     *       "MsgBody" => $msg_body_list
     *   );
     *   //构造MsgList
     *   $msg_list = array();
     *   array_push($msg_list, $msg_list_elem);
     *
     * @return array 通过解析REST接口json返回包得到的关联数组，包含成功与否、错误提示等字段
     */
    function group_import_group_msg2($group_id, $msg_list)
    {
        #构造新消息
        $msg = [
            "GroupId" => $group_id,
            "MsgList" => $msg_list,
        ];
        $ret = $this->httpPostJson('v4/group_open_http_svc/import_group_msg', $msg);

        return $ret;
    }

    /**
     * 设置群组成员未读计数
     * @param string $group_id       要操作的群组id
     * @param string $member_account 要操作的群成员
     * @param int    $unread_msg_num 该成员的未读计数
     * @return array 通过解析REST接口json返回包得到的关联数组，包含成功与否、错误提示等字段
     */
    function group_set_unread_msg_num($group_id, $member_account, $unread_msg_num)
    {
        #构造新消息
        $msg = [
            "GroupId"        => $group_id,
            "Member_Account" => $member_account,
            "UnreadMsgNum"   => (int)$unread_msg_num,
        ];

        $ret = $this->httpPostJson('v4/group_open_http_svc/set_unread_msg_num', $msg);

        return $ret;
    }

    /**
     * 打开图片并上传
     * @param $account_id
     * @param $receiver
     * @param $pic_path
     * @param $busi_type
     * @return mixed
     */
    public function openpic_pic_upload($account_id, $receiver, $pic_path, $busi_type)
    {

        #获取长度和md5值
        $pic_data = file_get_contents($pic_path);
        $md5      = md5($pic_data);
        $pic_size = filesize($pic_path);

        #进行base64处理
        $fp       = fopen($pic_path, "r");
        $pic_data = fread($fp, $pic_size);

        $slice_data = [];
        $slice_size = [];
        $SLICE_SIZE = 32 * 4096;

        //对文件进行分片
        for ($i = 0; $i < $pic_size; $i = $i + $SLICE_SIZE) {
            if ($i + $SLICE_SIZE > $pic_size) {
                break;
            }
            $slice_tmp    = substr($pic_data, $i, $SLICE_SIZE);
            $slice_tmp    = chunk_split(base64_encode($slice_tmp));
            $slice_tmp    = str_replace("\r\n", '', $slice_tmp);
            $slice_size[] = $SLICE_SIZE;
            $slice_data[] = $slice_tmp;
        }

        //最后一个分片
        if ($i - $SLICE_SIZE < $pic_size) {
            $slice_size[] = $pic_size - $i;
            $tmp          = substr($pic_data, $i, $pic_size - $i);
            $slice_size[] = strlen($tmp);
            $tmp          = chunk_split(base64_encode($tmp));
            $tmp          = str_replace("\r\n", '', $tmp);

            $slice_data[] = $tmp;
        }
        $pic_rand      = rand(1, 65535);
        $time_stamp    = time();
        $req_data_list = [];
        $sentOut       = 0;
        //printf("handle %d segments\n", count($slice_data) - 1);
        for ($i = 0; $i < count($slice_data) - 1; $i++) {
            #构造消息
            $msg = [
                "From_Account" => $account_id,  //发送者
                "To_Account"   => $receiver,      //接收者
                "App_Version"  => 1.4,       //应用版本号
                "Seq"          => $i + 1,                      //同一个分片需要保持一致
                "Timestamp"    => $time_stamp,         //同一张图片的不同分片需要保持一致
                "Random"       => $pic_rand,              //同一张图片的不同分片需要保持一致
                "File_Str_Md5" => $md5,         //图片MD5，验证图片的完整性
                "File_Size"    => $pic_size,       //图片原始大小
                "Busi_Id"      => $busi_type,                    //群消息:1 c2c消息:2 个人头像：3 群头像：4
                "PkgFlag"      => 1,                 //同一张图片要保持一致: 0表示图片数据没有被处理 ；1-表示图片经过base64编码，固定为1
                "Slice_Offset" => $i * $SLICE_SIZE,           //必须是4K的整数倍
                "Slice_Size"   => $slice_size[$i],        //必须是4K的整数倍,除最后一个分片列外
                "Slice_Data"   => $slice_data[$i]     //PkgFlag=1时，为base64编码
            ];
            array_push($req_data_list, $msg);
            $sentOut = 0;
            if ($i != 0 && ($i + 1) % 4 == 0) {
                //将消息序列化为json串
                $req_data_list = json_encode($req_data_list);
                //printf("\ni = %d, call multi_api once\n", $i);
                // 更改为异步

                //$ret = $this->multi_api("openpic", "pic_up", $this->identifier, $this->usersig, $req_data_list, false);
                if (gettype($ret) == "string") {
                    $ret = json_decode($ret, true);

                    return $ret;
                }
                $req_data_list = [];
                $sentOut       = 1;
            }
        }
        if ($sentOut == 0) {
            //$req_data_list = json_encode($req_data_list);
            //printf("\ni = %d, call multi_api once\n", $i);
            $this->httpAsyncPostJson('v4/openpic/pic_up', $req_data_list);
            //$this->multi_api("openpic", "pic_up", $this->identifier, $this->usersig, $req_data_list, false);
        }

        #最后一个分片
        $msg = [
            "From_Account" => $account_id,    //发送者
            "To_Account"   => $receiver,        //接收者
            "App_Version"  => 1.4,        //应用版本号
            "Seq"          => $i + 1,                        //同一个分片需要保持一致
            "Timestamp"    => $time_stamp,            //同一张图片的不同分片需要保持一致
            "Random"       => $pic_rand,                //同一张图片的不同分片需要保持一致
            "File_Str_Md5" => $md5,            //图片MD5，验证图片的完整性
            "File_Size"    => $pic_size,        //图片原始大小
            "Busi_Id"      => $busi_type,                    //群消息:1 c2c消息:2 个人头像：3 群头像：4
            "PkgFlag"      => 1,                    //同一张图片要保持一致: 0表示图片数据没有被处理 ；1-表示图片经过base64编码，固定为1
            "Slice_Offset" => $i * $SLICE_SIZE,            //必须是4K的整数倍
            "Slice_Size"   => $slice_size[count($slice_data) - 1],        //必须是4K的整数倍,除最后一个分片列外
            "Slice_Data"   => $slice_data[count($slice_data) - 1]        //PkgFlag=1时，为base64编码
        ];

        //$req_data = json_encode($msg);
        $ret = $this->httpPostJson('v4/openpic/pic_up', $msg);
        //dd($ret);
        //$ret = json_decode($ret, true);
        //echo json_format($ret);

        return $ret;
    }
}

//辅助过滤器类
class Filter
{
}

;