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

use Pimple\Container;
use GuzzleHttp\Client as GuzzleHttp;
use Pimple\ServiceProviderInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Cache\Simple\FilesystemCache;

/**
 * Class ServiceProvider.
 *
 * @author guansq <94600115@qq.com>
 */
class ServiceProvider implements ServiceProviderInterface
{
    public function register(Container $app)
    {
        $app['request'] = function () {
            return Request::createFromGlobals();
        };

        $app['http_client'] = function () {
            return new GuzzleHttp([
                'base_uri' => 'https://console.tim.qq.com/',
                'headers' => [
                    'Content-Type' => 'application/json',
                ],
                'timeout' => 5.0,
            ]);
        };

        $app['credential'] = function ($app) {
            return new Credential($app);
        };

        $app['cache'] = function () {
            return new FilesystemCache();
        };
    }
}
