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
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

require getcwd() . '/vendor/autoload.php';

class DatabaseJanitorCommand extends Command {

  protected function configure() {
    $this->setName('database-janitor')
      ->setDescription('Cleans up databases between servers or dev enviornments')
      ->addArgument('host', InputArgument::REQUIRED, 'Database host');
  }

  protected function execute(InputInterface $input, OutputInterface $output) {
    $output->writeln('Host: '.$input->getArgument('host'));
  }

}
