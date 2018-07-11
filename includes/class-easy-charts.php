<?php
/**
 * The file that defines the core plugin class
 *
 * A class definition that includes attributes and functions used across both the
 * public-facing side of the site and the admin area.
 *
 * @link       https://kiranpotphode.com/
 * @since      1.0.0
 *
 * @package    Easy_Charts
 * @subpackage Easy_Charts/includes
 */

/**
 * The core plugin class.
 *
 * This is used to define internationalization, admin-specific hooks, and
 * public-facing site hooks.
 *
 * Also maintains the unique identifier of this plugin as well as the current
 * version of the plugin.
 *
 * @since      1.0.0
 * @package    Easy_Charts
 * @subpackage Easy_Charts/includes
 * @author     Kiran Potphode <kiranpotphode15@gmail.com>
 */
class Easy_Charts {

	/**
	 * The loader that's responsible for maintaining and registering all hooks that power
	 * the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      Easy_Charts_Loader $loader Maintains and registers all hooks for the plugin.
	 */
	protected $loader;

	/**
	 * The unique identifier of this plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string $plugin_name The string used to uniquely identify this plugin.
	 */
	protected $plugin_name;

	/**
	 * The current version of the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string $version The current version of the plugin.
	 */
	protected $version;

	/**
	 * Define the core functionality of the plugin.
	 *
	 * Set the plugin name and the plugin version that can be used throughout the plugin.
	 * Load the dependencies, define the locale, and set the hooks for the admin area and
	 * the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function __construct() {

		$this->plugin_name = 'easy-charts';
		$this->version     = '1.2.1';

		$this->load_dependencies();
		$this->set_locale();
		$this->define_admin_hooks();
		$this->define_public_hooks();

	}

	/**
	 * Load the required dependencies for this plugin.
	 *
	 * Include the following files that make up the plugin:
	 *
	 * - Easy_Charts_Loader. Orchestrates the hooks of the plugin.
	 * - Easy_Charts_i18n. Defines internationalization functionality.
	 * - Easy_Charts_Admin. Defines all hooks for the admin area.
	 * - Easy_Charts_Public. Defines all hooks for the public side of the site.
	 *
	 * Create an instance of the loader which will be used to register the hooks
	 * with WordPress.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function load_dependencies() {

		/**
		 * The class responsible for orchestrating the actions and filters of the
		 * core plugin.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-easy-charts-loader.php';

		/**
		 * The class responsible for defining internationalization functionality
		 * of the plugin.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-easy-charts-i18n.php';

		/**
		 * The class responsible for defining all actions that occur in the admin area.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/class-easy-charts-admin.php';

		/**
		 * The class responsible for defining all actions that occur in the public-facing
		 * side of the site.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'public/class-easy-charts-public.php';

		$this->loader = new Easy_Charts_Loader();

	}

	/**
	 * Define the locale for this plugin for internationalization.
	 *
	 * Uses the Easy_Charts_i18n class in order to set the domain and to register the hook
	 * with WordPress.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function set_locale() {

		$plugin_i18n = new Easy_Charts_i18n();

		$this->loader->add_action( 'plugins_loaded', $plugin_i18n, 'load_plugin_textdomain' );

	}

	/**
	 * Register all of the hooks related to the admin area functionality
	 * of the plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function define_admin_hooks() {

		$plugin_admin = new Easy_Charts_Admin( $this->get_plugin_name(), $this->get_version() );

		$this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_styles' );
		$this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_scripts' );
		$this->loader->add_action( 'init', $plugin_admin, 'init' );
		$this->loader->add_action( 'add_meta_boxes', $plugin_admin, 'add_meta_boxes' );
		$this->loader->add_action( 'save_post', $plugin_admin, 'easy_charts_save_meta_box_data' );
		$this->loader->add_action( 'admin_head', $plugin_admin, 'easy_charts_add_insert_chart_button' );
		$this->loader->add_action( 'admin_print_scripts', $plugin_admin, 'admin_print_scripts' );
		$this->loader->add_action( 'admin_menu', $plugin_admin, 'add_options_menu' );
	}

	/**
	 * Register all of the hooks related to the public-facing functionality
	 * of the plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function define_public_hooks() {

		$plugin_public = new Easy_Charts_Public( $this->get_plugin_name(), $this->get_version() );

		$this->loader->add_action( 'wp_enqueue_scripts', $plugin_public, 'enqueue_styles' );
		$this->loader->add_action( 'wp_enqueue_scripts', $plugin_public, 'enqueue_scripts' );
		$this->loader->add_action( 'init', $plugin_public, 'init' );
		$this->loader->add_action( 'wp_ajax_easy_charts_save_chart_data', $plugin_public, 'init' );
		$this->loader->add_action( 'wp_ajax_easy_charts_get_published_charts', $plugin_public, 'init' );

	}

	/**
	 * Run the loader to execute all of the hooks with WordPress.
	 *
	 * @since    1.0.0
	 */
	public function run() {
		$this->loader->run();
	}

	/**
	 * The name of the plugin used to uniquely identify it within the context of
	 * WordPress and to define internationalization functionality.
	 *
	 * @since     1.0.0
	 * @return    string    The name of the plugin.
	 */
	public function get_plugin_name() {
		return $this->plugin_name;
	}

	/**
	 * The reference to the class that orchestrates the hooks with the plugin.
	 *
	 * @since     1.0.0
	 * @return    Easy_Charts_Loader    Orchestrates the hooks of the plugin.
	 */
	public function get_loader() {
		return $this->loader;
	}

	/**
	 * Retrieve the version number of the plugin.
	 *
	 * @since     1.0.0
	 * @return    string    The version number of the plugin.
	 */
	public function get_version() {
		return $this->version;
	}

	/**
	 * Create dropdown of available chart types.
	 *
	 * @param  array $args An array of arguments to create chart options dropdown.
	 *
	 * @access private
	 * @return string  HTML string of output.
	 */
	private function easy_chart_dropdown( $args = '' ) {
		$defaults = array(
			'selected'              => 0,
			'echo'                  => 1,
			'name'                  => 'ec_dropdown',
			'id'                    => '',
			'options'               => array(),
			'show_option_none'      => '',
			'show_option_no_change' => '',
			'option_none_value'     => '',
		);

		$r      = wp_parse_args( $args, $defaults );
		$output = '';
		// Back-compat with old system where both id and name were based on $name argument.
		if ( empty( $r['id'] ) ) {
			$r['id'] = $r['name'];
		}

		if ( ! empty( $r['options'] ) ) {
			$output = "<select name='" . esc_attr( $r['name'] ) . "' id='" . esc_attr( $r['id'] ) . "' class='ec-dropdown-select'>\n";
			if ( $r['show_option_no_change'] ) {
				$output .= "\t<option value=\"-1\" " . selected( $r['selected'], - 1, 0 ) . '>' . $r['show_option_no_change'] . "</option>\n";
			}
			if ( $r['show_option_none'] ) {
				$output .= "\t<option value=\"" . esc_attr( $r['option_none_value'] ) . '" ' . selected( $r['selected'], esc_attr( $r['option_none_value'] ), 0 ) . '>' . $r['show_option_none'] . "</option>\n";
			}
			foreach ( $r['options'] as $key => $value ) {
				$output .= "\t<option value=\"" . esc_attr( $key ) . '" ' . selected( $r['selected'], esc_attr( $key ), 0 ) . '>' . $value . "</option>\n";
			}

			$output .= "</select>\n";
		}

		/**
		 * Filter the HTML output.
		 *
		 * @since 1.0.0
		 *
		 * @param string $output HTML output.
		 */
		$html = apply_filters( 'easy_chart_dropdown', $output );

		if ( $r['echo'] ) {
			echo $html;
		}

		return $html;

	}

	/**
	 * Helper to transpose any two dimensional array.
	 *
	 * @since 1.0.0
	 * @access private
	 *
	 * @param array               $array An 2D array to transpose.
	 * @param integer|string|bool $selectkey Key on which transpose the array    Optional  Default us false.
	 *
	 * @return array    Transposed array.
	 */
	private function array_transpose( $array, $selectkey = false ) {
		if ( ! is_array( $array ) ) {
			return false;
		}
		$return = array();

		foreach ( $array as $key => $value ) {

			if ( ! is_array( $value ) ) {
				return $array;
			}

			if ( $selectkey ) {

				if ( isset( $value[ $selectkey ] ) ) {
					$return[] = $value[ $selectkey ];
				}
			} else {

				foreach ( $value as $key2 => $value2 ) {
					$return[ $key2 ][ $key ] = $value2;
				}
			}
		}

		return $return;
	}

	/**
	 * Get available font Family
	 *
	 * @since 1.0.3
	 *
	 * @return        array        Array of font-family.
	 */
	public function get_font_family_array() {

		$font_family = array(
			'Arial'               => 'Arial',
			'Impact'              => 'Impact',
			'Palatino Linotype'   => 'Palatino Linotype',
			'Tahoma'              => 'Tahoma',
			'Century Gothic'      => ' Century Gothic',
			'Lucida Sans Unicode' => 'Lucida Sans Unicode',
			'Arial Black'         => 'Arial Black',
			'Times New Roman'     => 'Times New Roman',
			'Arial Narrow'        => 'Arial Narrow',
			'Verdana'             => 'Verdana',
			'Lucida Console'      => 'Lucida Console',
			'Gill Sans'           => 'Gill Sans',
			'Trebuchet MS'        => 'Trebuchet MS',
			'Courier New'         => 'Courier New',
			'Georgia'             => 'Georgia',
		);

		/**
		 * Filter to add font family.
		 *
		 * @since 1.0.3
		 *
		 * @param array $font_family Array of font familly.
		 */

		return apply_filters( 'easy_charts_fonts', $font_family );
	}

	/**
	 * Get chart data.
	 *
	 * @since 1.0.0
	 *
	 * @param integer|null $chart_id ID of chart to retrieve data   Default is null.
	 *
	 * @return    array        Array of data.
	 */
	public function get_ec_chart_data( $chart_id = null ) {

		if ( null === $chart_id ) {
			return;
		}

		$ec_chart_data          = array();
		$ec_chart_categories    = array();
		$ec_chart_configuration = array();

		$ec_chart_type = get_post_meta( $chart_id, '_ec_chart_type', true );

		switch ( $ec_chart_type ) {
			case 'ec_bar_chart':
				$ec_chart_type = 'Bar';
				break;

			case 'ec_area_chart':
				$ec_chart_type = 'Area';
				break;

			case 'ec_stacked_bar_chart':
				$ec_chart_type = 'StackedBar';
				break;

			case 'ec_stacked_area_chart':
				$ec_chart_type = 'StackedArea';
				break;

			case 'ec_percent_bar_chart':
				$ec_chart_type = 'PercentBar';
				break;

			case 'ec_percent_area_chart':
				$ec_chart_type = 'PercentArea';
				break;

			case 'ec_pie_chart':
				$ec_chart_type = 'Pie';
				break;

			case 'ec_donut_chart':
				$ec_chart_type = 'Donut';
				break;

			case 'ec_step_up_bar_chart':
				$ec_chart_type = 'StepUpBar';
				break;

			case 'ec_waterfall_chart':
				$ec_chart_type = 'Waterfall';
				break;

			case 'ec_line_chart':
				$ec_chart_type = 'Line';
				break;

			case 'ec_polar_area_chart':
				$ec_chart_type = 'PolarArea';
				break;

			default:
				$ec_chart_type = 'Bar';
				break;
		}

		$ec_chart_dataset = json_decode( get_post_meta( $chart_id, '_easy_charts_chart_data', true ) );

		if ( null === $ec_chart_dataset ) {
			return;
		}

		if ( 'Pie' === $ec_chart_type || 'Donut' === $ec_chart_type || 'PolarArea' === $ec_chart_type || 'StepUpBar' === $ec_chart_type ) {
			$ec_chart_dataset = $this->array_transpose( $ec_chart_dataset );
		}

		$ec_chart_categories = array_shift( $ec_chart_dataset );

		array_shift( $ec_chart_categories );

		$translated_dataset = array();
		foreach ( $ec_chart_categories as $key => $ec_chart_category ) {
			$translated_dataset[ $ec_chart_category ] = array();

			foreach ( $ec_chart_dataset as $data_key => $data_value ) {
				$translated_dataset[ $ec_chart_category ][] = array(
					'name'  => $data_value[0],
					'value' => floatval( $data_value[ $key + 1 ] ),
				);
			}
		}

		$ec_chart_graph      = $this->ec_get_chart_configuration( $chart_id, 'graph' );
		$ec_chart_meta       = $this->ec_get_chart_configuration( $chart_id, 'meta' );
		$ec_chart_dimension  = $this->ec_get_chart_configuration( $chart_id, 'dimension' );
		$ec_chart_margin     = $this->ec_get_chart_configuration( $chart_id, 'margin' );
		$ec_chart_frame      = $this->ec_get_chart_configuration( $chart_id, 'frame' );
		$ec_chart_axis       = $this->ec_get_chart_configuration( $chart_id, 'axis' );
		$ec_chart_label      = $this->ec_get_chart_configuration( $chart_id, 'label' );
		$ec_chart_legend     = $this->ec_get_chart_configuration( $chart_id, 'legend' );
		$ec_chart_scale      = $this->ec_get_chart_configuration( $chart_id, 'scale' );
		$ec_chart_tooltip    = $this->ec_get_chart_configuration( $chart_id, 'tooltip' );
		$ec_chart_caption    = $this->ec_get_chart_configuration( $chart_id, 'caption' );
		$ec_chart_subcaption = $this->ec_get_chart_configuration( $chart_id, 'subcaption' );
		$ec_chart_bar        = $this->ec_get_chart_configuration( $chart_id, 'bar' );
		$ec_chart_line       = $this->ec_get_chart_configuration( $chart_id, 'line' );
		$ec_chart_area       = $this->ec_get_chart_configuration( $chart_id, 'area' );
		$ec_chart_pie        = $this->ec_get_chart_configuration( $chart_id, 'pie' );
		$ec_chart_donut      = $this->ec_get_chart_configuration( $chart_id, 'donut' );

		$ec_chart_data = array(
			'chart_type'          => $ec_chart_type,
			'chart_data'          => $translated_dataset,
			'chart_categories'    => $ec_chart_categories,
			'chart_configuration' => array(
				'graph'      => $ec_chart_graph,
				'meta'       => $ec_chart_meta,
				'frame'      => $ec_chart_frame,
				'axis'       => $ec_chart_axis,
				'dimension'  => $ec_chart_dimension,
				'margin'     => $ec_chart_margin,
				'label'      => $ec_chart_label,
				'legend'     => $ec_chart_legend,
				'scale'      => $ec_chart_scale,
				'tooltip'    => $ec_chart_tooltip,
				'caption'    => $ec_chart_caption,
				'subCaption' => $ec_chart_subcaption,
				'bar'        => $ec_chart_bar,
				'line'       => $ec_chart_line,
				'area'       => $ec_chart_area,
				'pie'        => $ec_chart_pie,
				'donut'      => $ec_chart_donut,
			),
		);

		/**
		 * Filter for get data of chart require to render chart.
		 *
		 * @since 1.0.3
		 *
		 * @param array $ec_chart_data All chart related data.
		 * @param int $chart_is Chart id.
		 */
		$ec_chart_data = apply_filters( 'easy_charts_get_chart_data', $ec_chart_data, $chart_id );

		return $ec_chart_data;

	}

	/**
	 * Render easy chart.
	 *
	 * @since 1.0.0
	 *
	 * @param integer|null $chart_id Chart id which is to be rendered.
	 *
	 * @return string        html markup for chart container.
	 */
	public function ec_render_chart( $chart_id = null ) {
		$chart_html = '';
		if ( $chart_id ) {

			$chart = get_post( $chart_id );

			if ( ! empty( $chart ) && 'easy_charts' !== $chart->post_type ) {
				return;
			}

			$ec_chart_data = $this->get_ec_chart_data( $chart_id );

			if ( null !== $ec_chart_data ) {

				if ( is_admin() ) {
					wp_localize_script( 'easy-charts-admin-js', 'ec_chart_data', $ec_chart_data );
				} else {
					wp_localize_script( 'easy-charts-public-js', 'ec_object_' . $chart_id, $ec_chart_data );
				}
			}

			$chart_html = '<div  class="ec-uv-chart-container uv-div-' . $chart_id . '" data-object="ec_object_' . $chart_id . '"></div>';

			/**
			 * Filter to replace html content of chart.
			 *
			 * @since 1.0.3
			 *
			 * @param string $chart_html HTML of chart to render.
			 * @param int $chart_id Chart ID.
			 */
			$chart_html = apply_filters( 'easy_charts_render_chart', $chart_html, $chart_id );

			return $chart_html;

		}

		return $chart_html;

	}

	/**
	 * Get  chart configuration options.
	 *
	 * @since 1.0.0
	 *
	 * @param integer|null $chart_id Chart id.
	 * @param string       $meta_key Meta key of configuration.
	 *
	 * @return array    Array of configuration.
	 */
	public function ec_get_chart_configuration( $chart_id = null, $meta_key = '' ) {

		$ec_chart_option = get_post_meta( $chart_id, '_ec_chart_' . $meta_key, true );

		if ( '' === $ec_chart_option ) {

			switch ( $meta_key ) {
				case 'meta':
					$ec_chart_option = array(
						'position'       => '#uv-div',
						'caption'        => '',
						'subcaption'     => '',
						'hlabel'         => '',
						'hsublabel'      => '',
						'vlabel'         => '',
						'vsublabel'      => '',
						'isDownloadable' => 0,
						'downloadLabel'  => 'Download',
					);
					break;

				case 'graph':
					$ec_chart_option = array(
						'palette'     => 'Brink',
						'responsive'  => 1,
						'bgcolor'     => '#ffffff',
						'orientation' => 'Horizontal',
						'opacity'     => 1,
					);
					break;

				case 'dimension':
					$ec_chart_option = array(
						'width'  => 400,
						'height' => 400,
					);
					break;

				case 'margin':
					$ec_chart_option = array(
						'top'    => 50,
						'bottom' => 150,
						'left'   => 100,
						'right'  => 100,
					);
					break;

				case 'frame':
					$ec_chart_option = array(
						'bgcolor' => '#ffffff',
					);
					break;

				case 'axis':
					$ec_chart_option = array(
						'opacity'      => 0.1,
						'ticks'        => 8,
						'subticks'     => 2,
						'padding'      => 5,
						'strokecolor'  => '#000000',
						'minor'        => - 10,
						'fontfamily'   => 'Arial',
						'fontsize'     => 11,
						'fontweight'   => 700,
						'showticks'    => 1,
						'showsubticks' => 1,
						'showtext'     => 1,
					);
					break;

				case 'label':
					$ec_chart_option = array(
						'fontfamily'  => 'Arial',
						'fontsize'    => 11,
						'fontweight'  => 700,
						'strokecolor' => '#000000',
						'showlabel'   => 1,
						'precision'   => 2,
						'prefix'      => '',
						'suffix'      => '',
					);
					break;

				case 'legend':
					$ec_chart_option = array(
						'position'      => 'bottom',
						'fontfamily'    => 'Arial',
						'fontsize'      => '11',
						'fontweight'    => 'normal',
						'color'         => '#000000',
						'strokewidth'   => 0.15,
						'textmargin'    => 15,
						'symbolsize'    => 10,
						'inactivecolor' => '#DDD',
						'legendstart'   => 0,
						'legendtype'    => 'categories',
						'showlegends'   => true,
					);
					break;

				case 'scale':
					$ec_chart_option = array(
						'type'       => 'linear',
						'ordinality' => 0.2,
					);
					break;

				case 'tooltip':
					$ec_chart_option = array(
						'show'   => 1,
						'format' => '%c [%l] : %v',
					);
					break;

				case 'caption':
					$ec_chart_option = array(
						'fontfamily'     => 'Arial',
						'fontsize'       => 11,
						'fontweight'     => 700,
						'textdecoration' => 'none',
						'strokecolor'    => '#000000',
						'cursor'         => 'pointer',
					);
					break;

				case 'subcaption':
					$ec_chart_option = array(
						'fontfamily'     => 'Arial',
						'fontsize'       => 11,
						'fontweight'     => 700,
						'textdecoration' => 'none',
						'strokecolor'    => '#000000',
						'cursor'         => 'pointer',
					);
					break;

				case 'bar':
					$ec_chart_option = array(
						'fontfamily'  => 'Arial',
						'fontsize'    => 10,
						'fontweight'  => 700,
						'strokecolor' => 'none',
						'textcolor'   => '#000000',
					);
					break;

				case 'line':
					$ec_chart_option = array(
						'interpolation' => 'linear',
					);
					break;

				case 'area':
					$ec_chart_option = array(
						'interpolation' => 'linear',
						'opacity'       => 0.2,
						'offset'        => 'zero',
					);
					break;

				case 'pie':
					$ec_chart_option = array(
						'fontfamily'  => 'Arial',
						'fontsize'    => 11,
						'fontweight'  => 700,
						'fontvariant' => 'small-caps',
						'fontfill'    => '#000000',
						'strokecolor' => '#ffffff',
						'strokewidth' => 1,
					);
					break;

				case 'donut':
					$ec_chart_option = array(
						'fontfamily'  => 'Arial',
						'fontsize'    => 11,
						'fontweight'  => 700,
						'fontvariant' => 'small-caps',
						'fontfill'    => '#000000',
						'strokecolor' => '#ffffff',
						'strokewidth' => 1,
						'factor'      => 0.4,
					);
					break;

				default:
					break;
			}
		}

		/**
		 * Filter to get configuration options for selected meta key.
		 *
		 * @since 1.0.3
		 *
		 * @param array $ec_chart_option Chart configuration options.
		 * @param int $chart_id ID of chart.
		 * @param string $meta_key Meta key for which to get options.
		 */
		$ec_chart_option = apply_filters( 'easy_charts_get_chart_configurations', $ec_chart_option, $chart_id, $meta_key );

		return $ec_chart_option;
	}

	/**
	 * Render input field.
	 *
	 * @since 1.0.0
	 *
	 * @param string $field_type Type of input field.
	 * @param string $field_name Name of field to refer in form.
	 * @param string $field_label Label to display along with input field.
	 * @param string $field_value Value of input field.
	 * @param array  $field_options Optional array of options for input field.
	 */
	public function ec_render_field( $field_type, $field_name, $field_label, $field_value, $field_options = array() ) {

		switch ( $field_type ) {

			case 'text':
			?>
				<div class="field">
					<table>
						<tbody>
						<tr>
							<td class="ec-td-label">
								<label><?php echo esc_html( $field_label ); ?> :</label>
							</td>
							<td class="ec-td-field">
								<input type="text" name="<?php echo esc_attr( $field_name ); ?>" value="<?php echo esc_attr( $field_value ); ?>"/>
							</td>
						</tr>
						</tbody>
					</table>
				</div>
				<?php
				break;

			case 'number':
			?>
				<div class="field">
					<table>
						<tbody>
						<tr>
							<td class="ec-td-label">
								<label><?php echo esc_html( $field_label ); ?> :</label>
							</td>
							<td class="ec-td-field">
								<input type="number" name="<?php echo esc_attr( $field_name ); ?>"
										min="<?php echo esc_attr( isset( $field_options['min'] ) ? $field_options['min'] : '' ); ?>"
										max="<?php echo esc_attr( isset( $field_options['max'] ) ? $field_options['max'] : '' ); ?>"
										step="<?php echo esc_attr( isset( $field_options['step'] ) ? $field_options['step'] : '' ); ?>"
										value="<?php echo esc_attr( $field_value ); ?>" class="ec-field-number"/>
							</td>
						</tr>
						</tbody>
					</table>
				</div>
				<?php
				break;

			case 'radio':
			?>
				<div class="field">
					<table>
						<tbody>
						<tr>
							<td class="ec-td-label">
								<label><?php echo esc_html( $field_label ); ?> :</label>
							</td>
							<td class="ec-td-field">
								<div class="ec-field-buttonset">
									<?php foreach ( $field_options as $key => $value ) { ?>
										<input  name="<?php echo esc_attr( $field_name ); ?>"
												id="<?php echo esc_attr( $field_name . $value ); ?>" type="radio"
												value="<?php echo esc_attr( $value ); ?>" <?php checked( $value, $field_value ); ?> />
										<label for="<?php echo esc_attr( $field_name . $value ); ?>"><?php echo esc_html( $key ); ?></label>
									<?php } ?>
								</div>
							</td>
						</tr>
						</tbody>
					</table>
				</div>
				<?php
				break;

			case 'slider':
			?>
				<div class="field">
					<table>
						<tbody>
						<tr>
							<td class="ec-td-label">
								<label><?php echo esc_html( $field_label ); ?> :</label>
							</td>
							<td class="ec-td-field">
								<input type="text" name="<?php echo esc_attr( $field_name ); ?>"
										class="ec-field-slider-attach <?php echo esc_attr( $field_name ); ?>" readonly
										value="<?php echo esc_attr( $field_value ); ?>"/>
								<div class="ec-field-slider" data-attach=".<?php echo esc_attr( $field_name ); ?>"></div>
							</td>
						</tr>
						</tbody>
					</table>
				</div>
				<?php
				break;

			case 'color-picker':
			?>
				<div class="field">
					<table>
						<tbody>
						<tr>
							<td class="ec-td-label">
								<label><?php echo esc_html( $field_label ); ?> :</label>
							</td>
							<td class="ec-td-field">
								<input type="text" name="<?php echo esc_attr( $field_name ); ?>" class="ec-color-picker" value="<?php echo esc_attr( $field_value ); ?>"/>
							</td>
						</tr>
						</tbody>
					</table>
				</div>
				<?php
				break;

			case 'dropdown':
			?>
				<div class="field">
					<table>
						<tbody>
						<tr>
							<td class="ec-td-label">
								<label><?php echo esc_html( $field_label ); ?> :</label>
							</td>
							<td class="ec-td-field">
								<?php
								$args = array(
									'options'  => $field_options,
									'selected' => $field_value,
									'id'       => $field_name,
									'name'     => $field_name,
									'echo'     => 1,
								);
								$this->easy_chart_dropdown( $args );
								?>
							</td>
						</tr>
						</tbody>
					</table>
				</div>
				<?php
				break;

			default:
				break;
		}

	}
}
