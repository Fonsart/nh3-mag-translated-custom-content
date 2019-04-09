<?php
/**
 * Writes the passed $text to the console using an `echo` call.
 * @param $text mixed - Can be either a string or an array of string. If you pass an array of string, each item will be written on y new line.
 */
function write($text = '') {
  if (is_array($text)) {
    foreach ($text as $line) {
      write($line);
    }
  } else {
    echo $text.PHP_EOL;
  }
}

/**
 * Load the plugin configuration file.
 * The file MUST be named `plugin.json` and be stored at the root of your plugin folder.
 */
function loadConfigFile() {
  return json_decode(file_get_contents('plugin.json'));
}

function normalize_plugin_name($name, $separator = '-', $toUpper = false) {
  $name = $toUpper ? strtoupper($name) : strtolower($name);
  $name = preg_replace('~ - ~', ' ', $name);
  return preg_replace('~ ~', $separator, $name);
}
