<?php

namespace HaloService\Console;

use Illuminate\Console\Command;
use Illuminate\Console\GeneratorCommand;
use Illuminate\Support\Facades\File;

class MakeBaseFileCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'make:base_file {name : Class (singular) for example User}';

    protected $type = 'BaseOperation';

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
        $name = $this->argument('name');

        $this->controller($name);
        $this->model($name);

        \File::append(base_path('routes/api.php'), 'Route::resource(\'' . strtolower($name) . "', '{$name}Controller');");

        $this->info($this->type . ' created successfully.');
    }

    /**
     * 获取传入的类名
     *
     * @return string
     */
    protected function getNameInput()
    {
        return trim($this->argument('name'));
    }

    /**
     * @inheritDoc
     */
    protected function getStub($type)
    {
        return file_get_contents(__DIR__ . "/stubs/$type.stub");
    }

    protected function model($name)
    {
        $modelTemplate = str_replace(
            ['{{modelName}}'],
            [$name],
            $this->getStub('Model')
        );

        file_put_contents(app_path("/{$name}.php"), $modelTemplate);
    }

    protected function controller($name)
    {
        $controllerTemplate = str_replace(
            [
                '{{modelName}}',
                '{{modelNamePluralLowerCase}}',
                '{{modelNameSingularLowerCase}}',
            ],
            [
                $name,
                strtolower($name),
                strtolower($name),
            ],
            $this->getStub('Controller')
        );

        file_put_contents(app_path("/Http/Controllers/{$name}Controller.php"), $controllerTemplate);
    }
}
