<?php

namespace DatabaseJanitor;

require getcwd() . '/vendor/autoload.php';

use Ifsnop\Mysqldump as IMysqldump;

/**
 * Class DatabaseJanitor.
 *
 * @package DatabaseJanitor
 */
class DatabaseJanitor {

  private $password;
  private $host;
  private $user;
  private $database;
  private $dumpOptions;

  /**
   * DatabaseJanitor constructor.
   */
  public function __construct($database, $user, $host, $password, $dumpOptions) {
    $this->database    = $database;
    $this->user        = $user;
    $this->host        = $host;
    $this->password    = $password;
    $this->dumpOptions = $dumpOptions;
  }

  /**
   * Basic dumping.
   *
   * @return bool|string
   *   FALSE if dump encountered an error, otherwise return location of dump.
   */
  public function dump($host = FALSE, $output = FALSE) {
    if (!$output) {
      $output = 'php://stdout';
    }

    if ($host) {
      $this->database = $host->database;
      $this->user     = $host->user;
      $this->host     = $host->host;
      $this->password = $host->password;
    }

    $dumpSettings = [
      'add-locks' => FALSE,
      'exclude-tables' => $this->dumpOptions['excluded_tables'] ?? [],
    ];
    try {
      $dump = new IMysqldump\Mysqldump('mysql:host=' . $this->host . ';dbname=' . $this->database, $this->user, $this->password, $dumpSettings);
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
   * @param string $table_name
   *   The current table's name.
   * @param string $col_name
   *   The current column name.
   * @param string $col_value
   *   The current value in the column.
   * @param array $options
   *   Full configuration of tables to sanitize.
   *
   * @return string
   *   New col value.
   */
  public function sanitize($table_name, $col_name, $col_value, array $options) {
    if (isset($options['sanitize_tables'])) {
      foreach ($options['sanitize_tables'] as $table => $val) {
        if ($table == $table_name) {
          foreach ($options['sanitize_tables'][$table] as $col) {
            if ($col == $col_name) {
              // Generate value based on the type of the actual value.
              // Helps avoid breakage with incorrect types in cols.
              switch (gettype($col_value)) {
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
    }

    return $col_value;
  }

  /**
   * Removes every other row from specified table.
   *
   * @return array|bool
   *   FALSE if something goes wrong, otherwise array of removed items.
   */
  public function trim() {
    try {
      $connection = new \PDO('mysql:host=' . $this->host . ';dbname=' . $this->database, $this->user, $this->password);
    }
    catch (\PDOException $e) {
      echo $e;
      return FALSE;
    }
    $ignore = [];
    foreach ($this->dumpOptions['trim_tables'] as $table) {
      // Rename table and copy is over.
      $connection->exec("ALTER TABLE " . $table . " RENAME TO original_" . $table);
      $ignore[] = 'original_' . $table;
      $connection->exec("CREATE TABLE " . $table . " SELECT * FROM original_" . $table);
      // This makes assumptions about the primary key, should be configurable.
      $primary_key = $connection->query("SHOW KEYS FROM original_" . $table . " WHERE Key_name = 'PRIMARY'")->fetch()['Column_name'];
      if ($primary_key) {
        $all = $connection->query("SELECT " . $primary_key . " FROM " . $table)->fetchAll();
        foreach ($all as $key => $row) {
          // Delete every other row.
          if ($key % 4 == 0) {
            continue;
          }
          $connection->exec("DELETE FROM " . $table . " WHERE " . $primary_key . "=" . $row[$primary_key]);
        }
      }
    }
    return $ignore;
  }

  /**
   * Completely scrub a table (aka truncate).
   *
   * @return array|bool
   *   Scrubbed tables with original_ appended for cleanup, false on error.
   */
  public function scrub() {
    try {
      $connection = new \PDO('mysql:host=' . $this->host . ';dbname=' . $this->database, $this->user, $this->password);
    }
    catch (\PDOException $e) {
      echo $e;
      return FALSE;
    }
    $ignore = [];
    foreach ($this->dumpOptions['scrub_tables'] as $table) {
      // Rename table and copy is over.
      $connection->exec("ALTER TABLE " . $table . " RENAME TO original_" . $table);
      $ignore[] = 'original_' . $table;
      $connection->exec("CREATE TABLE " . $table . " SELECT * FROM original_" . $table);
      $connection->exec("TRUNCATE " . $table);
    }
    return $ignore;
  }

  /**
   * Post-run to rename the original tables back.
   *
   * @param array $tables
   *   Tables to rename, in the form original_X.
   *
   * @return bool
   *   False if error occurred, true otherwise.
   */
  public function cleanup(array $tables) {
    try {
      $connection = new \PDO('mysql:host=' . $this->host . ';dbname=' . $this->database, $this->user, $this->password);
    }
    catch (\PDOException $e) {
      echo $e;
      return FALSE;
    }
    foreach ($tables as $table) {
      // Bit of a funky replace, but make sure we DO NOT alter the original
      // table name.
      $table = explode('_', $table);
      unset($table[0]);
      $table = implode('_', $table);

      $connection->exec("DROP TABLE " . $table);
      $connection->exec("ALTER TABLE original_" . $table . " RENAME TO " . $table);
    }
    return TRUE;
  }

}
