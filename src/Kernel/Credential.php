<?php

/*
 * This file is part of the halobear/tencent-im
 *
 * (c) guansq <94600115@qq.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace TencentIm\Kernel;

use TencentIm\Application;
use TencentIm\Kernel\Exceptions;

/**
 * Class Credential.
 *
 * @author guansq <94600115@qq.com>
 */
class Credential
{
    use MakesHttpRequests;

    /**
     * @var \TencentIm\Application
     */
    protected $app;

    protected $usersig;

    /**
     * Credential constructor.
     *
     * @param \TencentIm\Application $app
     */
    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    /**
     * Get credential token.
     *
     * @return string
     */
    public function token(): string
    {
        if ($value = $this->app['cache']->get($this->cacheKey())) {
            return $value;
        }

        $result = $this->request('GET', 'gettoken', [
            'query' => $this->credentials(),
        ]);

        $this->setToken($token = $result['access_token'], 7000);

        return $token;
    }

    /**
     * @param string                 $token
     * @param int|\DateInterval|null $ttl
     *
     * @return $this
     */
    public function setToken(string $token, $ttl = null)
    {
        $this->app['cache']->set($this->cacheKey(), $token, $ttl);

        return $this;
    }

    /**
     * @return array
     */
    protected function credentials(): array
    {
        return [
            'app_id'     => $this->app['config']->get('app_id'),
            'app_secret' => $this->app['config']->get('app_secret'),
            'key_path'   => $this->app['config']->get('key_path'),
        ];
    }

    /**
     * 独立模式根据Identifier生成UserSig的方法
     * @param int    $identifier         用户账号
     * @param int    $expiry_after       过期时间
     * @param string $protected_key_path 私钥的存储路径及文件名
     * @return string $out 返回的签名字符串
     */
    public function generate_user_sig($identifier, $expiry_after = 86400 * 360, $protected_key_path = '', $tool_path = '')
    {
        $credentials        = $this->credentials();
        $protected_key_path = $protected_key_path ?: $credentials['key_path'];
        $tool_path          = $tool_path ?: base_path('vendor/halobear/tencent-im/src/Command/linux-signature64');
        # 这里需要写绝对路径，开发者根据自己的路径进行调整
        $command = escapeshellarg($tool_path) . ' ' . escapeshellarg($protected_key_path) . ' ' . escapeshellarg($credentials['app_id']) . ' ' . escapeshellarg($identifier);
        $ret     = exec($command, $out, $status);
        if ($status == -1) {
            return null;
        }
        dd($ret);
        $this->usersig = $out[0];

        return $out;
    }


    /**
     * 获取UserSig
     * @param $user
     * @return string
     */
    public function userSig($user)
    {
        $credentials = $this->credentials();

        // 生成user sig
        $sig = '';
        try {
            $sig = $this->genSig($user);
        } catch (Exceptions $exception) {
            abort(400, $exception->getMessage());
        }

        return $sig;
    }

    /**
     * 生成usersig
     * @param string    $identifier 用户名
     * @param float|int $expire     usersig有效期 默认为360天
     * @return string 生成的UserSig 失败时为false
     * @throws Exception
     */
    public function genSig($identifier, $expire = 360 * 86400)
    {
        $json            = [
            'TLS.account_type' => '0',
            'TLS.identifier'   => (string)$identifier,
            'TLS.appid_at_3rd' => '0',
            'TLS.sdk_appid'    => (string)$this->appid,
            'TLS.expire_after' => (string)$expire,
            'TLS.version'      => '201512300000',
            'TLS.time'         => (string)time(),
        ];
        $err             = '';
        $content         = $this->genSignContent($json, $err);
        $signature       = $this->sign($content);
        $json['TLS.sig'] = base64_encode($signature);
        if ($json['TLS.sig'] === false) {
            throw new Exception('base64_encode error');
        }
        $json_text = json_encode($json);
        if ($json_text === false) {
            throw new Exception('json_encode error');
        }
        $compressed = gzcompress($json_text);
        if ($compressed === false) {
            throw new Exception('gzcompress error');
        }

        return $this->base64Encode($compressed);
    }

    /**
     * 根据json内容生成需要签名的buf串
     * @param array $json 票据json对象
     * @return string 按标准格式生成的用于签名的字符串 失败时返回false
     * @throws Exception
     */
    private function genSignContent(array $json)
    {
        static $members = [
            'TLS.appid_at_3rd',
            'TLS.account_type',
            'TLS.identifier',
            'TLS.sdk_appid',
            'TLS.time',
            'TLS.expire_after',
        ];
        $content = '';
        foreach ($members as $member) {
            if (!isset($json[$member])) {
                throw new Exception('json need ' . $member);
            }
            $content .= "{$member}:{$json[$member]}\n";
        }

        return $content;
    }

    /**
     * @return string
     */
    protected function cacheKey(): string
    {
        return 'TencentIm.access_token.' . md5(json_encode($this->credentials()));
    }
}
