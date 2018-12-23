<?php

/*
 * This file is part of the halobear/tencent-im.
 *
 * (c) guansq <94600115@qq.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace TencentIm;

use Pimple\Container;

/**
 * Class Application.
 *
 * @author guansq <94600115@qq.com>
 *
 * @property \TencentIm\Auth\Client         $auth
 * @property \TencentIm\User\Client         $user
 * @property \TencentIm\Message\Client      $message
 * @property \TencentIm\Relation\Client     $relation
 * @property \TencentIm\Group\Client        $group
 * @property \TencentIm\Kernel\Credential   $credential
 * @property \TencentIm\Message\AsyncClient $async_message
 */
class Application extends Container
{
    /**
     * @var array
     */
    protected $providers = [
        Auth\ServiceProvider::class,
        User\ServiceProvider::class,
        Kernel\ServiceProvider::class,
        Message\ServiceProvider::class,
        Relation\ServiceProvider::class,
        Group\ServiceProvider::class,
    ];

    /**
     * Application constructor.
     *
     * @param array $config
     */
    public function __construct(array $config)
    {
        parent::__construct();

        $this['config'] = function () use ($config) {
            return new Kernel\Config($config);
        };

        $this->registerProviders();
    }

    /**
     * Register providers.
     */
    protected function registerProviders()
    {
        foreach ($this->providers as $provider) {
            $this->register(new $provider());
        }
    }

    /**
     * @param $id
     *
     * @return mixed
     */
    public function __get($id)
    {
        return $this->offsetGet($id);
    }
}
