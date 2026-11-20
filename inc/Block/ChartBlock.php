<?php
/**
 * The chart block.
 *
 * @package EasyCharts
 */

namespace EasyCharts\Block;

use EasyCharts\Frontend\ChartRenderer;
use EasyCharts\Migration\V1Converter;
use EasyCharts\Service;
use EasyCharts\Settings\SettingsResolver;

/**
 * Registers the `easy-charts/chart` block and renders it on the server.
 *
 * A block either embeds an existing chart post (`chartId`) or holds its own chart document
 * (`chart`). Nothing but the attributes is saved in the post content, so markup changes never
 * invalidate existing blocks and global settings apply at render time.
 */
final class ChartBlock implements Service {

	const NAME = 'easy-charts/chart';

	/**
	 * Chart output.
	 *
	 * @var ChartRenderer
	 */
	private $renderer;

	/**
	 * Constructor.
	 *
	 * @param ChartRenderer $renderer Chart output.
	 */
	public function __construct( ChartRenderer $renderer ) {
		$this->renderer = $renderer;
	}

	/**
	 * Attach hooks.
	 *
	 * @return void
	 */
	public function register() {
		// After ChartRenderer registers the ECharts script the editor script depends on.
		add_action( 'init', array( $this, 'register_block' ), 20 );
		add_action( 'enqueue_block_editor_assets', array( $this, 'editor_data' ) );
	}

	/**
	 * Register the block from its built block.json. Skipped if the build is missing.
	 *
	 * @return void
	 */
	public function register_block() {
		$dir = EASY_CHARTS_DIR . 'build/blocks/chart';
		if ( ! is_readable( $dir . '/block.json' ) ) {
			return;
		}

		register_block_type( $dir, array( 'render_callback' => array( $this, 'render' ) ) );
		wp_set_script_translations( generate_block_asset_handle( self::NAME, 'editorScript' ), 'easy-charts', EASY_CHARTS_DIR . 'languages' );
	}

	/**
	 * Give the editor the settings every chart starts from, so its preview matches the front end.
	 *
	 * @return void
	 */
	public function editor_data() {
		$handle = generate_block_asset_handle( self::NAME, 'editorScript' );
		if ( ! wp_script_is( $handle, 'registered' ) ) {
			return;
		}

		$data = array(
			'baseSettings'   => SettingsResolver::base(),
			// For 1.x charts that keep their 1.x look (see V1Converter::look_settings()).
			'v1BaseSettings' => V1Converter::look_settings(),
			'strings'        => ChartRenderer::strings( false ),
		);

		wp_add_inline_script( $handle, 'window.easyChartsEditor = ' . wp_json_encode( $data, JSON_HEX_TAG | JSON_HEX_AMP ) . ';', 'before' );
	}

	/**
	 * Render the block.
	 *
	 * @param array $attributes Block attributes.
	 * @return string HTML.
	 */
	public function render( $attributes ) {
		$chart_id = isset( $attributes['chartId'] ) ? absint( $attributes['chartId'] ) : 0;

		if ( $chart_id > 0 ) {
			$html = $this->renderer->render( $chart_id );
		} elseif ( isset( $attributes['chart'] ) ) {
			$html = $this->renderer->render_document( $attributes['chart'] );
		} else {
			$html = '';
		}

		if ( '' === $html ) {
			return '';
		}

		return sprintf( '<div %s>%s</div>', get_block_wrapper_attributes(), $html );
	}
}
