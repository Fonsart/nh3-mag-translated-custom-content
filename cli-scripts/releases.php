<?php
// phpcs:ignoreFile

namespace CliScripts;

use Composer\Script\Event;
use Composer\Installer\PackageEvent;
use Localheinz\Json\Printer\Printer;

use \CurlFile;
use \ZipArchive;

require_once 'utils.php';

class Releases {

  const VERSION_SYNTAX = '/v(\d\.){2}\d/';
  const MAKE_COMMAND   = 'make';
  const ZIP_COMMAND    = 'zip';
	const WHITELIST      = array( 'major', 'minor', 'patch' );
	const JSON_INDENT    = '  ';

	/**
	 * Main entry point.
	 * Will execute an action based on the given parameters.
	 * @param Event $event A Composer Event object
	 */
	public static function route( Event $event ) {
		$args = $event->getArguments();
		// Incorrect number of arguments
		if ( sizeof( $args ) === 0 || sizeof( $args ) > 2 ) {
			write( 'ERROR --- You provided ' . sizeof( $args ) . ' argument' . ( sizeof( $args ) === 0 ? '' : 's' ) . '.' );
			self::writeHelp();
			exit();
		}
		$action = $args[0];
		// Make action
 		if ( $action === self::MAKE_COMMAND ) {
			$type = isset( $args[1] ) ? $args[1] : null;
			if ( null !== $type && in_array( $type, self::WHITELIST ) ) {
				self::make( $type );
			} else {
				write(
					array(
						'Bad type argument',
						self::writeHelp(),
					)
				);
			}
			// Shortcut make action
		} elseif ( in_array( $action, self::WHITELIST ) ) {
			self::make( $action );
			// Unknown action - prints the help
		} else {
			write( self::writeHelp() );
		}
	}

	/**
	 * Creates a new release using the semver syntax.
	 * The function accepts one argument, which is the type of the release, that must be one of the following values:
	 * * `major` - A major release up the major number of your semver, and reset the minor and patch numbers, i.e. going from v0.1.3 to v1.0.0
	 * * `minor` - A minor release up the minor number of your semver, and reset the patch number, i.e. going from v0.1.3 to v0.2.0
	 * * `patch` - A patch release up the patch number of your semver, i.e. going from v0.1.3 to v0.1.4
	 *
	 * The current version number is retrieved and bumped according to the given $type.
	 *
	 * After bumping the version number, it will be saved in both the plugin.json file and package.json file.
	 * Then, those file changes will be commited to git and tagged with a new git tag, whose name will be the new version number.
	 * Finally, these new commit and tag will be pushed to the current remote branch.
	 *
	 * @param string $type The type of release to make
	 */
	private static function make( string $type ) {
		if ( self::checkGitStatus() ) {
			$versions = self::bumpVersionNumber( $type );
			write(
				array(
					'INFO ---- Last version found was ' . $versions['last'],
					"INFO ---- Release type \"$type\" bumped the version to " . $versions['current'],
				)
      );
      // Update the plugin.json if it exists
			if ( file_exists( 'plugin.json' ) ) {
        self::updateJsonVersion( $versions['current'] );
			}
			// Update the package.json if it exists
			if ( file_exists( 'package.json' ) ) {
				self::updateJsonVersion( $versions['current'], array( 'file' => 'package.json' ) );
			}
			// Regenerate the plugin header file
			exec( 'composer plugin-header' );
			// Make new commit
			exec( 'git add .' );
			exec( 'git commit -m "Release new ' . $type . ' version - ' . $versions['current'] . '"' );
			write( 'INFO ---- New commit for the release.' );
			// Add new tag with the new version
			exec( 'git tag ' . $versions['current'] );
			write( 'INFO ---- New git tag "' . $versions['current'] . '" created.' );
			// Push changes to remote
			exec( 'git push && git push --tag' );
			write( 'SUCCESS - Release commit and tag pushed to remote branch' );
		}
  }

  /**
   * Creates the zip folder for the release.
   * The resulting zip will be named after the plugin.
   * It will contain a single directory which will contain all the plugin files.
   * @return Mixed The relative path to the release zip, as a String, or false if something bad happened.
   */
  public static function makeZipFolder() {
    $pluginName = normalizeName(loadConfigFrom('plugin.json')->pluginName);
    $config = self::getReleaseConfig();
    $zipName = "{$pluginName}.zip";
    $zipPath = $zipName;
    try {
      $zip = new ZipArchive();
      if ($zip->open($zipPath, ZipArchive::CREATE) !== TRUE) {
        throw new Exception("cannot open <$zipPath>\n");
      }
      foreach ($config['zip_content']['files'] as $glob) {
        $zip->addGlob($glob, GLOB_BRACE, ['add_path' => 'nh3-mag-translated-custom-content/']);
      }
      write("INFO ---- $zip->numFiles file(s) added to the zip.");
      $zip->close();
      write("SUCCESS - New release zip file created at $zipPath");
    } catch (\Exception $e) {
      write("ERROR --- Error while creating the zipfile.");
      $zipPath = false;
    }
    return $zipPath;
  }

  /**
   * Returns the content of the release .conf file as an associative array.
   */
  private static function getReleaseConfig() {
    return parse_ini_file('.release.conf', true);
  }

	public static function writeHelp() {
		write(
			array(
				'---------------------------------------',
				'Update the version number',
				'',
				'Usage:',
				' composer release [make] <major|minor|patch>',
				'',
				'Arguments:',
				'  major ---- will increment the first number of the release version.',
				'             example: getting from a v1.2.3 to a v2.0.0',
				'  minor ---- will increment the second number of the release version.',
				'             example: getting from a v.1.2.3 to a v.1.3.0',
				'  patch ---- will increment the last number of the release version.',
        '             example: getting from a v.1.2.3 to a v.1.2.4',
        '',
				'Help:',
				'  Update the plugin.json file and/or the package.json file with a new semver number.',
        '  Regenerate a new [plugin-name].php file, using the new version number.',
        '  Create a new Git commit with all the updated files.',
        '  Add a new tag using the semver number as name.',
        '  Push the commit and the tag to the remote branch tracked by the current local branch.'
			)
		);
	}

	/**
	 * Retrieves the current version number from (by order of priority):
	 * * The latest git tag that matches the semver syntax, if any (TODO)
	 * * The version number in the plugin.json file, if it exists
	 * * The version number in the package.json file, if it exists
	 * * Defaults to v0.0.0 if none of the above apply
	 *
	 * @param string $type The type of version update.
	 * @return array An array with two item.
	 *                `last` contains the last version number.
	 *                `current` contains the new version number.
	 */
	private static function bumpVersionNumber( $type ) {
		$versions = array();
		exec( 'git tag -l', $tags ); // Get git tags
		if ( sizeof( $tags ) !== 0 ) {
			$versions['last'] = end( $tags );
		} elseif ( file_exists( 'plugin.json' ) ) {
			$versions['last'] = getVersionFrom( 'plugin.json' );
		} elseif ( file_exists( 'package.json' ) ) {
			$versions['last'] = getVersionFrom( 'package.json' );
		} else {
			$versions['last'] = 'v0.0.0';
		}
		$last_array    = explode( '.', str_replace( 'v', '', $versions['last'] ) );
		$current_array = array(
			'major' => (int) $last_array[0],
			'minor' => (int) $last_array[1],
			'patch' => (int) $last_array[2],
		);

		// Bump the version
		switch ( $type ) {
			case 'major':
				$current_array['major']++;
				$current_array['minor'] = 0;
				$current_array['patch'] = 0;
				break;
			case 'minor':
				$current_array['minor']++;
				$current_array['patch'] = 0;
				break;
			case 'patch':
				$current_array['patch']++;
				break;
		}
		$versions['current'] = 'v' . implode( '.', $current_array );
		return $versions;
	}

	/**
	 * Update the version number in the plugin.json file to the given $version number.
	 * You can change the file in which the version is updated by passing an $options array with
	 * a `file` item whose value contains the path to the file.
   * **This file must be a JSON file with at least a `version` property.**
	 * @param string $version The version number
	 * @param array [$options] An options array
	 * @param string [$options['file']] The path to the file to update. Defaults to `plugin.json`
	 */
	private static function updateJsonVersion( string $version, array $options = array( 'file' => 'plugin.json' ) ) {
		$config          = loadConfigFrom( $options['file'] );
		$config->version = str_replace( 'v', '', $version );
		$printer         = new Printer();
		$printed         = $printer->print( json_encode( $config, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES ), self::JSON_INDENT );
		file_put_contents( $options['file'], $printed );
		write( "SUCCESS - {$options['file']} version has been updated to $version" );
	}

	/**
	 * Performs status checks on the repository.
	 * Ensure that there is no unstaged changes and no unpushed commits.
	 * @return boolean True if all checks passed, False otherwise.
	 */
	private static function checkGitStatus() {
		exec( 'git status --porcelain', $status );
		if ( sizeof( $status ) !== 0 ) {
			write(
				array(
					'ERROR --- You have unstaged changes in your repository...',
					'INFO ---- Please commit or stash them and retry.',
				)
			);
			return false;
		}
		exec( 'git log @{u}..', $commits );
		if ( sizeof( $commits ) !== 0 ) {
			write(
				array(
					'ERROR --- You have local commits that are not pushed to remote branch...',
					'INFO ---- Please push your local commits and retry.',
				)
			);
			return false;
		}
		return true;
	}

}
