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

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in Easy_Charts_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Easy_Charts_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

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

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in Easy_Charts_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Easy_Charts_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

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
	 * @return void
	 */
	public function register_gutenberg_blocks() {
		register_block_type( EASY_CHARTS_PATH . '/build/blocks/easy-chart' );
	}

	/**
	 * Register rest route for the chart data.
	 *
	 * @return void
	 */
	public function register_rest_route() {
		register_rest_route(
			'easy-charts/v1',
			'/chart/(?P<id>\d+)/',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'get_chart_data_for_easy_charts' ),
				'permission_callback' => function ( WP_REST_Request $request ) {
					if ( ! wp_verify_nonce( $request->get_header( 'X-Easy-Charts-Fetch-Nonce' ), 'easy-charts-fetch-nonce' ) ) {
						return new WP_Error( 'rest_invalid_nonce', 'Invalid nonce', array( 'status' => 403 ) );
					}
					return current_user_can( 'read' );
				},
			)
		);
	}

	/**
	 * Get chart data by id.
	 *
	 * @param WP_REST_Request $request REST request.
	 *
	 * @return WP_Error|WP_HTTP_Response|WP_REST_Response
	 */
	public function get_chart_data_for_easy_charts( WP_REST_Request $request ) {
		$chart_id = intval( $request['id'] );

		// Replace this with your function to fetch chart data.
		$plugin = new Easy_Charts();

		$chart_data = $plugin->get_ec_chart_data( $chart_id );
		return rest_ensure_response( $chart_data );
	}
}
