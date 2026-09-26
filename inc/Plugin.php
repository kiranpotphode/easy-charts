<?php
/**
 * Plugin entry point.
 *
 * @package EasyCharts
 */

namespace EasyCharts;

/**
 * Builds the plugin's services and registers them with WordPress.
 */
final class Plugin {

	/**
	 * Shared instance.
	 *
	 * @var Plugin|null
	 */
	private static $instance = null;

	/**
	 * Whether boot() has already run.
	 *
	 * @var bool
	 */
	private $booted = false;

	/**
	 * Registered services, keyed by class name.
	 *
	 * @var Service[]
	 */
	private $services = array();

	/**
	 * Get the shared instance.
	 *
	 * @return Plugin
	 */
	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Create the services and attach them to WordPress hooks. Safe to call more than once.
	 *
	 * @return void
	 */
	public function boot() {
		if ( $this->booted ) {
			return;
		}
		$this->booted = true;

		foreach ( $this->create_services() as $service ) {
			$this->services[ get_class( $service ) ] = $service;
			$service->register();
		}
	}

	/**
	 * Get a registered service by class name.
	 *
	 * @param string $class_name Fully qualified class name.
	 * @return Service|null
	 */
	public function get( $class_name ) {
		return isset( $this->services[ $class_name ] ) ? $this->services[ $class_name ] : null;
	}

	/**
	 * Build the plugin's services.
	 *
	 * @return Service[]
	 */
	private function create_services() {
		return array(
			new PostType\ChartPostType(),
			new Chart\ChartMeta(),
		);
	}
}
