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

        $token = $this->generate_user_sig($this->identifier);// 重新生成usersig

        $this->setToken($token, 86400);

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
            'identifier' => $this->app['config']->get('identifier'),
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
        $this->app['config']['identifier'] = $identifier;
        if ($usrsig = $this->app['cache']->get($this->cacheKey())) {
            return $usrsig;
        }
        $credentials        = $this->credentials();
        $protected_key_path = $protected_key_path ?: $credentials['key_path'];
        $tool_path          = $tool_path ?: base_path('vendor/guansq/tencent-im/src/Command/linux-signature64');
        # 这里需要写绝对路径，开发者根据自己的路径进行调整
        $command = escapeshellarg($tool_path) . ' ' . escapeshellarg($protected_key_path) . ' ' . escapeshellarg($credentials['app_id']) . ' ' . escapeshellarg($identifier);
        $ret     = exec($command, $out, $status);
        if ($status == -1) {
            return null;
        }
        $usrsig = $this->usersig = $out[0];
        $this->setToken($this->usersig, 86400);

        return $usrsig;
    }

    /**
     * @return string
     */
    protected function cacheKey(): string
    {
        $keys = $this->credentials();

        return 'TencentIm.usersig.' . md5(json_encode($keys));
    }
}
