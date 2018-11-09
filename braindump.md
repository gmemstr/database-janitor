database janitor
---

## usage

Fetching would be done with a custom PHP library that will use a config
or env variables (cli > env > config file), laid out something like this.

```json
{
  "config": {
    # By default, sanitize users with defaults
    "sanitize_users": true,
    # Whether or not to trim data
    "trim": false
  }
  "tables": {
    # Table to sanitize
    "commerce_order": [
      # Specific columns to sanitize
      "order_number"
    ],
    "commerce_moneris": [
      "payload"
    ]
  }
}
```

env variables

```
DB_JANITOR_SANITIZE_USERS=true
DB_JANITOR_TRIM=false
DB_JANITOR_HOST=[host]
DB_JANITOR_USER=[user]
DB_JANITOR_PASSWORD=[password]
DB_JANITOR_DATABASE=[database]
```

cli usage

```bash
db-janitor --host=[host] --user=[user] --password=[password] --database=[database]
```

Syntax would actually be pretty similar to stock `mysqldump` command,
with some additions to account for sanitation and trim features. Behaviour
would likely be pretty similar as well, so piping to file or other server
would be required.

## backend design

core library is built on top of [mysqldump-php](https://github.com/ifsnop/mysqldump-php)
and handle configuration and actual execution of commands & queries. besides
mysqldump-php & php7+, should be completely standalone, preferably packaged as a
.phar for CLI use.
