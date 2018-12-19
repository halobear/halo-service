<?php

/*
 * This file is part of the halobear/tencent-im
 *
 * (c) guansq <94600115@qq.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace TencentIm\Kernel\Messages;

/**
 * Class Message.
 *
 * @author guansq <94600115@qq.com>
 */
abstract class Message
{
    /**
     * @var string
     */
    protected $type;

    /**
     * @var array
     */
    protected $attributes = [];

    /**
     * Message constructor.
     *
     * @param array $attributes
     */
    public function __construct(array $attributes)
    {
        $this->attributes = $attributes;
    }

    /**
     * @param int|string|\TencentIm\Kernel\Messages\Message $message
     *
     * @return \TencentIm\Kernel\Messages\Message
     */
    public static function parse($message): self
    {
        if (is_int($message) || is_string($message)) {
            $message = new Text($message);
        }

        return $message;
    }

    public function type()
    {
        return $this->type;
    }

    public function body()
    {
        return $this->attributes;
    }

    /**
     * @return array
     */
    public function transform(): array
    {
        return [
            'msgtype' => $this->type,
            $this->type => $this->attributes,
        ];
    }
}
