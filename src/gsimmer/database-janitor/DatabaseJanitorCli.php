<?php

// If running from CLI, use CLI mode.
if (defined('STDIN')) {
  printf("Running from CLI...\n");
  $config = [];
  // Parse passed args.
  if (isset($argv)) {
    foreach (array_slice($argv,1) as $arg) {
      $arg_split = explode('=', $arg);
      $arg_split[0] = str_replace('--', '', $arg_split[0]);
      $config[$arg_split[0]] = $arg_split[1];
    }
  }

  if (isset($config['config'])) {
    $config_json = file_get_contents(__DIR__ . '/' . $config['config']);
  }
  else {
    $config_json = file_get_contents(__DIR__ . '/janitor.json');
  }
}
else {
  exit();
}
