<?php

namespace DatabaseJanitor;

require __DIR__ . '/../vendor/autoload.php';

use Ifsnop\Mysqldump\Mysqldump;

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

  private $connection;

  /**
   * DatabaseJanitor constructor.
   */
  public function __construct($database, $user, $host, $password, $dumpOptions) {
    $this->database    = $database;
    $this->user        = $user;
    $this->host        = $host;
    $this->password    = $password;
    $this->dumpOptions = $dumpOptions;
    try {
      $this->connection = new \PDO('mysql:host=' . $this->host . ';dbname=' . $this->database, $this->user, $this->password, [
        \PDO::ATTR_PERSISTENT => TRUE,
      ]);
    }
    catch (\Exception $e) {
      echo $e;
    }
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
      'add-locks'      => FALSE,
      'exclude-tables' => $this->dumpOptions['excluded_tables'] ?? [],
      'no-data' => $this->dumpOptions['no-data'],
    ];
    try {
      $dump = new Mysqldump('mysql:host=' . $this->host . ';dbname=' . $this->database, $this->user, $this->password, $dumpSettings);
      $dump->setTransformTableNameHook(function($table_name, $reset) {
        return $this->rename_table($table_name, $reset);
      });
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
  public function sanitize($table_name, $col_name, $col_value, $options) {
    if (isset($options['sanitize_tables'])) {
      foreach ($options['sanitize_tables'] as $table => $val) {
        if ($table == $table_name) {
          foreach ($options['sanitize_tables'][$table] as $col) {
            if ($col == $col_name) {
              // Generate value based on the type of the actual value.
              // Helps avoid breakage with incorrect types in cols.
              switch (gettype($col_value)) {
                case 'integer':
                case 'double':
                  return random_int(1000000, 9999999);

                case 'string':
                  return (string) random_int(1000000, 9999999) . '-janitor';

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
    $ignore = [];
    if ($this->dumpOptions['trim_tables']) {
      foreach ($this->dumpOptions['trim_tables'] as $table) {
        // Skip table if not found.
        if (!$this->connection->query('SELECT 1 FROM ' . $table . ' LIMIT 1;')) {
          continue;
        }
        $this->connection->exec('CREATE TABLE janitor_' . $table . ' LIKE ' . $table);
        $ignore[] = $table;
        // This makes assumptions about the primary key, should be configurable.
        $primary_key = $this->getPrimaryKey($table);
        if ($primary_key) {
          $keep = [];
          if (isset($this->dumpOptions['keep_rows'][$table])) {
            $keep = implode(',', $this->dumpOptions['keep_rows'][$table]);
          }
          $all = $this->connection->query('SELECT ' . $primary_key . ' FROM ' . $table)
            ->fetchAll();
          foreach ($all as $key => $row) {
            // Delete every other row.
            if ($key % 4 == 0) {
              $keep[] = $row[$primary_key];
            }
          }
          $keep = implode(',', $keep);
          $this->connection->exec('INSERT INTO janitor_' . $table . ' SELECT * FROM ' . $table . ' WHERE ' . $primary_key . ' IN (' . $keep . ')');
        }
      }
    }
    return $ignore;
  }

  /**
   * Post-run to remove janitor_ prefixed tables.
   *
   * @param array $tables
   *   Tables to rename, in the form original_X.
   *
   * @return bool
   *   False if error occurred, true otherwise.
   */
  public function cleanup(array $tables) {
    foreach ($tables as $table) {
      $table = 'janitor_' . $table;
      $this->connection->exec('DROP TABLE ' . $table);
    }
    return TRUE;
  }

  /**
   * Returns primary key of table, if available.
   *
   * @param string $table
   *   Table name.
   *
   * @return mixed
   *   Primary key name.
   */
  private function getPrimaryKey($table) {
    $primary_key = $this->connection->query("SHOW KEYS FROM " . $table . " WHERE Key_name = 'PRIMARY'")
      ->fetch()['Column_name'];

    return $primary_key;
  }

  public function rename_table($table_name, $reset) {
    if (in_array($table_name, $this->dumpOptions['trim_tables'])) {
      return 'janitor_' . $table_name;
    }
    if ($reset) {
      return str_replace('janitor_', '', $table_name);
    }
    return $table_name;
  }
}
