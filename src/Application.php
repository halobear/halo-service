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
 * @property \TencentIm\Chat\Client         $chat
 * @property \TencentIm\Role\Client         $role
 * @property \TencentIm\User\Client         $user
 * @property \TencentIm\Media\Client        $media
 * @property \TencentIm\Jssdk\Client        $jssdk
 * @property \TencentIm\Report\Client       $report
 * @property \TencentIm\Checkin\Client      $checkin
 * @property \TencentIm\Message\Client      $message
 * @property \TencentIm\Process\Client      $process
 * @property \TencentIm\Microapp\Client     $microapp
 * @property \TencentIm\Attendance\Client   $attendance
 * @property \TencentIm\Kernel\Credential   $credential
 * @property \TencentIm\Department\Client   $department
 * @property \TencentIm\Message\AsyncClient $async_message
 */
class Application extends Container
{
    /**
     * @var array
     */
    protected $providers = [
        // Auth\ServiceProvider::class,
        // Chat\ServiceProvider::class,
        // Role\ServiceProvider::class,
        // User\ServiceProvider::class,
        // Jssdk\ServiceProvider::class,
        // Media\ServiceProvider::class,
        Kernel\ServiceProvider::class,
        // Report\ServiceProvider::class,
        // Checkin\ServiceProvider::class,
        // Message\ServiceProvider::class,
        // Process\ServiceProvider::class,
        // Microapp\ServiceProvider::class,
        // Attendance\ServiceProvider::class,
        // Department\ServiceProvider::class,
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
