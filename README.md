# migrate
PHP版的迁移项目

## 执行前

```bash
cp .env.example .env
```

## 执行

```bash
$ php console.php 
migrate 0.0.2

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
 make
  make:migration    Create a new migration file
 migrate
  migrate:rollback  Rollback the last database migration
```