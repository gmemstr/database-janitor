<?php

/**
 * @file Contains basic interface for DatabaseJanitor library.
 */

namespace DatabaseJanitor;
require getcwd() . '/vendor/autoload.php';

use Ifsnop\Mysqldump as IMysqldump;

/**
 * Class DatabaseJanitor
 *
 * @package DatabaseJanitor
 */
class DatabaseJanitor {

  // Should these be a single array?
  private $SqlPassword;

  private $SqlHost;

  private $SqlUser;

  private $SqlDatabase;

  private $dumpOptions;


  /**
   * DatabaseJanitor constructor.
   */
  public function __construct($SqlDatabase, $SqlUser, $SqlHost, $SqlPassword, $dumpOptions) {
    $this->SqlDatabase     = $SqlDatabase;
    $this->SqlUser         = $SqlUser;
    $this->SqlHost         = $SqlHost;
    $this->SqlPassword     = $SqlPassword;
    $this->dumpOptions     = $dumpOptions;
  }

  /**
   * Basic dumping.
   *
   * @return bool|string
   *   FALSE if dump encountered an error, otherwise return location of dump.
   */
  public function dump() {

    $dumpSettings = [
      'add-locks' => FALSE,
      'compress' => IMysqldump\Mysqldump::GZIP,
      'exclude-tables' => $this->dumpOptions['excluded_tables'] ?? '',
    ];
    try {
      $dump = new IMysqldump\Mysqldump('mysql:host=' . $this->SqlHost . ';dbname=' . $this->SqlDatabase, $this->SqlUser, $this->SqlPassword);
      $dump->setTransformColumnValueHook(function ($table_name, $col_name, $col_value) {
        return $this->sanitize($table_name, $col_name, $col_value, $this->dumpOptions);
      });
      $dump->start(getcwd() . '/output/' . $this->SqlHost . '_' . $this->SqlDatabase . '.sql.gzip', $dumpSettings);
    }
    catch (\Exception $e) {
      echo 'mysqldump - php error: ' . $e->getMessage();
      return FALSE;
    }
    return getcwd() . '/output/' . $this->SqlHost . '_' . $this->SqlDatabase . '.sql.gzip';
  }

  /**
   * Replace values in specific table col with random value.
   *
   * @param $table_name
   *   The current table's name.
   * @param $col_name
   *   The current column name.
   * @param $col_value
   *   The current value in the column.
   * @param $targets
   *   Column names we want to sanitize.
   *
   * @return string
   *   New col value.
   */
  public function sanitize($table_name, $col_name, $col_value, $options) {
    foreach ($options['tables'] as $table => $val) {
      if ($table == $table_name) {
        foreach ($options['tables']->{$table} as $col) {
          if ($col == $col_name) {
            // Generate value based on the type of the actual value.
            // Helps avoid breakage with incorrect types in cols.
            switch(gettype($col_value)) {
              case "integer":
              case "double":
                return random_int(1000000, 9999999);
                break;
              case "string":
                return (string) random_int(1000000, 9999999) . '-janitor';
                break;

              default:
                return $col_value;
            }
          }
        }
      }
    }

    // Always sanitize users (unless otherwise set).
    if ($options['sanitize_users']) {
      if ($table_name == 'user' || $table_name == 'users_field_data') {
        switch ($col_name) {
          case 'pass':
            // Todo: Replace with default "password" as hash value.
            $col_value = "some_unique_value";
            break;

          case 'name':
            $col_value = substr($col_value, 0, 4) . '-janitor';
            break;

          case 'init':
          case 'mail':
            $col_value = substr($col_value, 0, 4) . '-janitor@email.com';
            break;
        }
      }
    }

    return $col_value;
  }

  public function trim() {

  }
}
