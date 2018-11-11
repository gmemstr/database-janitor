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
   */
  public function dump() {
    try {
      $dump = new IMysqldump\Mysqldump('mysql:host=' . $this->SqlHost . ';dbname=' . $this->SqlDatabase, $this->SqlUser, $this->SqlPassword);
      if (isset($this->dumpOptions)) {
        $dump->setTransformColumnValueHook(function ($table_name, $col_name, $col_value) {
          return sanitize($table_name, $col_name, $col_value, $this->dumpOptions['tables']);
        });
      }
      $dump->setTransformColumnValueHook(function ($table_name, $col_name, $col_value) {
        return santize_users($table_name, $col_name, $col_value, $this->dumpOptions['tables']);
      });
      $dump->start(getcwd() . '/output/' . $this->SqlHost . '_' . $this->SqlDatabase . '.sql');
    }
    catch (\Exception $e) {
      echo 'mysqldump - php error: ' . $e->getMessage();
    }
  }

  private function sanitize($table_name, $col_name, $col_value, $targets) {
    if (in_array($col_name, $targets)) {
      return (string) rand(1000000, 9999999);
    }

    return $col_value;
  }

  private function sanitize_users($table_name, $col_name, $col_value, $targets) {
    if ($table_name == 'user') {
      switch ($col_name) {
        case 'password':
          // Todo: Replace with default "password" as hash value.
          $col_value = "";
          break;

        case 'username':
        case 'email':
          $col_value = "";
          break;
      }
    }

    return $col_value;
  }
}
