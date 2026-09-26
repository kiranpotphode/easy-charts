<?php
/**
 * Built script and style files.
 *
 * @package EasyCharts
 */

namespace EasyCharts;

/**
 * Reads the files `npm run build` writes to build/: <name>.js, <name>.css and <name>.asset.php
 * (dependencies and version).
 */
final class Assets {

	/**
	 * Dependencies and version of a built entry.
	 *
	 * @param string $name Entry name.
	 * @return array|null `dependencies` and `version`, or null if the build is missing.
	 */
	public static function get( $name ) {
		$file = EASY_CHARTS_DIR . 'build/' . $name . '.asset.php';
		if ( ! is_readable( $file ) ) {
			return null;
		}

		$asset = require $file;

		return is_array( $asset ) ? $asset : null;
	}

	/**
	 * URL of a built file.
	 *
	 * @param string $file File name inside build/.
	 * @return string
	 */
	public static function url( $file ) {
		return plugins_url( 'build/' . $file, EASY_CHARTS_FILE );
	}
}
