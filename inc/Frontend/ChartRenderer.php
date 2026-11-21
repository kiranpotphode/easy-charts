<?php
/**
 * Front-end chart output.
 *
 * @package EasyCharts
 */

namespace EasyCharts\Frontend;

use EasyCharts\Assets;
use EasyCharts\Chart\ChartRepository;
use EasyCharts\Chart\Schema;
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
		$echarts  = Assets::get( 'echarts' );
		$frontend = Assets::get( 'frontend' );
		if ( null === $echarts || null === $frontend ) {
			return;
		}

		wp_register_script( self::SCRIPT_ECHARTS, Assets::url( 'echarts.js' ), $echarts['dependencies'], $echarts['version'], true );
		wp_register_script(
			self::SCRIPT_FRONTEND,
			Assets::url( 'frontend.js' ),
			array_merge( $frontend['dependencies'], array( self::SCRIPT_ECHARTS ) ),
			$frontend['version'],
			true
		);
		wp_register_style( self::STYLE_FRONTEND, Assets::url( 'frontend.css' ), array(), $frontend['version'] );
	}

	/**
	 * Render a chart.
	 *
	 * @param int $chart_id Chart post ID.
	 * @return string HTML; for a chart without usable data, a notice for its editors and nothing
	 *                for anyone else; an empty string if the chart may not be shown.
	 */
	public function render( $chart_id ) {
		$chart_id = (int) $chart_id;
		$doc      = $this->charts->find( $chart_id );
		if ( null === $doc ) {
			return $this->no_data_notice( $chart_id );
		}
		if ( ! $this->can_show( $chart_id ) ) {
			return '';
		}

		$settings = $this->settings->resolve( $doc );
		$html     = $this->markup( $doc, $settings, $this->title( $chart_id, $settings ), $chart_id );

		/**
		 * Filters the HTML of a chart post's chart.
		 *
		 * @since 1.0.3
		 * @since 2.0.0 The HTML is the ECharts figure with its data table, not the uvCharts container.
		 *
		 * @param string $html     Chart HTML.
		 * @param int    $chart_id Chart post ID.
		 */
		return apply_filters( 'easy_charts_render_chart', $html, $chart_id );
	}

	/**
	 * Render a chart that has no chart post, e.g. one stored in a block's attributes.
	 *
	 * The document is validated first: block attributes can be edited by anyone who can edit the
	 * post, so they are untrusted input.
	 *
	 * @param mixed $doc Chart document.
	 * @return string HTML, or an empty string if the document is invalid.
	 */
	public function render_document( $doc ) {
		$doc = is_array( $doc ) ? Schema::sanitize( $doc ) : null;
		if ( ! is_array( $doc ) ) {
			return '';
		}

		$settings = $this->settings->resolve( $doc );
		$title    = isset( $settings['title']['text'] ) && is_string( $settings['title']['text'] ) ? $settings['title']['text'] : '';

		return $this->markup( $doc, $settings, $title, 0 );
	}

	/**
	 * Strings the chart script needs, translated.
	 *
	 * @param bool $with_table Whether the chart is followed by its data table (front end), which
	 *                         its accessible description then points to.
	 * @return array
	 */
	public static function strings( $with_table = true ) {
		return array(
			'increase'      => __( 'Increase', 'easy-charts' ),
			'decrease'      => __( 'Decrease', 'easy-charts' ),
			'types'         => Schema::type_labels(),
			/* translators: 1: chart title, 2: chart type, e.g. "Stacked bar". */
			'chartLabel'    => __( '%1$s (%2$s chart).', 'easy-charts' ),
			/* translators: %s: chart type, e.g. "Stacked bar". */
			'untitledChart' => __( '%s chart.', 'easy-charts' ),
			'tableNote'     => $with_table ? __( 'The values are in the table that follows.', 'easy-charts' ) : '',
		);
	}

	/**
	 * Chart markup, and the assets to draw it.
	 *
	 * @param array  $doc      Valid chart document.
	 * @param array  $settings Resolved settings.
	 * @param string $title    Chart title.
	 * @param int    $chart_id Chart post ID, or 0 for a chart without a post.
	 * @return string HTML.
	 */
	private function markup( array $doc, array $settings, $title, $chart_id ) {
		$config = array(
			'type'     => $doc['type'],
			'data'     => $doc['data'],
			'settings' => $settings,
			'title'    => $title,
			'strings'  => self::strings(),
		);

		$html = sprintf(
			'<figure class="easy-charts-chart easy-charts-chart--%1$s"%2$s><div class="easy-charts-chart__canvas" style="%3$s"></div><script type="application/json" class="easy-charts-chart__config">%4$s</script>%5$s%6$s</figure>',
			esc_attr( $doc['type'] ),
			$chart_id > 0 ? sprintf( ' data-chart-id="%d"', $chart_id ) : '',
			esc_attr( $this->box_style( $settings ) ),
			wp_json_encode( $config, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT ),
			$this->download_button( $settings, $title ),
			$this->table( $doc['data'], $title )
		);

		$this->enqueue();

		return $html;
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
	 * Tell people who can edit a chart that it can't be drawn (no data, or 1.x data that can't be
	 * converted). Visitors get nothing: there is no data to show them.
	 *
	 * @param int $chart_id Chart post ID.
	 * @return string HTML.
	 */
	private function no_data_notice( $chart_id ) {
		if ( ! $this->charts->is_chart( $chart_id ) || 'trash' === get_post_status( $chart_id ) || ! current_user_can( 'edit_post', $chart_id ) ) {
			return '';
		}

		$title = (string) get_post_field( 'post_title', $chart_id, 'raw' );
		if ( '' === $title ) {
			/* translators: %d: chart post ID. */
			$title = sprintf( __( '#%d (no title)', 'easy-charts' ), $chart_id );
		}

		return sprintf(
			'<p class="easy-charts-notice">%1$s <a href="%2$s">%3$s</a></p>',
			esc_html(
				sprintf(
					/* translators: %s: chart title. */
					__( 'Easy Charts: the chart "%s" has no data that can be drawn, so visitors see nothing here. Only people who can edit the chart see this message.', 'easy-charts' ),
					$title
				)
			),
			esc_url( admin_url( 'post.php?post=' . $chart_id . '&action=edit' ) ),
			esc_html__( 'Edit chart', 'easy-charts' )
		);
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
	 * @param array  $settings Resolved settings.
	 * @param string $title    Chart title, for the button's accessible name.
	 * @return string HTML.
	 */
	private function download_button( array $settings, $title ) {
		if ( empty( $settings['download']['enabled'] ) ) {
			return '';
		}

		$label = isset( $settings['download']['label'] ) && '' !== $settings['download']['label']
			? $settings['download']['label']
			: __( 'Download', 'easy-charts' );

		// Several charts on a page would otherwise have identical "Download" buttons.
		$target = '' !== $title
			/* translators: %s: chart title. */
			? sprintf( __( 'PNG image of "%s"', 'easy-charts' ), $title )
			: __( 'PNG image of the chart', 'easy-charts' );

		return sprintf(
			'<button type="button" class="easy-charts-chart__download" hidden>%1$s<span class="easy-charts-chart__sr"> (%2$s)</span></button>',
			esc_html( $label ),
			esc_html( $target )
		);
	}

	/**
	 * Data table: visible without JavaScript, read by screen readers, indexed by search engines.
	 *
	 * The wrapper is what gets visually hidden once the chart draws: a table ignores `width`
	 * and `overflow`, so hiding the table itself leaves its full width and makes narrow pages
	 * scroll sideways.
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
			'<div class="easy-charts-chart__data"><table class="easy-charts-chart__table"><caption>%s</caption><thead><tr>%s</tr></thead><tbody>%s</tbody></table></div>',
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
}
