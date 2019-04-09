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

/**
 * Normalize the $name passed in argument.
 * By default, this remove all dash from the name, then replace all spaces by a defined separator (a dash "-" by default).
 * You can pass a different separator with the second $separator parameter.
 * The resulting name will be normalized in lower case unless you pass the third parameter $toUpper a `true` value.
 * @param String $name The name to normalize
 * @param String $separator The separator to use. Defaults to "-"
 * @param Boolean $toupper Wether the name sould be in lower case (false) or upper case (true). Defaults to `false`.
 * @return String The normalized name
 */
function normalize_name($name, $separator = '-', $toUpper = false) {
  $name = $toUpper ? strtoupper($name) : strtolower($name);
  $name = preg_replace('~ - ~', ' ', $name);
  return preg_replace('~ ~', $separator, $name);
}
