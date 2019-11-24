# migrate
PHP版的迁移项目

## 执行前

```bash
composer create-project wowangjuanzi/migrate
```

## 执行

```bash
$ php artisan
migrate 0.0.3

Usage:
  command [options] [arguments]

Options:
  -h, --help            Display this help message
  -q, --quiet           Do not output any message
  -V, --version         Display this application version
      --ansi            Force ANSI output
      --no-ansi         Disable ANSI output
  -n, --no-interaction  Do not ask any interactive question
      --env[=ENV]       The environment the command should run under
  -v|vv|vvv, --verbose  Increase the verbosity of messages: 1 for normal output, 2 for more verbose output and 3 for debug

Available commands:
  help              Displays help for a command
  list              Lists commands
  migrate           Run the database migrations
 db
  db:wipe           Drop all tables, views, and types
 make
  make:migration    Create a new migration file
 migrate
  migrate:fresh     Drop all tables and re-run all migrations
  migrate:install   Create the migration repository
  migrate:refresh   Reset and re-run all migrations
  migrate:reset     Rollback all database migrations
  migrate:rollback  Rollback the last database migration
```

## 文档

具体文档可参考如下网址：

- [官网 - 数据库迁移](https://laravel.com/docs/6.x/migrations)
- [LearnKu - 数据库迁移](https://learnku.com/docs/laravel/6.x/migrations/5173)
- [学院君 - 数据库迁移](https://xueyuanjun.com/post/19972.html)