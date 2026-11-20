<?php
/**
 * Chart document schema (v2).
 *
 * @package EasyCharts
 */

namespace EasyCharts\Chart;

use WP_Error;

/**
 * Defines, validates and normalizes the v2 chart document.
 *
 * A document looks like:
 *
 *     {
 *       "schema": 2,
 *       "type": "bar",
 *       "data": { "columns": [ "", "Kia", "Nissan" ], "rows": [ [ "2008", 10, 11 ] ] },
 *       "settings": { "legend": { "position": "bottom" } },
 *       "source": { "templateId": null }
 *     }
 *
 * The data grid keeps the 1.x orientation: column 0 holds the row labels and every
 * later column is one series. `settings` holds only values that override the
 * defaults, global settings and template (see SettingsResolver).
 */
final class Schema {

	const VERSION = 2;

	const TYPES = array(
		'bar',
		'stacked-bar',
		'step-up-bar',
		'percent-bar',
		'area',
		'stacked-area',
		'percent-area',
		'line',
		'pie',
		'donut',
		'polar-area',
		'waterfall',
	);

	/**
	 * Chart type names, translated.
	 *
	 * @return array Labels keyed by type slug.
	 */
	public static function type_labels() {
		return array(
			'bar'          => __( 'Bar', 'easy-charts' ),
			'stacked-bar'  => __( 'Stacked bar', 'easy-charts' ),
			'percent-bar'  => __( 'Percent bar', 'easy-charts' ),
			'step-up-bar'  => __( 'Step-up bar', 'easy-charts' ),
			'waterfall'    => __( 'Waterfall', 'easy-charts' ),
			'line'         => __( 'Line', 'easy-charts' ),
			'area'         => __( 'Area', 'easy-charts' ),
			'stacked-area' => __( 'Stacked area', 'easy-charts' ),
			'percent-area' => __( 'Percent area', 'easy-charts' ),
			'pie'          => __( 'Pie', 'easy-charts' ),
			'donut'        => __( 'Donut', 'easy-charts' ),
			'polar-area'   => __( 'Polar area', 'easy-charts' ),
		);
	}

	/**
	 * JSON schema of a chart document, in the subset understood by the WordPress REST API.
	 *
	 * @return array
	 */
	public static function get() {
		return array(
			'type'                 => 'object',
			'additionalProperties' => false,
			'required'             => array( 'schema', 'type', 'data' ),
			'properties'           => array(
				'schema'   => array(
					'type' => 'integer',
					'enum' => array( self::VERSION ),
				),
				'type'     => array(
					'type' => 'string',
					'enum' => self::TYPES,
				),
				'data'     => array(
					'type'                 => 'object',
					'additionalProperties' => false,
					'required'             => array( 'columns', 'rows' ),
					'properties'           => array(
						'columns' => array(
							'type'     => 'array',
							'minItems' => 1,
							'items'    => array( 'type' => 'string' ),
						),
						'rows'    => array(
							'type'  => 'array',
							'items' => array(
								'type'  => 'array',
								'items' => array( 'type' => array( 'string', 'number', 'null' ) ),
							),
						),
					),
				),
				'settings' => self::settings_schema(),
				'source'   => array(
					'type'                 => 'object',
					'additionalProperties' => false,
					'properties'           => array(
						'templateId' => array( 'type' => array( 'integer', 'null' ) ),
						// A converted 1.x chart that keeps its 1.x look until it is migrated.
						'v1Look'     => array( 'type' => 'boolean' ),
					),
				),
			),
		);
	}

	/**
	 * Schema of a settings object: groups (legend, tooltip, ...) of option values.
	 *
	 * Shared by chart overrides and the global settings option. Group contents stay open until the
	 * ECharts option builder defines them.
	 *
	 * @return array
	 */
	public static function settings_schema() {
		return array(
			'type'                 => 'object',
			'additionalProperties' => array(
				'type'                 => 'object',
				'additionalProperties' => true,
			),
		);
	}

	/**
	 * A new, empty chart document.
	 *
	 * @return array
	 */
	public static function empty_document() {
		return array(
			'schema'   => self::VERSION,
			'type'     => 'bar',
			'data'     => array(
				'columns' => array( '' ),
				'rows'    => array(),
			),
			'settings' => array(),
			'source'   => array( 'templateId' => null ),
		);
	}

	/**
	 * Validate a document against the schema.
	 *
	 * @param mixed $value Candidate document.
	 * @return true|WP_Error
	 */
	public static function validate( $value ) {
		return rest_validate_value_from_schema( $value, self::get(), 'chart' );
	}

	/**
	 * Sanitize a document against the schema, then normalize it.
	 *
	 * Also used as the post meta sanitize callback, so it must accept any value.
	 *
	 * @param mixed $value Candidate document.
	 * @return array|WP_Error
	 */
	public static function sanitize( $value ) {
		$valid = self::validate( $value );
		if ( is_wp_error( $valid ) ) {
			return $valid;
		}

		$value = rest_sanitize_value_from_schema( $value, self::get(), 'chart' );
		if ( is_wp_error( $value ) ) {
			return $value;
		}

		return self::normalize( $value );
	}

	/**
	 * Fill optional parts and make every row as long as the header row.
	 *
	 * Short rows are padded with null and long rows are trimmed, so renderers can rely on a rectangular grid.
	 *
	 * @param array $doc A document that passed validation.
	 * @return array
	 */
	public static function normalize( array $doc ) {
		$width = count( $doc['data']['columns'] );

		$rows = array();
		foreach ( $doc['data']['rows'] as $row ) {
			$row    = array_slice( array_values( $row ), 0, $width );
			$rows[] = array_map( array( self::class, 'normalize_cell' ), array_pad( $row, $width, null ) );
		}
		$doc['data']['rows'] = $rows;

		if ( ! isset( $doc['settings'] ) ) {
			$doc['settings'] = array();
		}

		$template_id   = isset( $doc['source']['templateId'] ) ? (int) $doc['source']['templateId'] : 0;
		$v1_look       = ! empty( $doc['source']['v1Look'] );
		$doc['source'] = array( 'templateId' => $template_id > 0 ? $template_id : null );
		if ( $v1_look ) {
			$doc['source']['v1Look'] = true;
		}

		return $doc;
	}

	/**
	 * Keep whole numbers as integers.
	 *
	 * REST sanitization casts every "number" to float, which would turn 10 into 10.0.
	 *
	 * @param mixed $cell Cell value.
	 * @return mixed
	 */
	private static function normalize_cell( $cell ) {
		if ( is_float( $cell ) && is_finite( $cell ) && floor( $cell ) === $cell && abs( $cell ) <= PHP_INT_MAX ) {
			return (int) $cell;
		}

		return $cell;
	}
}
