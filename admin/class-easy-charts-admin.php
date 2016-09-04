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
	 * @var      string    $plugin_name    The ID of this plugin.
	 */
	private $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $version    The current version of this plugin.
	 */
	private $version;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @todo    Move ajax action to separate location
	 *
	 * @since    1.0.0
	 * @param      string    $plugin_name       The name of this plugin.
	 * @param      string    $version    The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {

		$this->plugin_name = $plugin_name;
		$this->version = $version;

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

		if ( 'post-new.php' === $pagenow ||  'post.php' === $pagenow &&  'easy_charts' === $typenow ) {
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

		if ( 'post-new.php' === $pagenow || 'post.php' === $pagenow  && 'easy_charts' === $typenow ) {

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

	public function admin_print_scripts() {
		echo "<script type='text/javascript'>\n";
		echo 'var ajaxurl = "'.esc_url( admin_url( 'admin-ajax.php' ) ).'"';
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
			'name'               => _x( 'Easy Charts', 'post type general name', 'easy-charts' ),
			'singular_name'      => _x( 'Chart', 'post type singular name', 'easy-charts' ),
			'menu_name'          => _x( 'Easy Charts', 'admin menu', 'easy-charts' ),
			'name_admin_bar'     => _x( 'Chart', 'add new on admin bar', 'easy-charts' ),
			'add_new'            => _x( 'Add New', 'easy-charts' ),
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
			'labels' => $labels,
			'public' => false,
			'show_ui' => true,
			'_builtin' => false,
			'capability_type' => 'page',
			'hierarchical' => true,
			'menu_icon' => 'dashicons-chart-bar',
			'rewrite' => false,
			'query_var' => 'easy_charts',
			'supports' => array(
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
	 * @since    1.0.0
	 */
	public function easy_charts_shortcode_metabox_callback( $post ) {
		require_once plugin_dir_path( __FILE__ ) . 'partials/easy-charts-shortcode-metabox-display.php';
	}

	/**
	 * Render Shortcode Meta Box content.
	 *
	 * @param WP_Post $post The post object.
	 * @since    1.0.0
	 */
	public function easy_charts_preview_metabox_callback( $post ) {
		require_once plugin_dir_path( __FILE__ ) . 'partials/easy-charts-preview-metabox-display.php';
	}


	/**
	 * Render Shortcode Meta Box content.
	 *
	 * @param WP_Post $post The post object.
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

		$ec_chart_meta = array(
						'position' => $_POST['ec_chart_meta_position'],
						'caption' => $_POST['ec_chart_meta_caption'],
						'subcaption' => $_POST['ec_chart_meta_subcaption'],
						'hlabel' => $_POST['ec_chart_meta_hlabel'],
						'hsublabel' => $_POST['ec_chart_meta_hsublabel'],
						'vlabel' => $_POST['ec_chart_meta_vlabel'],
						'vsublabel' => $_POST['ec_chart_meta_vsublabel'],
						'isDownloadable' => (integer) $_POST['ec_chart_meta_isDownloadable'],
						'downloadLabel' => $_POST['ec_chart_meta_downloadLabel'],
					);

		$ec_chart_graph  = array(
						'responsive' => (boolean) $_POST['ec_chart_graph_responsive'],
						'palette' => $_POST['ec_chart_graph_palette'],
						'bgcolor' => $_POST['ec_chart_graph_bgcolor'],
						'orientation' => $_POST['ec_chart_graph_orientation'],
						'opacity' => (float) $_POST['ec_chart_graph_opacity'],
					);

		$ec_chart_dimension  = array(
						'width' => (integer) $_POST['ec_chart_dimension_width'],
						'height' => (integer) $_POST['ec_chart_dimension_height'],
					);

		$ec_chart_margin  = array(
						'top' => (integer) $_POST['ec_chart_margin_top'],
						'bottom' => (integer) $_POST['ec_chart_margin_bottom'],
						'left' => (integer) $_POST['ec_chart_margin_left'],
						'right' => (integer) $_POST['ec_chart_margin_right'],
					);

		$ec_chart_frame  = array(
						'bgcolor' => $_POST['ec_chart_frame_bgcolor'],
					);

		$ec_chart_axis  = array(
						'opacity' => (float) $_POST['ec_chart_axis_opacity'],
						'ticks' => (integer) $_POST['ec_chart_axis_ticks'],
						'subticks' => (integer) $_POST['ec_chart_axis_subticks'],
						'padding' => (integer) $_POST['ec_chart_axis_padding'],
						'strokecolor' => $_POST['ec_chart_axis_strokecolor'],
						'minor' => (integer) $_POST['ec_chart_axis_minor'],
						'fontfamily' => $_POST['ec_chart_axis_fontfamily'],
						'fontsize' => $_POST['ec_chart_axis_fontsize'],
						'fontweight' => $_POST['ec_chart_axis_fontweight'],
						'showticks' => (integer) $_POST['ec_chart_axis_showticks'],
						'showsubticks' => (integer) $_POST['ec_chart_axis_showsubticks'],
						'showtext' => (integer) $_POST['ec_chart_axis_showtext'],
					);

		$ec_chart_label  = array(
						'strokecolor' => $_POST['ec_chart_label_strokecolor'],
						'fontfamily' => $_POST['ec_chart_label_fontfamily'],
						'fontsize' => $_POST['ec_chart_label_fontsize'],
						'fontweight' => $_POST['ec_chart_label_fontweight'],
						'showlabel' => (integer) $_POST['ec_chart_label_showlabel'],
						'precision' => (integer) $_POST['ec_chart_label_precision'],
						'prefix' => $_POST['ec_chart_label_prefix'],
						'suffix' => $_POST['ec_chart_label_suffix'],
					);

	    $ec_chart_legend  = array(
			            'position' => $_POST['ec_chart_legend_position'],
			            'fontfamily' => $_POST['ec_chart_legend_fontfamily'],
			            'fontsize' => $_POST['ec_chart_legend_fontsize'],
			            'fontweight' => $_POST['ec_chart_legend_fontweight'],
			            'color' => $_POST['ec_chart_legend_color'],
			            'strokewidth' => (float) $_POST['ec_chart_legend_strokewidth'],
			            'textmargin' => (integer) $_POST['ec_chart_legend_textmargin'],
			            'symbolsize' => (integer) $_POST['ec_chart_legend_symbolsize'],
			            'inactivecolor' => $_POST['ec_chart_legend_inactivecolor'],
			            'legendstart' => (integer) $_POST['ec_chart_legend_legendstart'],
			            'legendtype' => 'categories',
			            'showlegends' => (integer) $_POST['ec_chart_legend_showlegends'],
	          		);

		$ec_chart_scale  = array(
						'type' => $_POST['ec_chart_scale_type'],
						'ordinality' => (float) $_POST['ec_chart_scale_ordinality'],
					);

		$ec_chart_tooltip  = array(
						'show' => (integer) $_POST['ec_chart_tooltip_show'],
						'format' => $_POST['ec_chart_tooltip_format'],
					);

		$ec_chart_caption  = array(
						'fontfamily' => $_POST['ec_chart_caption_fontfamily'],
						'fontsize' => $_POST['ec_chart_caption_fontsize'],
						'fontweight' => $_POST['ec_chart_caption_fontweight'],
						'textdecoration' => $_POST['ec_chart_caption_textdecoration'],
						'strokecolor' => $_POST['ec_chart_caption_strokecolor'],
						'cursor' => $_POST['ec_chart_caption_cursor'],
					);

		$ec_chart_subcaption  = array(
						'fontfamily' => $_POST['ec_chart_subcaption_fontfamily'],
						'fontsize' => $_POST['ec_chart_subcaption_fontsize'],
						'fontweight' => $_POST['ec_chart_subcaption_fontweight'],
						'textdecoration' => $_POST['ec_chart_subcaption_textdecoration'],
						'strokecolor' => $_POST['ec_chart_subcaption_strokecolor'],
						'cursor' => $_POST['ec_chart_subcaption_cursor'],
					);

		$ec_chart_bar  = array(
						'fontfamily' => $_POST['ec_chart_bar_fontfamily'],
						'fontsize' => $_POST['ec_chart_bar_fontsize'],
						'fontweight' => $_POST['ec_chart_bar_fontweight'],
						'strokecolor' => $_POST['ec_chart_bar_strokecolor'],
						'textcolor' => $_POST['ec_chart_bar_textcolor'],
					);

		$ec_chart_line  = array(
						'interpolation' => $_POST['ec_chart_line_interpolation'],
					);

		$ec_chart_area  = array(
						'interpolation' => $_POST['ec_chart_area_interpolation'],
						'opacity' => (float) $_POST['ec_chart_area_opacity'],
						'offset' => $_POST['ec_chart_area_offset'],
					);

		$ec_chart_pie  = array(
						'fontfamily' => $_POST['ec_chart_pie_fontfamily'],
						'fontsize' => $_POST['ec_chart_pie_fontsize'],
						'fontweight' => $_POST['ec_chart_pie_fontweight'],
						'fontvariant' => $_POST['ec_chart_pie_fontvariant'],
						'fontfill' => $_POST['ec_chart_pie_fontfill'],
						'strokecolor' => $_POST['ec_chart_pie_strokecolor'],
						'strokewidth' => $_POST['ec_chart_pie_strokewidth'],
					);

		$ec_chart_donut  = array(
						'fontfamily' => $_POST['ec_chart_donut_fontfamily'],
						'fontsize' => $_POST['ec_chart_donut_fontsize'],
						'fontweight' => $_POST['ec_chart_donut_fontweight'],
						'fontvariant' => $_POST['ec_chart_donut_fontvariant'],
						'fontfill' => $_POST['ec_chart_donut_fontfill'],
						'strokecolor' => $_POST['ec_chart_donut_strokecolor'],
						'strokewidth' => $_POST['ec_chart_donut_strokewidth'],
						'factor' => $_POST['ec_chart_donut_factor'],
					);

		update_post_meta( $post_id, '_ec_chart_type', $_POST['ec_chart_type'] );
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

		if ( 'easy_charts_save_chart_data' !== $_POST['action'] ) {
			exit( 0 );
		}

		update_post_meta( $_POST['chart_id'], '_easy_charts_chart_data', $_POST['chart_data'] );

		echo wp_json_encode( $plugin->get_ec_chart_data( $_POST['chart_id'] ) );

		exit( 0 );
	}

	/**
	 * Get published charts.
	 *
	 * @since 1.0.0
	 */
	public function easy_charts_get_published_charts_callback() {
		if ( 'easy_charts_get_published_charts' !== $_POST['action'] ) {
			exit( 0 );
		}

		$args = array(
				'post_type' => 'easy_charts',
				'post_status' => 'publish',
				'posts_per_page' => -1,
			);

		$chart_query = new WP_Query( $args );

		$charts = array();
		if ( $chart_query->have_posts() ) {
			foreach ( $chart_query->posts as $chart_key => $chart ) {
				$chart_title = '';
				if ( '' === $chart->post_title ) {
					$chart_title = 'Chart-'.$chart->ID;
				} else {
					$chart_title = $chart->post_title;
				}
				$charts[] = array(
								'text' => $chart_title,
								'value' => "[easy_chart chart_id='".$chart->ID."']",
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

	    // check user permissions
	    if ( ! current_user_can( 'edit_posts' ) && ! current_user_can( 'edit_pages' ) ) {
	    	return;
	    }

	    // check if WYSIWYG is enabled
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
	 * @param 	array 	$plugin_array  	Array of plugins urls.
	 * @return  array     Array of plugin urls with newly added plugin.
	 */
	public function easy_charts_add_tinymce_plugin( $plugin_array ) {
		$plugin_array['easy_charts_insert_chart_tc_button'] = plugin_dir_url( __FILE__ ).'js/insert-chart-button.js';
		return $plugin_array;
	}

	/**
	 * Add Insert chart button.
	 *
	 * @since 1.0.0
	 *
	 * @param 	array  	$buttons  	Array of button names.
	 * @return   	array      Array if buttons with newly added button name.
	 */
	function easy_charts_register_insert_chart_tc_button( $buttons ) {
		array_push( $buttons, 'easy_charts_insert_chart_tc_button' );
		return $buttons;
	}
}
