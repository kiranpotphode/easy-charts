<?php
/**
 * The public-facing functionality of the plugin.
 *
 * @link       https://kiranpotphode.com/
 * @since      1.0.0
 *
 * @package    Easy_Charts
 * @subpackage Easy_Charts/public
 */

/**
 * The public-facing functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    Easy_Charts
 * @subpackage Easy_Charts/public
 * @author     Kiran Potphode <kiranpotphode15@gmail.com>
 */
class Easy_Charts_Public {

	/**
	 * The ID of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string $plugin_name The ID of this plugin.
	 */
	private $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string $version The current version of this plugin.
	 */
	private $version;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 *
	 * @param      string $plugin_name The name of the plugin.
	 * @param      string $version The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {

		$this->plugin_name = $plugin_name;
		$this->version     = $version;
	}

	/**
	 * Register the stylesheets for the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_styles() {

		wp_enqueue_style( $this->plugin_name, EASY_CHARTS_URL . '/build/css/frontend.css', array(), $this->version, 'all' );

		wp_localize_script(
			'easy-charts-easy-chart-view-script',
			'easyChartsSettings',
			array(
				'restBase' => esc_url_raw( rest_url() ),
				'nonce'    => wp_create_nonce( 'easy-charts-fetch-nonce' ),
			)
		);
	}

	/**
	 * Register the JavaScript for the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts() {

		wp_register_script( 'easy-charts-public-js', EASY_CHARTS_URL . '/build/js/frontend.js', array( 'jquery' ), $this->version, true );

		wp_localize_script(
			'easy-charts-easy-chart-view-script',
			'easyChartsSettings',
			array(
				'restBase' => esc_url_raw( rest_url() ),
				'nonce'    => wp_create_nonce( 'easy-charts-fetch-nonce' ),
			)
		);
	}

	/**
	 * Add shortcode callback for chart shortcode.
	 *
	 * @since 1.0.0
	 *
	 * @param string $atts    Attributes for shortcode.
	 *
	 * @return string Parsed Shortcode html markup.
	 */
	public static function easy_chart_shortcode_callback( $atts ) {
		$atts = shortcode_atts(
			array(
				'chart_id' => null,
			),
			$atts,
			'easy_chart'
		);

		$chart_id = intval( $atts['chart_id'] );

		if ( $chart_id ) {
			$plugin = new Easy_Charts();

			wp_enqueue_script( 'easy-charts-public-js' );

			return $plugin->ec_render_chart( $chart_id );
		}

		return '';
	}

	/**
	 * Register shortcode on init hook.
	 *
	 * @since 1.0.0
	 */
	public function init() {
		add_shortcode( 'easy_chart', array( $this, 'easy_chart_shortcode_callback' ) );
	}

	/**
	 * Register Gutenberg Block.
	 *
	 * @since 2.0.0
	 *
	 * @return void
	 */
	public function register_gutenberg_blocks() {
		register_block_type( EASY_CHARTS_PATH . '/build/blocks/easy-chart' );
	}
}
