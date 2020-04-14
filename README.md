<p align="right">Initial development funded by Acro Media Inc.</p>

Database Janitor
---

Highly-configurable database dumper

## Features

 - Drupal configuration support
 - Configuration-first
 - PHP with minimal dependencies

## Usage

This application can either be used as a CLI app or a library that other applications can build on top of, e.g a drush
command or another custom application.

## Command Help

```bash
Description:
  Cleans up databases between servers or dev enviornments

Usage:
  database-janitor [options] [--] <database>

Arguments:
  database                   Database to dump

Options:
      --host[=HOST]          Database host, defaults to localhost
  -u, --username[=USERNAME]  Database username
  -p, --password[=PASSWORD]  Database password
  -t, --trim                 Whether or not to exclude data from dump (trimming)
  -d, --drupal=DRUPAL        Path to a Drupal settings file (ignores host, username and password flags)
  -h, --help                 Display this help message
  -q, --quiet                Do not output any message
  -V, --version              Display this application version
      --ansi                 Force ANSI output
      --no-ansi              Disable ANSI output
  -n, --no-interaction       Do not ask any interactive question
  -v|vv|vvv, --verbose       Increase the verbosity of messages: 1 for normal output, 2 for more verbose output and 3 for debug
```

### Configuration

```yaml
sanitize_tables:
  # List of tables and their columns you want sanitized.
  user:
  - mail
sanitize_tables_default:
  node:
    uid: 1
  node_revision:
    uid: 1
trim_tables:
# List of tables to be trimmed (every 4th row kept)
  - trim1
excluded_tables:
# Tables to NOT dump
  - exclude1
scrub_tables:
# Tables to dump sans content.
  - scrub1
keep_data:
# Keep data in these tables by key
  table_name:
    col: col_name
    # Only row with col value of 1
    rows:
      - 1
  table_name_2:
  # Every other row, using mod
    col: other_col_name
    rows: 2

  table_name_3:
  # Every row with a value of 1, 3 or 8.
    col: third_col_name
    rows: 1, 3, 8
```

### CLI

First you'll want to copy `.janitor.example.yml` to `.janitor.yml`. You can then go in and edit exactly which tables and
columns you want sanitized/ignored/cleared.

If not using the .phar, install dependencies with `composer install`.

#### Dumping

This will prompt you for the database password, then produce a gzip'd .sql file in the `output/` directory.

```bash
./janitor.phar --host=localhost:8787 --username=real real | gzip -c > output/real_test.sql.gz
```

#### Trimming

Trimming allows much smaller database dumps by reducing the data exported through the use of keeping data, scrubbing tables, and so on. 

```bash
./janitor.phar --host=localhost:8787 --username=real --trim real | gzip -c > output/real_test.sql.gz
```
