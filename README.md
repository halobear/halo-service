<h1 align="center"> halo-service </h1>

<p align="center"> .</p>


## Installing

```shell
$ composer require halobear/halo-service
```

## Usage

```shell
第一步
修改app/Console/Kernel.php文件

protected $commands = [
    MakeBaseFileCommand::class,
];
第二步
执行自定义创建控制器 模型 服务命令
php artisan make:base_file GoodsTest V1
```

## Contributing

You can contribute in one of three ways:

1. File bug reports using the [issue tracker](https://github.com/halo/service-demo/issues).
2. Answer questions or fix bugs on the [issue tracker](https://github.com/halo/service-demo/issues).
3. Contribute new features or update the wiki.

_The code contribution process is not very formal. You just need to make sure that you follow the PSR-0, PSR-1, and PSR-2 coding guidelines. Any new code contributions must be accompanied by unit tests where applicable._

## License

MIT