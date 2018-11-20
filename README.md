Database Janitor
---

Highly-configurable database dumper

## Features

 - First-party support for Drupal databases
 - Configuration-first
 - PHP with minimal dependencies

## Usage

This application can either be used as a CLI app or a library that other applications can build on top of, e.g a drush
command or another custom application.

### Testing

There is a sample Lando config you can use to spin up two separate mysql databases for testing, as well as a sample SQL
file containing random data.

```bash
lando start
lando db-import sampledata.sql --host real_database; lando db-import sampledata.sql --host trim_database
```

### Configuration

```yaml
# .janitor.yml
sanitize_tables:
# List of tables and their columns you want sanitized.
  user:
    - mail
trim_tables:
# List of tables to be trimmed (every 4th row kept)
excluded_tables:
# Tables to NOT dump
scrub_tables:
# Tables to dump sans content.
```

### CLI

First you'll want to copy `.janitor.example.yml` to `.janitor.yml`. You can then go in and edit exactly which tables and
columns you want sanitized/ignored/cleared.

Then install dependencies with `composer install`.

#### Dumping

This will prompt you for the database password, then produce a gzip'd .sql file in the `output/` directory.

By default Janitor output to STDOUT for piping.

```bash
./database-janitor --host=localhost:8787 --username=real real | gzip -c > output/real_test.sql.gz
```

#### Trimming

Trimming is currently being reworked, but here is the command that will be executable soon.

```bash
./database-janitor --host=localhost:8787 --username=real --trim=true real | gzip -c > output/real_test.sql.gz
```
