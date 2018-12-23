<?php

/*
 * This file is part of the mingyoung/dingtalk.
 *
 * (c) mingyoung <mingyoungcheung@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace TencentIm\Auth;

use TencentIm\Kernel\BaseClient;

/**
 * Class Client.
 *
 * @author guansq <94600115@qq.com>
 */
class Client extends BaseClient
{

    /**
     * 独立模式帐号同步接口
     * @param string $identifier 用户名
     * @param string $nick       用户昵称
     * @param string $face_url   用户头像URL
     * @return array 通过解析REST接口json返回包得到的关联数组，包含成功与否、错误提示等字段
     */
    public function account_import(string $identifier, string $nick = '', string $face_url = '')
    {
        #构造新消息
        /*$pulic_query = [
            'usersig'     => $usersig,
            'identifier'  => 'halobear',
            'sdkappid'    => $this->app['config']->get('app_id'),
            'random'      => str_random(32),
            'contenttype' => 'json',
        ];*/
        $body   = [
            'Identifier' => $identifier,
            'Nick'       => $nick,
            'FaceUrl'    => $face_url,
        ];
        $result = $this->httpPostJson('v4/im_open_login_svc/account_import', $body);

        return $result;
    }
}
