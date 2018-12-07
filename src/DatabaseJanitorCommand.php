<?php

namespace DatabaseJanitor;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;

require __DIR__ . '/../vendor/autoload.php';
require 'DatabaseJanitor.php';
/**
 * Class DatabaseJanitorCommand.
 *
 * @package DatabaseJanitor
 */
class DatabaseJanitorCommand extends Command {

  private $host;
  private $username;
  private $password;
  private $database;
  private $configuration;

  private $janitor;

  /**
   * DatabaseJanitorCommand constructor.
   */
  public function __construct($configuration = array()) {
    parent::__construct();
    $this->configuration = $configuration;
  }

  /**
   * Configure Symfony Console command.
   */
  protected function configure() {
    $this->setName('database-janitor')
      ->setDescription('Cleans up databases between servers or dev enviornments')
      ->addOption('host', NULL, InputOption::VALUE_REQUIRED, 'Database host')
      ->addOption('username', 'u', InputOption::VALUE_OPTIONAL, 'Database username')
      ->addOption('password', 'p', InputOption::VALUE_OPTIONAL, 'Database password')
      ->addOption('trim', 't', InputOption::VALUE_NONE, 'Whether or not to execute trimming')
      ->addOption('drupal', 'd', InputOption::VALUE_REQUIRED, 'Whether to read from a Drupal settings file (and where)')
      ->addArgument('database', InputArgument::REQUIRED, 'Database to dump');
  }

  /**
   * Execute Database Janitor functions based on values passed.
   */
  protected function execute(InputInterface $input, OutputInterface $output) {

    // Set up configuration.
    $helper = $this->getHelper('question');
    $this->host = $input->getOption('host');
    $this->database = $input->getArgument('database');
    if (!$input->getOption('drupal')) {
      if (!$this->username = $input->getOption('username')) {
        $question = new Question('Enter database user: ');
        $this->username = $helper->ask($input, $output, $question);
      }
      if (!$this->password = $input->getOption('password') && !$input->getOption('drupal')) {
        $question = new Question('Enter database password for ' . $this->username . ': ');
        $question->setHidden(TRUE);
        $question->setHiddenFallback(FALSE);
        $this->password = $helper->ask($input, $output, $question);
      }
    }
    else {
      // Try to load drupal configuration file specified, assuming database is
      // "default" until I hear otherwise.
      require_once $input->getOption('drupal');
      if ($databases['default'] && is_array($databases['default'])) {
        $db_array = $databases['default']['default'];
        $this->host = $db_array['host'];
        $this->username = $db_array['username'];
        $this->password = $db_array['password'];
      }
    }

    $this->janitor = new DatabaseJanitor(
      $this->database, $this->username, $this->host, $this->password, $this->configuration
    );

    if (!$input->getOption('trim')) {
      $dumpresult = $this->janitor->dump();
      if (!$dumpresult) {
        $output->writeln("Something went horribly wrong.");
      }
    }

    else {
      $trimmed_tables = $this->janitor->trim();
      $scrubbed_tables = $this->janitor->scrub();
      $ignore_tables = array_merge($trimmed_tables, $scrubbed_tables);

      foreach ($ignore_tables as $ignore_table) {
        $this->configuration['excluded_tables'][] = $ignore_table;
      }
      // Reload configuration with new ignore tables.
      $this->janitor = new DatabaseJanitor(
        $this->database, $this->username, $this->host, $this->password, $this->configuration
      );

      $dumpresult = $this->janitor->dump();
      if (!$dumpresult) {
        printf("Something went horribly wrong.");
      }
      $this->janitor->cleanup($ignore_tables);
    }
  }

}
