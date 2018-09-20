<?php
/**
 * The admin-specific functionality of the plugin.
 *
 * @link       https://kiranpotphode.com/
 * @since      1.0.0
 *
 * @package    Easy_Charts
 * @subpackage Easy_Charts/admin
 */

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    Easy_Charts
 * @subpackage Easy_Charts/admin
 * @author     Kiran Potphode <kiranpotphode15@gmail.com>
 */
class Easy_Charts_Admin {

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
	 * @todo    Move ajax action to separate location
	 *
	 * @since    1.0.0
	 *
	 * @param string $plugin_name The name of this plugin.
	 * @param string $version     The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {

		$this->plugin_name = $plugin_name;
		$this->version     = $version;

	}

	/**
	 * Register the stylesheets for the admin area.
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

		global $pagenow, $typenow;

		wp_enqueue_style( 'insert-chart-button-tc-css', plugin_dir_url( __FILE__ ) . 'css/insert-chart.css', array(), $this->version, 'all' );

		if ( 'post-new.php' === $pagenow || 'post.php' === $pagenow && 'easy_charts' === $typenow ) {
			wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'css/easy-charts-admin.css', array(), $this->version, 'all' );

			wp_enqueue_style( 'handsontable-css', plugin_dir_url( __FILE__ ) . 'css/handsontable/handsontable.full.css', array(), $this->version, 'all' );

			wp_enqueue_style( 'jquery-ui-css', plugin_dir_url( __FILE__ ) . 'css/jquery-ui.min.css', array(), $this->version, 'all' );

			wp_enqueue_style( 'responsive-tabs-css', plugin_dir_url( __FILE__ ) . 'css/jquery.pwstabs.min.css', array(), $this->version, 'all' );

			wp_enqueue_style( 'font-awesome-css', plugin_dir_url( __FILE__ ) . 'css/font-awesome.min.css', array(), $this->version, 'all' );

			wp_enqueue_style( 'wp-color-picker' );
		}

	}

	/**
	 * Register the JavaScript for the admin area.
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

		global $pagenow, $typenow;

		if ( 'post-new.php' === $pagenow || 'post.php' === $pagenow && 'easy_charts' === $typenow ) {

			wp_enqueue_script( 'easy-charts-admin-js', plugin_dir_url( __FILE__ ) . 'js/easy-charts-admin.js', array( 'jquery' ), $this->version, true );

			wp_enqueue_script( 'handsontable-js', plugin_dir_url( __FILE__ ) . 'js/handsontable/handsontable.full.js', array( 'jquery' ), $this->version, false );
			wp_enqueue_script( 'd3-js', plugins_url( 'includes/js/d3.min.js', dirname( __FILE__ ) ), array( 'jquery' ), $this->version, false );

			wp_enqueue_script( 'filesaver-js', plugins_url( 'includes/js/filesaver.js', dirname( __FILE__ ) ), array( 'jquery' ), $this->version, false );

			wp_enqueue_script( 'canvg-js', plugins_url( 'includes/js/canvg.js', dirname( __FILE__ ) ), array( 'jquery' ), $this->version, false );

			wp_enqueue_script( 'canvas-toblob-js', plugins_url( 'includes/js/canvas-toblob.js', dirname( __FILE__ ) ), array( 'jquery' ), $this->version, false );

			wp_enqueue_script( 'uvhcharts-js', plugins_url( 'includes/js/uvcharts.min.js', dirname( __FILE__ ) ), array( 'jquery' ), $this->version, false );

			wp_enqueue_script( 'responsive-tabs-js', plugin_dir_url( __FILE__ ) . 'js/jquery.pwstabs.min.js', array( 'jquery' ), $this->version, false );

			wp_enqueue_script( 'bootstrap-touchspin-js', plugin_dir_url( __FILE__ ) . 'js/jquery.bootstrap-touchspin.min.js', array( 'jquery' ), $this->version, false );

			wp_enqueue_script( 'jquery-ui-dialog' );
			wp_enqueue_script( 'jquery-ui-button' );
			wp_enqueue_script( 'jquery-ui-slider' );
			wp_enqueue_script( 'jquery-ui-selectmenu' );
			wp_enqueue_script( 'jquery-ui-spinner' );
			wp_enqueue_script( 'iris' );
			wp_enqueue_script( 'wp-color-picker' );
		}

	}

	/**
	 * Add ajaxurl variable to use in javascript.
	 */
	public function admin_print_scripts() {
		echo "<script type='text/javascript'>\n";
		echo 'var ajaxurl = "' . esc_url( admin_url( 'admin-ajax.php' ) ) . '"';
		echo "\n</script>";
	}

	/**
	 * Init actions.
	 *
	 *  This function does all actions hooked on "init" action.
	 *
	 * @since    1.0.0
	 */
	public function init() {

		$labels = array(
			'name'               => _x( 'Easy Charts', 'Post type general name', 'easy-charts' ),
			'singular_name'      => _x( 'Chart', 'Post type singular name', 'easy-charts' ),
			'menu_name'          => _x( 'Easy Charts', 'Admin menu', 'easy-charts' ),
			'name_admin_bar'     => _x( 'Chart', 'Add new chart on admin bar', 'easy-charts' ),
			'add_new'            => _x( 'Add New', 'Add new chart', 'easy-charts' ),
			'add_new_item'       => __( 'Add New Chart', 'easy-charts' ),
			'new_item'           => __( 'New Chart', 'easy-charts' ),
			'edit_item'          => __( 'Edit Chart', 'easy-charts' ),
			'view_item'          => __( 'View Chart', 'easy-charts' ),
			'all_items'          => __( 'All Charts', 'easy-charts' ),
			'search_items'       => __( 'Search Charts', 'easy-charts' ),
			'parent_item_colon'  => __( 'Parent Charts:', 'easy-charts' ),
			'not_found'          => __( 'No Charts found.', 'easy-charts' ),
			'not_found_in_trash' => __( 'No Charts found in Trash.', 'easy-charts' ),
		);

		$args = array(
			'labels'          => $labels,
			'public'          => false,
			'show_ui'         => true,
			'_builtin'        => false,
			'capability_type' => 'page',
			'hierarchical'    => true,
			'menu_icon'       => 'dashicons-chart-bar',
			'rewrite'         => false,
			'query_var'       => 'easy_charts',
			'supports'        => array(
				'title',
			),
		);

		register_post_type( 'easy_charts', $args );

		add_action( 'wp_ajax_easy_charts_save_chart_data', array( $this, 'easy_charts_save_chart_data_callback' ) );
		add_action( 'wp_ajax_easy_charts_get_published_charts', array( $this, 'easy_charts_get_published_charts_callback' ) );
	}

	/**
	 * Adds meta box container.
	 *
	 * @since 1.0.0
	 */
	public function add_meta_boxes() {

		$screens = array( 'easy_charts' );

		foreach ( $screens as $screen ) {

			add_meta_box(
				'easy_charts_data_metabox',
				__( 'Data', 'easy-charts' ),
				array( $this, 'easy_charts_data_metabox_callback' ),
				$screen
			);

			add_meta_box(
				'easy_charts_preview_metabox',
				__( 'Preview', 'easy-charts' ),
				array( $this, 'easy_charts_preview_metabox_callback' ),
				$screen
			);

			add_meta_box(
				'easy_charts_configuration_metabox',
				__( 'Configuration', 'easy-charts' ),
				array( $this, 'easy_charts_configuration_metabox_callback' ),
				$screen
			);

			add_meta_box(
				'easy_charts_shortcode_metabox',
				__( 'Shortcode', 'easy-charts' ),
				array( $this, 'easy_charts_shortcode_metabox_callback' ),
				$screen,
				'side'
			);

		}

	}

	/**
	 * Add settings menu to easy charts.
	 *
	 * @since 1.0.0
	 */
	public function add_options_menu() {
		add_submenu_page( 'edit.php?post_type=easy_charts', __( 'Charts settings', 'easy-charts' ), __( 'Charts settings', 'easy-charts' ), 'manage_options', 'easy-charts-settings', array( $this, 'easy_charts_settings_page_callback' ) );
	}

	/**
	 * Display settings page.
	 *
	 * @since 1.0.0
	 */
	public function easy_charts_settings_page_callback() {
		require_once plugin_dir_path( __FILE__ ) . 'partials/easy-charts-settings-page-display.php';
	}

	/**
	 * Render Data Meta Box content.
	 *
	 * @param WP_Post $post The post object.
	 *
	 * @since    1.0.0
	 */
	public function easy_charts_data_metabox_callback( $post ) {

		// Add an nonce field so we can check for it later.
		wp_nonce_field( 'easy_charts_save_meta_box_data', 'easy_charts_meta_box_nonce' );
		require_once plugin_dir_path( __FILE__ ) . 'partials/easy-charts-data-metabox-display.php';

	}

	/**
	 * Render Shortcode Meta Box content.
	 *
	 * @param WP_Post $post The post object.
	 *
	 * @since    1.0.0
	 */
	public function easy_charts_shortcode_metabox_callback( $post ) {
		require_once plugin_dir_path( __FILE__ ) . 'partials/easy-charts-shortcode-metabox-display.php';
	}

	/**
	 * Render Shortcode Meta Box content.
	 *
	 * @param WP_Post $post The post object.
	 *
	 * @since    1.0.0
	 */
	public function easy_charts_preview_metabox_callback( $post ) {
		require_once plugin_dir_path( __FILE__ ) . 'partials/easy-charts-preview-metabox-display.php';
	}


	/**
	 * Render Shortcode Meta Box content.
	 *
	 * @param WP_Post $post The post object.
	 *
	 * @since    1.0.0
	 */
	public function easy_charts_configuration_metabox_callback( $post ) {
		require_once plugin_dir_path( __FILE__ ) . 'partials/easy-charts-configuration-metabox-display.php';
	}

	/**
	 * When the post is saved, saves our custom data.
	 *
	 * @param int $post_id The ID of the post being saved.
	 */
	public function easy_charts_save_meta_box_data( $post_id ) {

		// Check if our nonce is set.
		if ( ! isset( $_POST['easy_charts_meta_box_nonce'] ) ) {
			return;
		}

		// Verify that the nonce is valid.
		if ( ! wp_verify_nonce( $_POST['easy_charts_meta_box_nonce'], 'easy_charts_save_meta_box_data' ) ) {
			return;
		}

		// If this is an autosave, our form has not been submitted, so we don't want to do anything.
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		// Check the user's permissions.
		if ( isset( $_POST['post_type'] ) && 'easy_charts' === $_POST['post_type'] ) {

			if ( ! current_user_can( 'edit_page', $post_id ) ) {
				return;
			}
		} else {

			if ( ! current_user_can( 'edit_post', $post_id ) ) {
				return;
			}
		}

		$ec_chart_graph = array(
			'responsive'  => (boolean) filter_input( INPUT_POST, 'ec_chart_graph_responsive', FILTER_SANITIZE_NUMBER_INT ),
			'palette'     => filter_input( INPUT_POST, 'ec_chart_graph_palette', FILTER_SANITIZE_STRING ),
			'bgcolor'     => filter_input( INPUT_POST, 'ec_chart_graph_bgcolor', FILTER_SANITIZE_STRING ),
			'orientation' => filter_input( INPUT_POST, 'ec_chart_graph_orientation', FILTER_SANITIZE_STRING ),
			'opacity'     => (float) filter_input( INPUT_POST, 'ec_chart_graph_opacity', FILTER_SANITIZE_STRING ),
		);

		$ec_chart_meta = array(
			'position'       => filter_input( INPUT_POST, 'ec_chart_meta_position', FILTER_SANITIZE_STRING ),
			'caption'        => filter_input( INPUT_POST, 'ec_chart_meta_caption', FILTER_SANITIZE_STRING ),
			'subcaption'     => filter_input( INPUT_POST, 'ec_chart_meta_subcaption', FILTER_SANITIZE_STRING ),
			'hlabel'         => filter_input( INPUT_POST, 'ec_chart_meta_hlabel', FILTER_SANITIZE_STRING ),
			'hsublabel'      => filter_input( INPUT_POST, 'ec_chart_meta_hsublabel', FILTER_SANITIZE_STRING ),
			'vlabel'         => filter_input( INPUT_POST, 'ec_chart_meta_vlabel', FILTER_SANITIZE_STRING ),
			'vsublabel'      => filter_input( INPUT_POST, 'ec_chart_meta_vsublabel', FILTER_SANITIZE_STRING ),
			'isDownloadable' => (integer) filter_input( INPUT_POST, 'ec_chart_meta_isDownloadable', FILTER_SANITIZE_NUMBER_INT ),
			'downloadLabel'  => filter_input( INPUT_POST, 'ec_chart_meta_downloadLabel', FILTER_SANITIZE_STRING ),
		);

		$ec_chart_dimension = array(
			'width'  => (integer) filter_input( INPUT_POST, 'ec_chart_dimension_width', FILTER_SANITIZE_NUMBER_INT ),
			'height' => (integer) filter_input( INPUT_POST, 'ec_chart_dimension_height', FILTER_SANITIZE_NUMBER_INT ),
		);

		$ec_chart_margin = array(
			'top'    => (integer) filter_input( INPUT_POST, 'ec_chart_margin_top', FILTER_SANITIZE_NUMBER_INT ),
			'bottom' => (integer) filter_input( INPUT_POST, 'ec_chart_margin_bottom', FILTER_SANITIZE_NUMBER_INT ),
			'left'   => (integer) filter_input( INPUT_POST, 'ec_chart_margin_left', FILTER_SANITIZE_NUMBER_INT ),
			'right'  => (integer) filter_input( INPUT_POST, 'ec_chart_margin_right', FILTER_SANITIZE_NUMBER_INT ),
		);

		$ec_chart_frame = array(
			'bgcolor' => filter_input( INPUT_POST, 'ec_chart_frame_bgcolor', FILTER_SANITIZE_STRING ),
		);

		$ec_chart_axis = array(
			'opacity'      => (float) filter_input( INPUT_POST, 'ec_chart_axis_opacity', FILTER_SANITIZE_STRING ),
			'ticks'        => (integer) filter_input( INPUT_POST, 'ec_chart_axis_ticks', FILTER_SANITIZE_NUMBER_INT ),
			'subticks'     => (integer) filter_input( INPUT_POST, 'ec_chart_axis_subticks', FILTER_SANITIZE_NUMBER_INT ),
			'padding'      => (integer) filter_input( INPUT_POST, 'ec_chart_axis_padding', FILTER_SANITIZE_NUMBER_INT ),
			'strokecolor'  => filter_input( INPUT_POST, 'ec_chart_axis_strokecolor', FILTER_SANITIZE_STRING ),
			'minor'        => (integer) filter_input( INPUT_POST, 'ec_chart_axis_minor', FILTER_SANITIZE_NUMBER_INT ),
			'fontfamily'   => filter_input( INPUT_POST, 'ec_chart_axis_fontfamily', FILTER_SANITIZE_STRING ),
			'fontsize'     => filter_input( INPUT_POST, 'ec_chart_axis_fontsize', FILTER_SANITIZE_STRING ),
			'fontweight'   => filter_input( INPUT_POST, 'ec_chart_axis_fontweight', FILTER_SANITIZE_STRING ),
			'showticks'    => (integer) filter_input( INPUT_POST, 'ec_chart_axis_showticks', FILTER_SANITIZE_NUMBER_INT ),
			'showsubticks' => (integer) filter_input( INPUT_POST, 'ec_chart_axis_showsubticks', FILTER_SANITIZE_NUMBER_INT ),
			'showtext'     => (integer) filter_input( INPUT_POST, 'ec_chart_axis_showtext', FILTER_SANITIZE_NUMBER_INT ),
		);

		$ec_chart_label = array(
			'strokecolor' => filter_input( INPUT_POST, 'ec_chart_label_strokecolor', FILTER_SANITIZE_STRING ),
			'fontfamily'  => filter_input( INPUT_POST, 'ec_chart_label_fontfamily', FILTER_SANITIZE_STRING ),
			'fontsize'    => filter_input( INPUT_POST, 'ec_chart_label_fontsize', FILTER_SANITIZE_STRING ),
			'fontweight'  => filter_input( INPUT_POST, 'ec_chart_label_fontweight', FILTER_SANITIZE_STRING ),
			'showlabel'   => (integer) filter_input( INPUT_POST, 'ec_chart_label_showlabel', FILTER_SANITIZE_NUMBER_INT ),
			'precision'   => (integer) filter_input( INPUT_POST, 'ec_chart_label_precision', FILTER_SANITIZE_NUMBER_INT ),
			'prefix'      => filter_input( INPUT_POST, 'ec_chart_label_prefix', FILTER_SANITIZE_STRING ),
			'suffix'      => filter_input( INPUT_POST, 'ec_chart_label_suffix', FILTER_SANITIZE_STRING ),
		);

		$ec_chart_legend = array(
			'position'      => filter_input( INPUT_POST, 'ec_chart_legend_position', FILTER_SANITIZE_STRING ),
			'fontfamily'    => filter_input( INPUT_POST, 'ec_chart_legend_fontfamily', FILTER_SANITIZE_STRING ),
			'fontsize'      => filter_input( INPUT_POST, 'ec_chart_legend_fontsize', FILTER_SANITIZE_STRING ),
			'fontweight'    => filter_input( INPUT_POST, 'ec_chart_legend_fontweight', FILTER_SANITIZE_STRING ),
			'color'         => filter_input( INPUT_POST, 'ec_chart_legend_color', FILTER_SANITIZE_STRING ),
			'strokewidth'   => (float) filter_input( INPUT_POST, 'ec_chart_legend_strokewidth', FILTER_SANITIZE_STRING ),
			'textmargin'    => (integer) filter_input( INPUT_POST, 'ec_chart_legend_textmargin', FILTER_SANITIZE_NUMBER_INT ),
			'symbolsize'    => (integer) filter_input( INPUT_POST, 'ec_chart_legend_symbolsize', FILTER_SANITIZE_NUMBER_INT ),
			'inactivecolor' => filter_input( INPUT_POST, 'ec_chart_legend_inactivecolor', FILTER_SANITIZE_STRING ),
			'legendstart'   => (integer) filter_input( INPUT_POST, 'ec_chart_legend_legendstart', FILTER_SANITIZE_NUMBER_INT ),
			'legendtype'    => filter_input( INPUT_POST, 'ec_chart_legend_type', FILTER_SANITIZE_STRING ),
			'showlegends'   => (integer) filter_input( INPUT_POST, 'ec_chart_legend_showlegends', FILTER_SANITIZE_NUMBER_INT ),
		);

		$ec_chart_scale = array(
			'type'       => filter_input( INPUT_POST, 'ec_chart_scale_type', FILTER_SANITIZE_STRING ),
			'ordinality' => (float) filter_input( INPUT_POST, 'ec_chart_scale_ordinality', FILTER_SANITIZE_STRING ),
		);

		$ec_chart_tooltip = array(
			'show'   => (integer) filter_input( INPUT_POST, 'ec_chart_tooltip_show', FILTER_SANITIZE_NUMBER_INT ),
			'format' => filter_input( INPUT_POST, 'ec_chart_tooltip_format', FILTER_SANITIZE_STRING ),
		);

		$ec_chart_caption = array(
			'fontfamily'     => filter_input( INPUT_POST, 'ec_chart_caption_fontfamily', FILTER_SANITIZE_STRING ),
			'fontsize'       => filter_input( INPUT_POST, 'ec_chart_caption_fontsize', FILTER_SANITIZE_STRING ),
			'fontweight'     => filter_input( INPUT_POST, 'ec_chart_caption_fontweight', FILTER_SANITIZE_STRING ),
			'textdecoration' => filter_input( INPUT_POST, 'ec_chart_caption_textdecoration', FILTER_SANITIZE_STRING ),
			'strokecolor'    => filter_input( INPUT_POST, 'ec_chart_caption_strokecolor', FILTER_SANITIZE_STRING ),
			'cursor'         => filter_input( INPUT_POST, 'ec_chart_caption_cursor', FILTER_SANITIZE_STRING ),
		);

		$ec_chart_subcaption = array(
			'fontfamily'     => filter_input( INPUT_POST, 'ec_chart_subcaption_fontfamily', FILTER_SANITIZE_STRING ),
			'fontsize'       => filter_input( INPUT_POST, 'ec_chart_subcaption_fontsize', FILTER_SANITIZE_STRING ),
			'fontweight'     => filter_input( INPUT_POST, 'ec_chart_subcaption_fontweight', FILTER_SANITIZE_STRING ),
			'textdecoration' => filter_input( INPUT_POST, 'ec_chart_subcaption_textdecoration', FILTER_SANITIZE_STRING ),
			'strokecolor'    => filter_input( INPUT_POST, 'ec_chart_subcaption_strokecolor', FILTER_SANITIZE_STRING ),
			'cursor'         => filter_input( INPUT_POST, 'ec_chart_subcaption_cursor', FILTER_SANITIZE_STRING ),
		);

		$ec_chart_bar = array(
			'fontfamily'  => filter_input( INPUT_POST, 'ec_chart_bar_fontfamily', FILTER_SANITIZE_STRING ),
			'fontsize'    => filter_input( INPUT_POST, 'ec_chart_bar_fontsize', FILTER_SANITIZE_STRING ),
			'fontweight'  => filter_input( INPUT_POST, 'ec_chart_bar_fontweight', FILTER_SANITIZE_STRING ),
			'strokecolor' => filter_input( INPUT_POST, 'ec_chart_bar_strokecolor', FILTER_SANITIZE_STRING ),
			'textcolor'   => filter_input( INPUT_POST, 'ec_chart_bar_textcolor', FILTER_SANITIZE_STRING ),
		);

		$ec_chart_line = array(
			'interpolation' => filter_input( INPUT_POST, 'ec_chart_line_interpolation', FILTER_SANITIZE_STRING ),
		);

		$ec_chart_area = array(
			'interpolation' => filter_input( INPUT_POST, 'ec_chart_area_interpolation', FILTER_SANITIZE_STRING ),
			'opacity'       => (float) filter_input( INPUT_POST, 'ec_chart_area_opacity', FILTER_SANITIZE_STRING ),
			'offset'        => filter_input( INPUT_POST, 'ec_chart_area_offset', FILTER_SANITIZE_STRING ),
		);

		$ec_chart_pie = array(
			'fontfamily'  => filter_input( INPUT_POST, 'ec_chart_pie_fontfamily', FILTER_SANITIZE_STRING ),
			'fontsize'    => filter_input( INPUT_POST, 'ec_chart_pie_fontsize', FILTER_SANITIZE_STRING ),
			'fontweight'  => filter_input( INPUT_POST, 'ec_chart_pie_fontweight', FILTER_SANITIZE_STRING ),
			'fontvariant' => filter_input( INPUT_POST, 'ec_chart_pie_fontvariant', FILTER_SANITIZE_STRING ),
			'fontfill'    => filter_input( INPUT_POST, 'ec_chart_pie_fontfill', FILTER_SANITIZE_STRING ),
			'strokecolor' => filter_input( INPUT_POST, 'ec_chart_pie_strokecolor', FILTER_SANITIZE_STRING ),
			'strokewidth' => filter_input( INPUT_POST, 'ec_chart_pie_strokewidth', FILTER_SANITIZE_STRING ),
		);

		$ec_chart_donut = array(
			'fontfamily'  => filter_input( INPUT_POST, 'ec_chart_donut_fontfamily', FILTER_SANITIZE_STRING ),
			'fontsize'    => filter_input( INPUT_POST, 'ec_chart_donut_fontsize', FILTER_SANITIZE_STRING ),
			'fontweight'  => filter_input( INPUT_POST, 'ec_chart_donut_fontweight', FILTER_SANITIZE_STRING ),
			'fontvariant' => filter_input( INPUT_POST, 'ec_chart_donut_fontvariant', FILTER_SANITIZE_STRING ),
			'fontfill'    => filter_input( INPUT_POST, 'ec_chart_donut_fontfill', FILTER_SANITIZE_STRING ),
			'strokecolor' => filter_input( INPUT_POST, 'ec_chart_donut_strokecolor', FILTER_SANITIZE_STRING ),
			'strokewidth' => filter_input( INPUT_POST, 'ec_chart_donut_strokewidth', FILTER_SANITIZE_STRING ),
			'factor'      => filter_input( INPUT_POST, 'ec_chart_donut_factor', FILTER_SANITIZE_STRING ),
		);

		update_post_meta( $post_id, '_ec_chart_type', filter_input( INPUT_POST, 'ec_chart_type', FILTER_SANITIZE_STRING ) );
		update_post_meta( $post_id, '_ec_chart_meta', $ec_chart_meta );
		update_post_meta( $post_id, '_ec_chart_graph', $ec_chart_graph );
		update_post_meta( $post_id, '_ec_chart_dimension', $ec_chart_dimension );
		update_post_meta( $post_id, '_ec_chart_margin', $ec_chart_margin );
		update_post_meta( $post_id, '_ec_chart_frame', $ec_chart_frame );
		update_post_meta( $post_id, '_ec_chart_axis', $ec_chart_axis );
		update_post_meta( $post_id, '_ec_chart_label', $ec_chart_label );
		update_post_meta( $post_id, '_ec_chart_legend', $ec_chart_legend );
		update_post_meta( $post_id, '_ec_chart_scale', $ec_chart_scale );
		update_post_meta( $post_id, '_ec_chart_tooltip', $ec_chart_tooltip );
		update_post_meta( $post_id, '_ec_chart_caption', $ec_chart_caption );
		update_post_meta( $post_id, '_ec_chart_subcaption', $ec_chart_subcaption );
		update_post_meta( $post_id, '_ec_chart_bar', $ec_chart_bar );
		update_post_meta( $post_id, '_ec_chart_line', $ec_chart_line );
		update_post_meta( $post_id, '_ec_chart_area', $ec_chart_area );
		update_post_meta( $post_id, '_ec_chart_pie', $ec_chart_pie );
		update_post_meta( $post_id, '_ec_chart_donut', $ec_chart_donut );

	}


	/**
	 * Ajax callback for save chart data.
	 *
	 * @since    1.0.0
	 */
	public function easy_charts_save_chart_data_callback() {

		$plugin = new Easy_Charts();

		check_ajax_referer( 'ec-ajax-nonce', '_nonce_check' );

		if ( 'easy_charts_save_chart_data' !== filter_input( INPUT_POST, 'action', FILTER_SANITIZE_STRING ) ) {
			exit( 0 );
		}

		$chart_id = filter_input( INPUT_POST, 'chart_id', FILTER_SANITIZE_NUMBER_INT );

		update_post_meta( $chart_id, '_easy_charts_chart_data', filter_input( INPUT_POST, 'chart_data', FILTER_UNSAFE_RAW ) );

		echo wp_json_encode( $plugin->get_ec_chart_data( $chart_id ) );

		exit( 0 );
	}

	/**
	 * Get published charts.
	 *
	 * @since 1.0.0
	 */
	public function easy_charts_get_published_charts_callback() {
		if ( 'easy_charts_get_published_charts' !== filter_input( INPUT_POST, 'action', FILTER_SANITIZE_STRING ) ) {
			exit( 0 );
		}

		$args = array(
			'post_type'      => 'easy_charts',
			'post_status'    => 'publish',
			'posts_per_page' => - 1,
		);

		$chart_query = new WP_Query( $args );

		$charts = array();
		if ( $chart_query->have_posts() ) {
			foreach ( $chart_query->posts as $chart_key => $chart ) {
				$chart_title = '';
				if ( '' === $chart->post_title ) {
					$chart_title = 'Chart-' . $chart->ID;
				} else {
					$chart_title = $chart->post_title;
				}
				$charts[] = array(
					'text'  => $chart_title,
					'value' => "[easy_chart chart_id='" . $chart->ID . "']",
				);
			}
		}
		wp_reset_postdata();

		echo wp_json_encode( $charts );

		exit( 0 );
	}

	/**
	 * Add insert chart button to editor.
	 *
	 * @since 1.0.0
	 */
	public function easy_charts_add_insert_chart_button() {

		// check user permissions.
		if ( ! current_user_can( 'edit_posts' ) && ! current_user_can( 'edit_pages' ) ) {
			return;
		}

		// check if WYSIWYG is enabled.
		if ( get_user_option( 'rich_editing' ) === 'true' ) {
			add_filter( 'mce_external_plugins', array( $this, 'easy_charts_add_tinymce_plugin' ) );
			add_filter( 'mce_buttons', array( $this, 'easy_charts_register_insert_chart_tc_button' ) );
		}
	}

	/**
	 * Add Insert chart button js.
	 *
	 * @since 1.0.0
	 *
	 * @param    array $plugin_array Array of plugins urls.
	 *
	 * @return  array     Array of plugin urls with newly added plugin.
	 */
	public function easy_charts_add_tinymce_plugin( $plugin_array ) {
		$plugin_array['easy_charts_insert_chart_tc_button'] = plugin_dir_url( __FILE__ ) . 'js/insert-chart-button.js';

		return $plugin_array;
	}

	/**
	 * Add Insert chart button.
	 *
	 * @since 1.0.0
	 *
	 * @param    array $buttons Array of button names.
	 *
	 * @return    array      Array if buttons with newly added button name.
	 */
	public function easy_charts_register_insert_chart_tc_button( $buttons ) {
		array_push( $buttons, 'easy_charts_insert_chart_tc_button' );

		return $buttons;
	}
}
