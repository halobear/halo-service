<?php
/**
 * Created by PhpStorm.
 * User: halobear
 * Date: 2018/12/19
 * Time: 3:31 PM
 */

namespace Halobear\TencentIm;

class ServiceProviderBAK extends \Illuminate\Support\ServiceProvider
{
    protected $defer = true;

    public function register()
    {
        $this->app->singleton(Wearther::class, function () {
            return new Wearther(config('services.weather.key'));
        });

        $this->app->alias(Wearther::class, 'weather');
    }

    public function provides()
    {
        return [Wearther::class, 'weather'];
    }
}