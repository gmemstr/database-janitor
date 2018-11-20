<?php
/**
 * Created by PhpStorm.
 * User: gsimmer
 * Date: 17/11/18
 * Time: 11:53 PM
 */

namespace DatabaseJanitor;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;

require getcwd() . '/vendor/autoload.php';
require 'DatabaseJanitor.php';

class DatabaseJanitorCommand extends Command {

  private $host;
  private $username;
  private $password;
  private $database;
  private $configuration;

  private $janitor;

  public function __construct($configuration = array()) {
    parent::__construct();
    $this->configuration = $configuration;
  }

  protected function configure() {
    $this->setName('database-janitor')
      ->setDescription('Cleans up databases between servers or dev enviornments')
      ->addOption('host', null, InputOption::VALUE_REQUIRED, 'Database host')
      ->addOption('username', 'u', InputOption::VALUE_OPTIONAL, 'Database username')
      ->addOption('password', 'p', InputOption::VALUE_OPTIONAL, 'Database password')
      ->addOption('trim','t', InputOption::VALUE_OPTIONAL, 'Whether or not to execute trimming', FALSE)
      ->addArgument('database', InputArgument::REQUIRED, 'Database to dump');
  }

  protected function execute(InputInterface $input, OutputInterface $output) {

    // Set up configuration.
    $helper = $this->getHelper('question');
    $this->host = $input->getOption('host');
    $this->database = $input->getArgument('database');
    if (!$this->username = $input->getOption('username')) {
      $question = new Question('Enter database user: ');
      $this->username = $helper->ask($input, $output, $question);
    }
    if (!$this->password = $input->getOption('password')) {
      $question = new Question('Enter database password for ' . $this->username . ': ');
      $question->setHidden(true);
      $question->setHiddenFallback(false);
      $this->password = $helper->ask($input, $output, $question);
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

    // Optionally trim the database, requires the earlier dump to be loaded into
    // the "trim database".
    else {
      $ignore_tables = $this->janitor->trim($this->configuration['trim_database']);

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
    }
  }

}
