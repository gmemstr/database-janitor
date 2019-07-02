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
    $this->database = $database;
    $this->user = $user;
    $this->host = $host;
    $this->password = $password;
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
  public function dump($host = FALSE, $output = FALSE, $trim = FALSE) {
    if (!$output) {
      $output = 'php://stdout';
    }

    if ($host) {
      $this->database = $host->database;
      $this->user = $host->user;
      $this->host = $host->host;
      $this->password = $host->password;
    }

    $dumpSettings = [
      'add-locks' => FALSE,
      'exclude-tables' => $this->dumpOptions['excluded_tables'] ?? [],
      'no-data' => $this->dumpOptions['scrub_tables'] ?? [],
      'keep-data' => $this->dumpOptions['keep_data'] ?? [],
    ];

    try {
      $dump = new Mysqldump('mysql:host=' . $this->host . ';dbname=' . $this->database, $this->user, $this->password, $dumpSettings);
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

}
