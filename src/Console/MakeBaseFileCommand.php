<?php

namespace HaloService\Console;

use Illuminate\Console\Command;
use Illuminate\Console\GeneratorCommand;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class MakeBaseFileCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'make:base_file {name : Class (singular) for example User} {version?} {model?}';
    /**
     * The filesystem instance.
     *
     * @var \Illuminate\Filesystem\Filesystem
     */
    protected $files;

    /**
     * Create a new controller creator command instance.
     *
     * @param  \Illuminate\Filesystem\Filesystem  $files
     * @return void
     */
    public function __construct(Filesystem $files)
    {
        parent::__construct();

        $this->files = $files;
    }

    protected $type = 'BaseFile';
    protected $version = 'V1';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '创建基础文件';

    public function handle()
    {
        $name       = $this->argument('name');
        $version    = $this->argument('version') ?: 'V1';
        $model      = $this->argument('model') ?? 1;
        $file_name  = "$version/{$name}";
        if ($this->alreadyExists("/Http/Controllers/{$file_name}Controller")) {
            $this->error($name . ' already exists!');

            return;
        }

        $model && $this->model($name);
        $this->service($name, $version);
        $this->controller($name, $version);

        $this->info($name . ' created successfully.');
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
            [
                '{{modelName}}',
            ],
            [
                $name,
            ],
            $this->getStub('Model')
        );
        $path          = app_path("Model/{$name}.php");
        $this->makeDirectory($path);
        file_put_contents($path, $modelTemplate);
    }

    protected function service($name, $version)
    {
        $modelTemplate = str_replace(
            [
                '{{versionName}}',
                '{{modelName}}',
                '{{serviceName}}',
            ],
            [
                $version ? "\\" . $version : '',
                $name,
                $name,
            ],
            $this->getStub('Service')
        );
        if ($version) {
            $path = app_path("Service/{$version}/{$name}Service.php");
        } else {
            $path = app_path("Service/{$name}Service.php");
        }
        $this->makeDirectory($path);
        file_put_contents($path, $modelTemplate);
    }

    protected function controller($name, $version)
    {
        $controllerTemplate = str_replace(
            [
                '{{versionName}}',
                '{{serviceName}}',
                '{{controllerName}}',
            ],
            [
                $version ? "\\" . $version : '',
                $name,
                $name,
            ],
            $this->getStub('Controller')
        );

        if ($version) {
            $path = app_path("/Http/Controllers/{$version}/{$name}Controller.php");
        } else {
            $path = app_path("/Http/Controllers/{$name}Controller.php");
        }
        $this->makeDirectory($path);

        file_put_contents($path, $controllerTemplate);
    }

    /**
     * Build the directory for the class if necessary.
     *
     * @param  string  $path
     * @return string
     */
    protected function makeDirectory($path)
    {
        if (!$this->files->isDirectory(dirname($path))) {
            $this->files->makeDirectory(dirname($path), 0777, true, true);
        }

        return $path;
    }

    /**
     * Determine if the class already exists.
     *
     * @param  string  $rawName
     * @return bool
     */
    protected function alreadyExists($rawName)
    {

        return $this->files->exists($this->getPath($this->qualifyClass($rawName)));
    }

    /**
     * Get the destination class path.
     *
     * @param  string  $name
     * @return string
     */
    protected function getPath($name)
    {
        $name = Str::replaceFirst($this->rootNamespace(), '', $name);

        return $this->laravel['path'] . '/' . str_replace('\\', '/', $name) . '.php';
    }

    /**
     * Get the root namespace for the class.
     *
     * @return string
     */
    protected function rootNamespace()
    {
        return $this->laravel->getNamespace();
    }

    /**
     * Parse the class name and format according to the root namespace.
     *
     * @param  string  $name
     * @return string
     */
    protected function qualifyClass($name)
    {
        $name = ltrim($name, '\\/');

        $rootNamespace = $this->rootNamespace();

        if (Str::startsWith($name, $rootNamespace)) {
            return $name;
        }

        $name = str_replace('/', '\\', $name);

        return $this->qualifyClass(
            $this->getDefaultNamespace(trim($rootNamespace, '\\')) . '\\' . $name
        );
    }

    /**
     * Get the default namespace for the class.
     *
     * @param  string  $rootNamespace
     * @return string
     */
    protected function getDefaultNamespace($rootNamespace)
    {
        return $rootNamespace;
    }
}
