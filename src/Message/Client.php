<?php

/*
 * This file is part of the mingyoung/dingtalk.
 *
 * (c) mingyoung <mingyoungcheung@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace TencentIm\Message;

use TencentIm\Kernel\BaseClient;
use TencentIm\Kernel\Messages\Message;

/**
 * Class Client.
 *
 * @author mingyoung <mingyoungcheung@gmail.com>
 */
class Client extends BaseClient
{
    /**
     * @var array
     */
    protected $data = [];

    /**
     * 发文本消息
     * @param string $account_id   发送者id
     * @param string $receiver     接收方的用户账号
     * @param string $text_content 消息内容(这里为文本消息)
     * @return array 通过解析REST接口json返回包得到的关联数组, 其中包含成功与否、及错误提示(如果有错误)等字段
     */
    public function openim_send_msg($account_id, $receiver, $text_content)
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
        //将创建的元素$msg_content_elem, 加入array $msg_content
        array_push($msg_content, $msg_content_elem);

        $ret = $this->openim_send_msg2($account_id, $receiver, $msg_content);

        return $ret;
    }

    /**
     * 单发消息(高级接口)
     * @param string $account_id  发送者id
     * @param string $receiver    接收方的用户账号
     * @param array  $msg_content 消息内容, php构造示例:
     *
     *     $msg_content = array();
     *     //创建array 所需元素
     *     $msg_content_elem = array(
     *         'MsgType' => 'TIMTextElem',       //文本类型
     *         'MsgContent' => array(
     *         'Text' => "hello",                //hello 为文本信息
     *        )
     *     );
     *     //将创建的元素$msg_content_elem, 加入array $msg_content
     *     array_push($msg_content, $msg_content_elem);
     *
     * @return array 通过解析REST接口json返回包得到的关联数组, 其中包含成功与否、及错误提示(如果有错误)等字段
     */
    public function openim_send_msg2($account_id, $receiver, $msg_content)
    {
        #构造新消息
        $body = [
            'To_Account'   => $receiver,
            'MsgSeq'       => rand(1, 65535),
            'MsgRandom'    => rand(1, 65535),
            'MsgTimeStamp' => time(),
            'MsgBody'      => $msg_content,
            'From_Account' => $account_id,
        ];
        #将消息序列化为json串
        $result = $this->httpPostJson('v4/openim/sendmsg', $body);

        return $result;
    }

    /**
     * 发图片消息(图片不大于10M)
     * @param string $account_id 发送者id
     * @param string $receiver   接收方的用户账号
     * @param string $pic_path   要发送的图片本地路径
     * @return array 通过解析REST接口json返回包得到的关联数组, 其中包含成功与否、及错误提示(如果有错误)等字段
     */
    public function openim_send_msg_pic($account_id, $receiver, $pic_path)
    {
        #构造高级接口所需参数
        //上传图片并获取url
        $busi_type = 2; //表示C2C消息
        $ret       = $this->openpic_pic_upload($account_id, $receiver, $pic_path, $busi_type);
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

        $ret = $this->openim_send_msg2($account_id, $receiver, $msg_content);

        return $ret;
    }

    /**
     * 批量发文本消息
     * @param array  $account_list 接收消息的用户id集合
     * @param string $text_content 消息内容(这里为文本消息)
     * @return array 通过解析REST接口json返回包得到的关联数组, 其中包含成功与否、及错误提示(如果有错误)等字段
     */
    public function openim_batch_sendmsg($account_list, $text_content)
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
        //将创建的元素$msg_content_elem, 加入array $msg_content
        array_push($msg_content, $msg_content_elem);

        $ret = $this->openim_batch_sendmsg2($account_list, $msg_content);

        return $ret;
    }

    /**
     * 批量发图片
     * @param array  $account_list 接收消息的用户id集合
     * @param string $pic_path     要发送图片的本地路径
     * @return array 通过解析REST接口json返回包得到的关联数组, 其中包含成功与否、及错误提示(如果有错误)等字段
     */
    public function openim_batch_sendmsg_pic($account_list, $pic_path)
    {

        #构造高级接口所需参数
        //上传图片并获取url
        $busi_type = 2; //表示C2C消息
        $ret       = $this->openpic_pic_upload($this->app['config']->get('identifier'), $account_list[0], $pic_path, $busi_type);
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

        $ret = $this->openim_batch_sendmsg2($account_list, $msg_content);

        return $ret;
    }

    /**
     * 批量发消息(高级接口)
     * @param array $account_list 接收消息的用户id集合
     * @param array $msg_content  消息内容, php构造示例:
     *
     *   $msg_content = array();
     *   //创建array 所需元素
     *   $msg_content_elem = array(
     *       'MsgType' => 'TIMTextElem',       //文本??型
     *       'MsgContent' => array(
     *       'Text' => "hello",                //hello 为文本信息
     *      )
     *   );
     *   //将创建的元素$msg_content_elem, 加入array $msg_content
     *   array_push($msg_content, $msg_content_elem);
     *
     * @return array 通过解析REST接口json返回包得到的关联数组, 其中包含成功与否、及错误提示(如果有错误)等字段
     */
    public function openim_batch_sendmsg2($account_list, $msg_content)
    {
        #构造新消息
        $msg = [
            'To_Account' => $account_list,
            'MsgRandom'  => rand(1, 65535),
            'MsgBody'    => $msg_content,
        ];

        $result = $this->httpPostJson('v4/openim/batchsendmsg', $msg);

        return $result;
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
