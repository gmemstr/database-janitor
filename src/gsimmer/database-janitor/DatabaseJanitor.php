<?php

/**
 * @file Contains basic interface for DatabaseJanitor library.
 */

namespace DatabaseJanitor;

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

  private $SqlDetails;

  private $dumpOptions;

  private $sanitizeOptions;

  private $cleanOptions;

  /**
   * DatabaseJanitor constructor.
   */
  public function __construct($SqlDatabase, $SqlUser, $SqlHost, $SqlPassword, $cleanOptions, $dumpOptions, $sanitizeOptions) {
    $this->SqlDatabase     = $SqlDatabase;
    $this->SqlUser         = $SqlUser;
    $this->SqlHost         = $SqlHost;
    $this->SqlPassword     = $SqlPassword;
    $this->cleanOptions    = $cleanOptions;
    $this->dumpOptions     = $dumpOptions;
    $this->sanitizeOptions = $sanitizeOptions;
  }

  /**
   * Basic dumping.
   */
  public function dump() {
    try {
      $dump = new IMysqldump\Mysqldump('mysql:host=' . $this->SqlHost . '=' . $this->SqlDatabase, $this->SqlUser, $this->SqlPassword);
      $dump->start('output/' . $this->SqlHost . '_' . $this->SqlDatabase . '.sql');
    }
    catch (\Exception $e) {
      echo 'mysqldump - php error: ' . $e->getMessage();
    }
  }
}
