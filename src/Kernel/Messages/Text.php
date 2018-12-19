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
 * Class Text.
 *
 * @author guansq <94600115@qq.com>
 */
class Text extends Message
{
    protected $type = 'text';

    public function __construct(string $content)
    {
        parent::__construct(compact('content'));
    }
}
