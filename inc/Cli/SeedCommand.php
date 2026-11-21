<?php
/**
 * Development command that seeds example charts.
 *
 * @package EasyCharts
 */

namespace EasyCharts\Cli;

use EasyCharts\Demo\Examples;
use EasyCharts\Service;
use WP_CLI;

/**
 * `wp easy-charts seed-examples`: creates the example charts and pages (see Examples). Only
 * registered on local and development sites.
 */
final class SeedCommand implements Service {

	/**
	 * Example charts.
	 *
	 * @var Examples
	 */
	private $examples;

	/**
	 * Constructor.
	 *
	 * @param Examples $examples Example charts.
	 */
	public function __construct( Examples $examples ) {
		$this->examples = $examples;
	}

	/**
	 * Whether the command should be available in this environment.
	 *
	 * @return bool
	 */
	public static function is_available() {
		return defined( 'WP_CLI' ) && WP_CLI && in_array( wp_get_environment_type(), array( 'local', 'development' ), true );
	}

	/**
	 * Register the WP-CLI command.
	 *
	 * @return void
	 */
	public function register() {
		WP_CLI::add_command( 'easy-charts seed-examples', array( $this, 'seed' ) );
	}

	/**
	 * Create or update the example charts and the page that shows them.
	 *
	 * ## OPTIONS
	 *
	 * [--v1]
	 * : Store the charts in the 1.x format (converted when drawn) instead of the 2.0 format.
	 *
	 * ## EXAMPLES
	 *
	 *     wp easy-charts seed-examples
	 *     wp easy-charts seed-examples --v1
	 *
	 * @param array $args       Positional arguments (unused).
	 * @param array $assoc_args Options.
	 * @return void
	 */
	public function seed( $args = array(), $assoc_args = array() ) {
		$result = $this->examples->create( ! empty( $assoc_args['v1'] ) );
		if ( is_wp_error( $result ) ) {
			WP_CLI::error( $result->get_error_message() );
		}

		$page = array_pop( $result );
		foreach ( $result as $line ) {
			WP_CLI::log( $line );
		}
		WP_CLI::success( $page );
	}
}
