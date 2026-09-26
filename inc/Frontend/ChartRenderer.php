<?php
/**
 * Front-end chart output.
 *
 * @package EasyCharts
 */

namespace EasyCharts\Frontend;

use EasyCharts\Chart\ChartRepository;
use EasyCharts\Service;
use EasyCharts\Settings\SettingsResolver;

/**
 * Renders a chart as a <figure> with a chart box, its JSON config and an accessible data table,
 * and registers the scripts that draw it with ECharts.
 */
final class ChartRenderer implements Service {

	const SCRIPT_ECHARTS  = 'easy-charts-echarts';
	const SCRIPT_FRONTEND = 'easy-charts-frontend';
	const STYLE_FRONTEND  = 'easy-charts-frontend';

	/**
	 * Chart storage.
	 *
	 * @var ChartRepository
	 */
	private $charts;

	/**
	 * Settings layering.
	 *
	 * @var SettingsResolver
	 */
	private $settings;

	/**
	 * Constructor.
	 *
	 * @param ChartRepository  $charts   Chart storage.
	 * @param SettingsResolver $settings Settings layering.
	 */
	public function __construct( ChartRepository $charts, SettingsResolver $settings ) {
		$this->charts   = $charts;
		$this->settings = $settings;
	}

	/**
	 * Attach hooks.
	 *
	 * @return void
	 */
	public function register() {
		add_action( 'init', array( $this, 'register_assets' ) );
	}

	/**
	 * Register the built scripts and style. Skipped (with a data-table-only fallback) if the build is missing.
	 *
	 * @return void
	 */
	public function register_assets() {
		$echarts  = $this->asset( 'echarts' );
		$frontend = $this->asset( 'frontend' );
		if ( null === $echarts || null === $frontend ) {
			return;
		}

		$build_url = plugins_url( 'build/', EASY_CHARTS_FILE );

		wp_register_script( self::SCRIPT_ECHARTS, $build_url . 'echarts.js', $echarts['dependencies'], $echarts['version'], true );
		wp_register_script(
			self::SCRIPT_FRONTEND,
			$build_url . 'frontend.js',
			array_merge( $frontend['dependencies'], array( self::SCRIPT_ECHARTS ) ),
			$frontend['version'],
			true
		);
		wp_register_style( self::STYLE_FRONTEND, $build_url . 'frontend.css', array(), $frontend['version'] );
	}

	/**
	 * Render a chart.
	 *
	 * @param int $chart_id Chart post ID.
	 * @return string HTML, or an empty string if the chart has no v2 document or may not be shown.
	 */
	public function render( $chart_id ) {
		$chart_id = (int) $chart_id;
		$doc      = $this->charts->find( $chart_id );
		if ( null === $doc || ! $this->can_show( $chart_id ) ) {
			return '';
		}

		$settings = $this->settings->resolve( $doc );
		$title    = $this->title( $chart_id, $settings );
		$config   = array(
			'type'     => $doc['type'],
			'data'     => $doc['data'],
			'settings' => $settings,
			'title'    => $title,
			'strings'  => array(
				'increase' => __( 'Increase', 'easy-charts' ),
				'decrease' => __( 'Decrease', 'easy-charts' ),
			),
		);

		$html = sprintf(
			'<figure class="easy-charts-chart easy-charts-chart--%1$s" data-chart-id="%2$d"><div class="easy-charts-chart__canvas" style="%3$s"></div><script type="application/json" class="easy-charts-chart__config">%4$s</script>%5$s%6$s</figure>',
			esc_attr( $doc['type'] ),
			$chart_id,
			esc_attr( $this->box_style( $settings ) ),
			wp_json_encode( $config, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT ),
			$this->download_button( $settings ),
			$this->table( $doc['data'], $title )
		);

		$this->enqueue();

		/** This filter is documented in includes/class-easy-charts.php (1.x). */
		return apply_filters( 'easy_charts_render_chart', $html, $chart_id );
	}

	/**
	 * Private and trashed charts are only shown to users who can read them. Other statuses render as in 1.x.
	 *
	 * @param int $chart_id Chart post ID.
	 * @return bool
	 */
	private function can_show( $chart_id ) {
		$status = get_post_status( $chart_id );
		if ( in_array( $status, array( 'private', 'trash' ), true ) ) {
			return current_user_can( 'read_post', $chart_id );
		}

		return false !== $status;
	}

	/**
	 * Chart title: the custom `title.text` setting, or else the post title.
	 *
	 * Raw post title: ECharts draws plain text (get_the_title() would add HTML entities); the table
	 * escapes it.
	 *
	 * @param int   $chart_id Chart post ID.
	 * @param array $settings Resolved settings.
	 * @return string
	 */
	private function title( $chart_id, array $settings ) {
		$custom = isset( $settings['title']['text'] ) && is_string( $settings['title']['text'] ) ? $settings['title']['text'] : '';

		return '' !== $custom ? $custom : (string) get_post_field( 'post_title', $chart_id, 'raw' );
	}

	/**
	 * Inline size of the chart box.
	 *
	 * @param array $settings Resolved settings.
	 * @return string CSS declarations.
	 */
	private function box_style( array $settings ) {
		$width  = isset( $settings['size']['width'] ) ? max( 100, (int) $settings['size']['width'] ) : 400;
		$height = isset( $settings['size']['height'] ) ? max( 100, (int) $settings['size']['height'] ) : 400;

		$style = sprintf( 'height:%dpx;', $height );
		if ( empty( $settings['size']['responsive'] ) ) {
			$style .= sprintf( 'width:%dpx;', $width );
		}

		return $style;
	}

	/**
	 * Download button, hidden until the script enables it.
	 *
	 * @param array $settings Resolved settings.
	 * @return string HTML.
	 */
	private function download_button( array $settings ) {
		if ( empty( $settings['download']['enabled'] ) ) {
			return '';
		}

		$label = isset( $settings['download']['label'] ) && '' !== $settings['download']['label']
			? $settings['download']['label']
			: __( 'Download', 'easy-charts' );

		return sprintf( '<button type="button" class="easy-charts-chart__download" hidden>%s</button>', esc_html( $label ) );
	}

	/**
	 * Data table: visible without JavaScript, read by screen readers, indexed by search engines.
	 *
	 * @param array  $data  Data grid.
	 * @param string $title Chart title.
	 * @return string HTML.
	 */
	private function table( array $data, $title ) {
		$caption = '' !== $title ? $title : __( 'Chart data', 'easy-charts' );

		$head = '';
		foreach ( $data['columns'] as $index => $column ) {
			$head .= ( 0 === $index && '' === $column )
				? '<td></td>'
				: sprintf( '<th scope="col">%s</th>', esc_html( $column ) );
		}

		$body = '';
		foreach ( $data['rows'] as $row ) {
			$cells = '';
			foreach ( array_values( $row ) as $index => $cell ) {
				$text   = null === $cell ? '' : (string) $cell;
				$cells .= 0 === $index
					? sprintf( '<th scope="row">%s</th>', esc_html( $text ) )
					: sprintf( '<td>%s</td>', esc_html( $text ) );
			}
			$body .= '<tr>' . $cells . '</tr>';
		}

		return sprintf(
			'<table class="easy-charts-chart__table"><caption>%s</caption><thead><tr>%s</tr></thead><tbody>%s</tbody></table>',
			esc_html( $caption ),
			$head,
			$body
		);
	}

	/**
	 * Load the scripts and style for charts on this page.
	 *
	 * @return void
	 */
	private function enqueue() {
		if ( wp_script_is( self::SCRIPT_FRONTEND, 'registered' ) ) {
			wp_enqueue_script( self::SCRIPT_FRONTEND );
			wp_enqueue_style( self::STYLE_FRONTEND );
		}
	}

	/**
	 * Read a build/<name>.asset.php file.
	 *
	 * @param string $name Entry name.
	 * @return array|null Dependencies and version, or null if the build is missing.
	 */
	private function asset( $name ) {
		$file = EASY_CHARTS_DIR . 'build/' . $name . '.asset.php';
		if ( ! is_readable( $file ) ) {
			return null;
		}

		$asset = require $file;

		return is_array( $asset ) ? $asset : null;
	}
}
