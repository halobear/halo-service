<?php

namespace HaloService\Console;

use Illuminate\Console\Command;
use Illuminate\Console\GeneratorCommand;

class MakeBaseFileCommand extends GeneratorCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'make:base_file';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '创建基础文件';

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {
        $this->info('111122222');
    }

    /**
     * @inheritDoc
     */
    protected function getStub()
    {
        // TODO: Implement getStub() method.
    }
}
