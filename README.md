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

|JSON Key|ENV Key|CLI Key|What it does|Default value|
|---|------------|-------------|---|---|
|`sanitize_users`|none|none|Tells janitor whether or not to run drupal-specific sanitation on dump.|`true`|
|`trim`|none|none|Whether or not we should attempt to cut down results in dump.|`false`|
|`tables`|none|none|Specific tables and their columns to sanitize.|`{}`|
|**not recommended** `host` |`DB_JANITOR_HOST`|`--host`|Specifies database host to connect to.| |
|**not recommended** `password` |`DB_JANITOR_PASSWORD`|`--password`|Specifies database user password.| |
|**not recommended** `database` |`DB_JANITOR_DATABASE`|`--database`|Specific database to dump.| |
|**not recommended** `user` |`DB_JANITOR_USER`|`--username`|Database user.| |

```json
{
  "config": {
    "sanitize_users": true,
    "trim": false
  },
  "tables": {
    "commerce_order": [
      "order_number"
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
