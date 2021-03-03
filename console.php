<?php

use Dotenv\Dotenv;
use Illuminate\Config\Repository;
use Illuminate\Console\Application;
use Illuminate\Container\Container;
use Illuminate\Contracts\Container\Container as IlluminateContainer;
use Illuminate\Contracts\Events\Dispatcher as IlluminateDispatcher;
use Illuminate\Database\Connectors\ConnectionFactory;
use Illuminate\Database\Console\Migrations\FreshCommand;
use Illuminate\Database\Console\Migrations\InstallCommand;
use Illuminate\Database\Console\Migrations\MigrateCommand;
use Illuminate\Database\Console\Migrations\MigrateMakeCommand;
use Illuminate\Database\Console\Migrations\RefreshCommand;
use Illuminate\Database\Console\Migrations\ResetCommand;
use Illuminate\Database\Console\Migrations\RollbackCommand;
use Illuminate\Database\Console\WipeCommand;
use Illuminate\Database\DatabaseManager;
use Illuminate\Database\Migrations\DatabaseMigrationRepository;
use Illuminate\Database\Migrations\MigrationCreator;
use Illuminate\Database\Migrations\Migrator;
use Illuminate\Events\Dispatcher;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Composer;
use Illuminate\Support\Facades\Facade;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Output\ConsoleOutput;

require './vendor/autoload.php';

$app = new class extends Container {

    /**
     * The base path for the Laravel installation.
     *
     * @var string
     */
    protected $basePath;

    /**
     * The custom database path defined by the developer.
     *
     * @var string
     */
    protected $databasePath;

    /**
     * Create a new Illuminate application instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->setBasePath(__DIR__);
    }

    /**
     * Set the base path for the application.
     *
     * @param  string  $basePath
     * @return $this
     */
    public function setBasePath($basePath)
    {
        $this->basePath = rtrim($basePath, '\/');

        $this->bindPathsInContainer();

        return $this;
    }

    protected function bindPathsInContainer()
    {
        // $this->instance('path', $this->path());
        $this->instance('path.base', $this->basePath());
        // $this->instance('path.lang', $this->langPath());
        $this->instance('path.config', $this->configPath());
        // $this->instance('path.public', $this->publicPath());
        // $this->instance('path.storage', $this->storagePath());
        $this->instance('path.database', $this->databasePath());
        // $this->instance('path.resources', $this->resourcePath());
        // $this->instance('path.bootstrap', $this->bootstrapPath());
    }

    /**
     * Get the path to the database directory.
     *
     * @param  string  $path Optionally, a path to append to the database path
     * @return string
     */
    public function databasePath($path = '')
    {
        return ($this->databasePath ?: $this->basePath.DIRECTORY_SEPARATOR.'database').($path ? DIRECTORY_SEPARATOR.$path : $path);
    }

    /**
     * Get the path to the application configuration files.
     *
     * @param  string  $path Optionally, a path to append to the config path
     * @return string
     */
    public function configPath($path = '')
    {
        return $this->basePath.DIRECTORY_SEPARATOR.'config'.($path ? DIRECTORY_SEPARATOR.$path : $path);
    }

    /**
     * Get the base path of the Laravel installation.
     *
     * @param  string  $path Optionally, a path to append to the base path
     * @return string
     */
    public function basePath($path = '')
    {
        return $this->basePath.($path ? DIRECTORY_SEPARATOR.$path : $path);
    }

    /**
     * Get or check the current application environment.
     *
     * @param  string|array  $environments
     * @return string|bool
     */
    public function environment(...$environments)
    {
        if (count($environments) > 0) {
            $patterns = is_array($environments[0]) ? $environments[0] : $environments;

            return Str::is($patterns, $this['env']);
        }

        return $this['env'];
    }
};

Container::setInstance($app);

Dotenv::create($app['path.base'])->safeLoad();

foreach([
    'app' => [IlluminateContainer::class],
    'events' => [IlluminateDispatcher::class],  
] as $key => $aliases) {
    foreach($aliases as $alias) {
        $app->alias($key, $alias);
    }
}

$app->singleton("app", function($app) {
    return $app;
});

$app->singleton("events", function($app) {
    return new Dispatcher($app);
});

$app->singleton("db.factory", function($app) {
    return new ConnectionFactory($app);
});

$app->singleton("db", function($app) {
    return new DatabaseManager($app, $app['db.factory']);
});

$app->instance('config', $config = new Repository([
    'app' => require config_path('app.php'),
    "database" => require config_path("database.php"),
]));

$app->singleton('env', function($app) {
    return $app['config']->get('app.env');
});

$app->singleton('files', function () {
    return new Filesystem;
});

$app->singleton("migration.repository", function($app) {
    return new DatabaseMigrationRepository($app['db'], $app['config']['database.migrations']);
});

$app->singleton("migrator", function($app) {
    return new Migrator($app["migration.repository"], $app['db'], $app['files'], $app['events']);
});

$app->singleton('command.migrate.install', function ($app) {
    return new InstallCommand($app['migration.repository']);
});

$app->singleton("command.migrate", function($app) {
    return new MigrateCommand($app['migrator']);
});

$app->singleton('migration.creator', function ($app) {
    return new MigrationCreator($app['files']);
});

$app->singleton('composer', function ($app) {
    return new Composer($app['files'], $app->basePath());
});

$app->singleton('command.migrate.make', function ($app) {
    $creator = $app['migration.creator'];

    $composer = $app['composer'];

    return new MigrateMakeCommand($creator, $composer);
});

$app->singleton('command.migrate.rollback', function ($app) {
    return new RollbackCommand($app['migrator']);
});

$app->singleton('command.migrate.reset', function ($app) {
    return new ResetCommand($app['migrator']);
});

$app->singleton('command.migrate.refresh', function () {
    return new RefreshCommand;
});

$app->singleton('command.migrate.fresh', function () {
    return new FreshCommand;
});

$app->singleton('command.db.wipe', function () {
    return new WipeCommand;
});

$app->singleton(Illuminate\Contracts\Console\Kernel::class, function($app) {
        $application = new Application($app, app('events'), "0.0.4");
        $application->setName(config("app.name"));
        return $application;
    }
);

Facade::setFacadeApplication($app);

/**
 * 执行迁移命令如果出现 SQLSTATE[42000]: Syntax error or access violation: 1071 Specified key was too long; max key length is 767 bytes 错误则开启此行代码即可解决问题
 */
// Schema::defaultStringLength(191);

$console = $app->make(Illuminate\Contracts\Console\Kernel::class);

$console->resolveCommands([
    "command.migrate.install",
    "command.migrate",
    "command.migrate.make",
    "command.migrate.rollback",
    "command.migrate.reset",
    "command.migrate.refresh",
    "command.migrate.fresh",
    "command.db.wipe",
]);

$console->run(new ArgvInput(), new ConsoleOutput());