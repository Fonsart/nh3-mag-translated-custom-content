<?php
// phpcs:ignoreFile

/**
 * Writes the passed $text to the console using an `echo` call.
 * @param $text mixed - Can be either a string or an array of string. If you pass an array of string, each item will be written on y new line.
 */
function write( $text = '' ) {
	if ( is_array( $text ) ) {
		foreach ( $text as $line ) {
			write( $line );
		}
	} else {
		echo $text . PHP_EOL;
	}
}

/**
 * Load the configuration from the given json $file name.
 * **Note that you need to pass the _complete_ file name, e.g. with its extension.**
 *
 * @param string $file The name (with or without extension) of the file to load from. Defaults to plugin.json
 * @return array|null The config array or null if the file does not exists.
 */
function loadConfigFrom( $file = 'plugin.json' ) {
	if ( file_exists( $file ) ) {
		return json_decode( file_get_contents( $file ) );
	} else {
		return null;
	}
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
function normalizeName( $name, $separator = '-', $toUpper = false ) {
	$name = $toUpper ? strtoupper( $name ) : strtolower( $name );
	$name = preg_replace( '~ - ~', ' ', $name );
	return preg_replace( '~ ~', $separator, $name );
}

/**
 * Get the current version number from the given file.
 * The $file value should be either `plugin.json` or `package.json`.
 * @param string $file The file to retrieve the version number from
 * @return string|null The version number or null if the file did not exists.
 */
function getVersionFrom( $file ) {
	if ( file_exists( $file ) ) {
		return 'v' . loadConfigFrom( $file )->version;
	} else {
		return null;
	}
}
