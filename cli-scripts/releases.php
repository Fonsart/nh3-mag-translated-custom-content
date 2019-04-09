<?php

namespace CliScripts;

use Composer\Script\Event;
use Composer\Installer\PackageEvent;
use \CurlFile;
use \ZipArchive;

include_once 'utils.php';

class Releases {

  /**
   * Creates the zip folder for the release.
   * The resulting zip will be named after the plugin.
   * It will contain a single directory which will contain all the plugin files.
   * @return Mixed The relative path to the release zip, as a String, or false if something bad happened.
   */
  public static function makeZipFolder() {
    $pluginName = normalize_plugin_name(loadConfigFile()->pluginName);
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

}
