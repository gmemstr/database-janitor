Database Janitor
---

Highly-configurable database dumper

Be sure to replace the contents of `vendor/ifsnop/mysqldump-php/src/Ifsnop/Mysqldump/Mysqldump.php` with the latest
version from [the github](https://github.com/ifsnop/mysqldump-php/blob/master/src/Ifsnop/Mysqldump/Mysqldump.php),
as the composer version seems to be older and does't support hooks.

## Features

 - First-party support for Drupal databases
 - Configuration-first
 - PHP with minimal dependencies

## Usage

This application can either be used as a CLI app or a library that other applications can build on top of, e.g a drush
command or another custom application.

### Configuration

Configuration can be done in three ways, and it's recommended you use all three. First, values are loaded from the
command-line arguments. The CLI command then looks for specific environment variables, finally falling back to the
`janitor.json` file.

```json
{
  "config": { <-- General configuration
    "sanitize_users": true, <-- Provides sane sanitation for Drupal user tables.
    "trim": false <-- Whether or not we should attempt to trim down the dump results (tbd).
  },
  "tables": { <-- Specific tables/columns to sanitize.
    "commerce_order": [ <-- Table
      "order_number" <-- Column
    ]
  }
}
```

```bash
export DB_JANITOR_HOST='localhost'
export DB_JANITOR_USER='[sql user]'
export DB_JANITOR_PASSWORD='[sql password]'
export DB_JANITOR_DATABASE='[sql database]'
```

Remember, Database Janitor prefers arguments > env variables > json configuration.

### CLI

First you'll want to copy `janitor.example.json` to `janitor.json`, which will serve as the primary configuration file.
You can technically define all your configuration within, but it's strongly encouraged you move more sensitive values
(passwords, usersnames) to either the command line args or ENV variables.

```bash
composer install
php src/gsimmer/database-janitor/DatabaseJanitorCli.php --host=localhost --username=[sql username] --password=[sql password] --database=[sql database]
```
