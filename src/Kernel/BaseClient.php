<?php

/*
 * This file is part of the halobear/tencent-im
 *
 * (c) guansq <94600115@qq.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace HaloService\Kernel;

use GuzzleHttp\HandlerStack;
use HaloService\Application;
use GuzzleHttp\RequestOptions;
use Psr\Http\Message\RequestInterface;

/**
 * Class BaseClient.
 *
 * @author guansq <94600115@qq.com>
 */
class BaseClient
{
    use MakesHttpRequests;

    /**
     * @var \HaloService\Application
     */
    protected $app;

    protected $HaloServiceHandlerStack;

    protected $taobaoHandlerStack;

    /**
     * @param  \HaloService\Application
     */
    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    /**
     * Make a get request.
     *
     * @param  string  $uri
     * @param  array  $query
     *
     * @return array|\GuzzleHttp\Psr7\Response
     */
    public function httpGet(string $uri, array $query = [])
    {
        return $this->requestHaloService('GET', $uri, [RequestOptions::QUERY => $query]);
    }

    /**
     * Make a post request.
     *
     * @param  string  $uri
     * @param  array  $json
     * @param  array  $query
     *
     * @return array|\GuzzleHttp\Psr7\Response
     */
    public function httpPostJson(string $uri, array $json = [], array $query = [])
    {
        return $this->requestHaloService(
            'POST',
            $uri,
            [
                RequestOptions::QUERY => $query,
                RequestOptions::JSON  => $json,
            ]
        );
    }

    /**
     * Make a post request.
     *
     * @param  string  $uri
     * @param  array  $json
     * @param  array  $query
     *
     * @return array|\GuzzleHttp\Psr7\Response
     */
    public function httpAsyncPostJson(string $uri, array $json = [], array $query = [])
    {
        return $this->requestAsyncHaloService(
            'POST',
            $uri,
            [
                RequestOptions::QUERY => $query,
                RequestOptions::JSON  => $json,
            ]
        );
    }

    /**
     * Make a get request.
     *
     * @param  string  $uri
     * @param  array  $json
     * @param  array  $query
     *
     * @return array|\GuzzleHttp\Psr7\Response
     */
    public function httpGetJson(string $uri, array $json = [], array $query = [])
    {
        return $this->requestHaloService(
            'GET',
            $uri,
            [
                RequestOptions::QUERY => $query,
                RequestOptions::JSON  => $json,
            ]
        );
    }

    /**
     * Upload files.
     *
     * @param  string  $uri
     * @param  array  $files
     * @param  array  $query
     *
     * @return array|\GuzzleHttp\Psr7\Response
     */
    public function httpUpload(string $uri, array $files, array $query = [])
    {
        $multipart = [];

        foreach ($files as $name => $path) {
            $multipart[] = [
                'name'     => $name,
                'contents' => fopen($path, 'r'),
            ];
        }

        return $this->requestHaloService(
            'POST',
            $uri,
            [
                RequestOptions::QUERY     => $query,
                RequestOptions::MULTIPART => $multipart,
            ]
        );
    }


    /**
     * @param       $method
     * @param       $uri
     * @param  array  $options
     *
     * @return array|\GuzzleHttp\Psr7\Response
     * @throws Exceptions\ClientError
     */
    protected function requestHaloService($method, $uri, array $options = [])
    {
        if (!$handler = $this->HaloServiceHandlerStack) {
            $handler = HandlerStack::create();

            $handler->push(
                function (callable $handler) {
                    return function (RequestInterface $request, array $options) use ($handler) {
                        return $handler(
                            $this->concat(
                                $request,
                                [
                                    'usersig'     => $this->app['credential']->token(),
                                    'identifier'  => $this->app['config']->get('identifier'),
                                    'sdkappid'    => $this->app['config']->get('app_id'),
                                    'random'      => str_random(32),
                                    'contenttype' => 'json',
                                ]
                            ),
                            $options
                        );
                    };
                }
            );

            $this->HaloServiceHandlerStack = $handler;
        }

        return $this->request($method, $uri, $options + compact('handler'));
    }

    /**
     * @param       $method
     * @param       $uri
     * @param  array  $options
     *
     * @return array|\GuzzleHttp\Psr7\Response
     */
    protected function requestAsyncHaloService($method, $uri, array $options = [])
    {
        if (!$handler = $this->HaloServiceHandlerStack) {
            $handler = HandlerStack::create();

            $handler->push(
                function (callable $handler) {
                    return function (RequestInterface $request, array $options) use ($handler) {
                        return $handler(
                            $this->concat(
                                $request,
                                [
                                    'usersig'     => $this->app['credential']->token(),
                                    'identifier'  => $this->app['config']->get('identifier'),
                                    'sdkappid'    => $this->app['config']->get('app_id'),
                                    'random'      => str_random(32),
                                    'contenttype' => 'json',
                                ]
                            ),
                            $options
                        );
                    };
                }
            );

            $this->HaloServiceHandlerStack = $handler;
        }

        return $this->requestAsync($method, $uri, $options + compact('handler'));
    }


    /**
     * @param  \Psr\Http\Message\RequestInterface  $request
     * @param  array  $query
     *
     * @return \Psr\Http\Message\RequestInterface
     */
    protected function concat(RequestInterface $request, array $query = []): RequestInterface
    {
        parse_str($request->getUri()->getQuery(), $parsed);
        $query = http_build_query(array_merge($query, $parsed));

        return $request->withUri($request->getUri()->withQuery($query));
    }
}
