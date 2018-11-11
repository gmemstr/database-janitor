Database Janitor
---

Highly-configurable database dumper

Be sure to replace the contents of `vendor/ifsnop/mysqldump-php/src/Ifsnop/Mysqldump/Mysqldump.php` with the latest
version from [the github](https://github.com/ifsnop/mysqldump-php/blob/master/src/Ifsnop/Mysqldump/Mysqldump.php),
as the composer version seems to be older and does't support hooks.

## Features

 - First-party support for Drupal databases

## Usage

This application can either be used as a CLI app or a library that other applications can build on top of, e.g a drush
command or another custom application.
