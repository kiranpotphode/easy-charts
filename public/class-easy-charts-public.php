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

		wp_register_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'css/easy-charts-public.css', array(), $this->version, 'all' );

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

		wp_register_script( 'easy-charts-public-js', plugin_dir_url( __FILE__ ) . 'js/easy-charts-public.js', array( 'jquery' ), $this->version, true );

		wp_register_script( 'd3-js', plugins_url( 'includes/js/d3.min.js', dirname( __FILE__ ) ), array( 'jquery' ), $this->version, false );

		wp_register_script( 'uvhcharts-js', plugins_url( 'includes/js/uvcharts.min.js', dirname( __FILE__ ) ), array( 'jquery' ), $this->version, false );

		wp_register_script( 'filesaver-js', plugins_url( 'includes/js/filesaver.js', dirname( __FILE__ ) ), array( 'jquery' ), $this->version, false );

		wp_register_script( 'canvg-js', plugins_url( 'includes/js/canvg.js', dirname( __FILE__ ) ), array( 'jquery' ), $this->version, false );

		wp_register_script( 'canvas-toblob-js', plugins_url( 'includes/js/canvas-toblob.js', dirname( __FILE__ ) ), array( 'jquery' ), $this->version, false );

	}

	/**
	 * Add shortcode callback for chart shortcode.
	 *
	 * @since 1.0.0
	 *
	 * @param string $atts    Attributes for shortcode.
	 * @param string $content Content inside Shortcode enclosing tags. Default is ''.
	 *
	 * @return string Parsed Shortcode html markup.
	 */
	public static function easy_chart_shortcode_callback( $atts, $content = '' ) {
		$atts = shortcode_atts( array(
			'chart_id' => null,
		), $atts, 'easy_chart' );

		$chart_id = $atts['chart_id'];

		if ( $chart_id ) {
			$plugin = new Easy_Charts();

			wp_enqueue_script( 'easy-charts-public-js' );

			wp_enqueue_script( 'd3-js' );

			wp_enqueue_script( 'uvhcharts-js' );

			wp_enqueue_script( 'filesaver-js' );

			wp_enqueue_script( 'canvg-js' );

			wp_enqueue_script( 'canvas-toblob-js' );

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
}
