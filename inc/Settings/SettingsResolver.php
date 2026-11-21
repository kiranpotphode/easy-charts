<?php
/**
 * Effective chart settings.
 *
 * @package EasyCharts
 */

namespace EasyCharts\Settings;

use EasyCharts\Chart\ChartRepository;
use EasyCharts\Migration\V1Converter;

/**
 * Works out a chart's effective settings from four layers, lowest first:
 *
 * 1. built-in defaults (Defaults)
 * 2. site-wide settings (GlobalSettings)
 * 3. the linked template chart's own settings, if any
 * 4. the chart's own overrides
 *
 * Charts store only their overrides, so changing a lower layer reaches every chart that did not
 * override that value.
 */
final class SettingsResolver {

	/**
	 * Chart storage, used to load templates.
	 *
	 * @var ChartRepository
	 */
	private $charts;

	/**
	 * Constructor.
	 *
	 * @param ChartRepository $charts Chart storage.
	 */
	public function __construct( ChartRepository $charts ) {
		$this->charts = $charts;
	}

	/**
	 * Get the effective settings for a chart document.
	 *
	 * @param array $doc Chart document.
	 * @return array
	 */
	public function resolve( array $doc ) {
		// A 1.x chart that keeps its 1.x look is drawn on the 1.x look, not the site-wide settings.
		$settings = empty( $doc['source']['v1Look'] )
			? self::merge( self::base(), $this->template_settings( $doc ) )
			: V1Converter::look_settings();

		$own = isset( $doc['settings'] ) && is_array( $doc['settings'] ) ? $doc['settings'] : array();

		return self::merge( $settings, $own );
	}

	/**
	 * The layers every chart shares: defaults with the site-wide settings applied.
	 *
	 * @return array
	 */
	public static function base() {
		return self::merge( Defaults::get(), GlobalSettings::get() );
	}

	/**
	 * Overrides from the chart's template.
	 *
	 * Only one level is followed: a template's own template is ignored, so templates cannot form loops.
	 * Templates that are missing, not charts, or not viewable by the current visitor are skipped.
	 *
	 * @param array $doc Chart document.
	 * @return array
	 */
	private function template_settings( array $doc ) {
		$template_id = isset( $doc['source']['templateId'] ) ? (int) $doc['source']['templateId'] : 0;
		if ( $template_id <= 0 ) {
			return array();
		}

		if ( 'publish' !== get_post_status( $template_id ) && ! current_user_can( 'read_post', $template_id ) ) {
			return array();
		}

		$template = $this->charts->find( $template_id );

		return ( null !== $template && is_array( $template['settings'] ) ) ? $template['settings'] : array();
	}

	/**
	 * Recursively merge overrides into a base.
	 *
	 * Keyed arrays merge key by key; lists (e.g. palette colours) and scalars replace the base value.
	 * An empty array overriding a keyed array changes nothing.
	 *
	 * @param array $base      Lower layer.
	 * @param array $overrides Higher layer.
	 * @return array
	 */
	public static function merge( array $base, array $overrides ) {
		foreach ( $overrides as $key => $value ) {
			if ( is_array( $value ) && isset( $base[ $key ] ) && is_array( $base[ $key ] ) && ! self::is_list( $base[ $key ] ) ) {
				if ( array() === $value ) {
					continue;
				}
				if ( ! self::is_list( $value ) ) {
					$base[ $key ] = self::merge( $base[ $key ], $value );
					continue;
				}
			}

			$base[ $key ] = $value;
		}

		return $base;
	}

	/**
	 * Whether an array is a non-empty list (sequential integer keys from 0).
	 *
	 * @param array $value Array to check.
	 * @return bool
	 */
	private static function is_list( array $value ) {
		return array() !== $value && array_keys( $value ) === range( 0, count( $value ) - 1 );
	}
}
