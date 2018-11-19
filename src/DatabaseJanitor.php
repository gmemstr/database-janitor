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
  public function dump($host = FALSE, $output = FALSE) {
    if (!$output) {
      $output = getcwd() . '/output/' . $this->SqlHost . '_' . $this->SqlDatabase . '.sql.gz';
    }

    if ($host) {
      $this->SqlDatabase     = $host->database;
      $this->SqlUser         = $host->user;
      $this->SqlHost         = $host->host;
      $this->SqlPassword     = $host->password;
    }

    $dumpSettings = [
      'add-locks' => FALSE,
      'compress' => 'Gzip',
      'exclude-tables' => $this->dumpOptions['excluded_tables'] ?? [],
    ];
    try {
      $dump = new IMysqldump\Mysqldump('mysql:host=' . $this->SqlHost . ';dbname=' . $this->SqlDatabase, $this->SqlUser, $this->SqlPassword, $dumpSettings);
      $dump->setTransformColumnValueHook(function ($table_name, $col_name, $col_value) {
        return $this->sanitize($table_name, $col_name, $col_value, $this->dumpOptions);
      });
      $dump->start($output);
    }
    catch (\Exception $e) {
      echo 'mysqldump - php error: ' . $e->getMessage();
      return FALSE;
    }
    return $output;
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

  /**
   * Removes every other row from specified table.
   *
   * @param array $temp_sql_details
   *   Array of temporary SQL database details.
   *
   * @return array|bool
   *   FALSE if something goes wrong, otherwise array of removed items.
   */
  public function trim($temp_sql_details) {
    try {
      $connection = new \PDO('mysql:host=' . $temp_sql_details->host . ';dbname=' . $temp_sql_details->database, $temp_sql_details->user, $temp_sql_details->password);
    }
    catch (\PDOException $e) {
      echo $e;
      return FALSE;
    }
    $ignore = [];
    foreach ($this->dumpOptions['trim_tables'] as $table) {
      $ignore[] = $table;
      $connection->exec("SELECT " . $table . " INTO janitor_" . $table);
      $primary_key = $connection->query("SHOW KEYS FROM janitor_" . $table . " WHERE Key_name = 'PRIMARY'")->fetch()['Column_name'];
      if ($primary_key) {
        $all = $connection->query("SELECT " . $primary_key . " FROM janitor_" . $table)->fetchAll();
        foreach ($all as $key => $row) {
          // Delete every other row.
          if ($key % 4 == 0) {
            continue;
          }
          $connection->exec("DELETE FROM janitor_" . $table . " WHERE " . $primary_key . "=" . $row[$primary_key]);
        }
      }
    }
    return $ignore;
  }

}
