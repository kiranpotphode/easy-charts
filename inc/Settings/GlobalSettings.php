<?php
/**
 * Site-wide chart settings.
 *
 * @package EasyCharts
 */

namespace EasyCharts\Settings;

use EasyCharts\Chart\Schema;
use EasyCharts\Service;

/**
 * Registers the `easy_charts_settings` option: site-wide overrides of the built-in defaults.
 *
 * Exposed in the REST settings endpoint (manage_options only). The settings page arrives later.
 */
final class GlobalSettings implements Service {

	const OPTION = 'easy_charts_settings';

	/**
	 * Attach hooks.
	 *
	 * @return void
	 */
	public function register() {
		add_action( 'init', array( $this, 'register_setting' ) );
	}

	/**
	 * Register the option.
	 *
	 * @return void
	 */
	public function register_setting() {
		register_setting(
			'easy_charts',
			self::OPTION,
			array(
				'type'              => 'object',
				'description'       => __( 'Site-wide chart settings that override the built-in defaults.', 'easy-charts' ),
				'default'           => array(),
				'sanitize_callback' => array( $this, 'sanitize' ),
				'show_in_rest'      => array(
					'schema' => Schema::settings_schema(),
				),
			)
		);
	}

	/**
	 * Keep only well-formed settings groups.
	 *
	 * @param mixed $value Option value being saved.
	 * @return array
	 */
	public function sanitize( $value ) {
		return self::groups( $value );
	}

	/**
	 * Get the stored global settings.
	 *
	 * @return array
	 */
	public static function get() {
		return self::groups( get_option( self::OPTION, array() ) );
	}

	/**
	 * Drop anything that is not a named group of options.
	 *
	 * @param mixed $value Candidate settings.
	 * @return array
	 */
	private static function groups( $value ) {
		if ( ! is_array( $value ) ) {
			return array();
		}

		return array_filter(
			$value,
			static function ( $group, $name ) {
				return is_string( $name ) && is_array( $group );
			},
			ARRAY_FILTER_USE_BOTH
		);
	}
}
