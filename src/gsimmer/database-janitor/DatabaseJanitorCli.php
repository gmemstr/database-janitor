<?php

require_once('DatabaseJanitor.php');

use DatabaseJanitor\DatabaseJanitor;

// If running from CLI, use CLI mode.
if (defined('STDIN')) {
  printf("Running from CLI...\n");

  // -- Load in configuration.
  $config = load_config();

  $janitor = new DatabaseJanitor(
    $config['database'],
    $config['username'],
    $config['host'],
    $config['password'],
    $config
  );

  // Only dump if trim is not set (--trim=true).
  if (!$config['trim']) {
    printf("Dump of " . $config['database'] . " from " . $config['host'] . " started at " . date('d-m-Y g:i:s') . "\n");
    $output = $janitor->dump();
    if (!$output) {
      printf("Something went horribly wrong.\n");
    }
    printf("Dump of " . $config['database'] . " from " . $config['host'] . " finished at " . date('d-m-Y g:i:s') . "\nSaved to " . $output);
  }

  // Optionally trim the database, requires the earlier dump to be loaded into
  // the "trim database".
  else {
    $trim_output = $janitor->trim($config['trim_database']);
    if (!$trim_output) {
      printf("No tables were trimmed.\n");
    }
    else {
      printf("Trimmed database, dumping.\n");
    }
    $output = $janitor->dump(getcwd() . '/output/' . $config['host'] . '_' . $config['database'] . '-trimmed.sql.gz');
    if (!$output) {
      printf("Something went horribly wrong.");
    }
    printf("Dump of " . $config['database'] . " from " . $config['host'] . " finished at " . date('d-m-Y g:i:s') . "\nSaved to " . $output);
  }
}
// If not executing from CLI just exit.
else {
  exit();
}

/**
 * Loads/refreshes configuration from args, env and json.
 *
 * @return array
 *   Array of configuration values.
 */
function load_config() {
  global $argv;
  $config = [];
  if (array_slice($argv,1) !== null) {
    foreach (array_slice($argv,1) as $arg) {
      $arg_split = explode('=', $arg);
      $arg_split[0] = str_replace('--', '', $arg_split[0]);
      $config[$arg_split[0]] = $arg_split[1];
    }
  }

  // Load env variables.
  $env_vars = [
    'host' => 'DB_JANITOR_HOST',
    'user' => 'DB_JANITOR_USER',
    'password' => 'DB_JANITOR_PASSWORD',
    'database' => 'DB_JANITOR_DATABASE',
  ];
  foreach ($env_vars as $key => $env_var) {
    $val = getenv($env_var);
    if ($val && !isset($config[$key])) {
      $config[$key] = $val;
    }
  }

  if (isset($config['config'])) {
    $config_json = file_get_contents(getcwd() . '/' . $config['config']);
  }
  else {
    $config_json = file_get_contents(getcwd() . '/janitor.json');
  }
  $config_json = json_decode($config_json);
  $c = $config_json->config;

  foreach ($c as $key => $value) {
    if (!isset($config[$key])) {
      $config[$key] = $value;
    }
  }
  $config['tables'] = $config_json->tables;
  $config['trim_tables'] = $config_json->trim_tables;

  return $config;
}
