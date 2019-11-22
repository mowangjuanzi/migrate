<?php

use Illuminate\Config\Repository;
use Illuminate\Console\OutputStyle;
use Illuminate\Container\Container;
use Illuminate\Database\Connectors\ConnectionFactory;
use Illuminate\Database\Console\Migrations\TableGuesser;
use Illuminate\Database\DatabaseManager;
use Illuminate\Database\Migrations\DatabaseMigrationRepository;
use Illuminate\Database\Migrations\MigrationCreator;
use Illuminate\Database\Migrations\Migrator;
use Illuminate\Events\Dispatcher;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\Facade;
use Illuminate\Support\Str;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Output\ConsoleOutput;

define("ROOT_PATH", realpath(__DIR__));

require ROOT_PATH . "/vendor/autoload.php";

if (!isset($argv[1])) {
    $argv[1] = null;
}

$container = new Container();

$config = new Repository();

$config->set("database", require 'config/database.php');

$container->instance("config", $config);

$file = new Filesystem();

$container->singleton("db", function ($container) {
    $db = new DatabaseManager($container, new ConnectionFactory($container));
    $db->connection("mysql");
    return $db;
});

Facade::setFacadeApplication($container);

/**
 * 执行迁移命令如果出现 SQLSTATE[42000]: Syntax error or access violation: 1071 Specified key was too long; max key length is 767 bytes 错误则开启此行代码即可解决问题
 */
//Builder::defaultStringLength(191);

Container::setInstance($container);

$repository = new DatabaseMigrationRepository($container["db"], "migrations");

$event = new Dispatcher($container);

$migrator = new Migrator($repository, $container["db"], $file, $event);

$output = new OutputStyle(new ArgvInput(), new ConsoleOutput());

if ($argv[1] == "create") {
    $creator = new MigrationCreator($file);
    $name = Str::snake($argv[2]);

    [$table, $create] = TableGuesser::guess($name);

    try {
        $file_path = $creator->create($name, ROOT_PATH . "/migrations", $table, $create);
        $file_path = pathinfo($file_path, PATHINFO_FILENAME);

        $output->success("Created Migration: {$file_path}");
    } catch (\InvalidArgumentException $exception) {
        $output->error($exception->getMessage());
    }
} elseif ($argv[1] == "up") {
    if (!$migrator->repositoryExists()) { // migrate:up
        $repository->createRepository();
    }

    $migrator->setOutput($output)->run(ROOT_PATH . "/migrations", [
        "pretend" => false,
        "step" => false
    ]);
} elseif ($argv[1] == "down") {
    if (!$migrator->repositoryExists()) { // migrate:down
        $repository->createRepository();
    }

    $migrator->setOutput($output)->rollback(ROOT_PATH . "/migrations", [
        "pretend" => false,
        "step" => 0
    ]);
}else {
    $output->text('操作方法：
php bin/migrate.php create {xxx} 创建迁移，命名规则为Laravel
php bin/migrate.php up 执行迁移
php bin/migrate.php down 回滚迁移'
);
}