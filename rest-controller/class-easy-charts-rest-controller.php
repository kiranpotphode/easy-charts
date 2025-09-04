<?php
/**
 * The public-facing functionality of the plugin.
 *
 * @link       https://kiranpotphode.com/
 * @since      2.0.0
 *
 * @package    Easy_Charts
 * @subpackage Easy_Charts/rest-api
 */

/**
 * The Custom REST API functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    Easy_Charts
 * @subpackage Easy_Charts/rest-api
 * @author     Kiran Potphode <kiranpotphode15@gmail.com>
 */
class Easy_Charts_REST_API {

	/**
	 * The ID of this plugin.
	 *
	 * @since    2.0.0
	 * @access   private
	 * @var      string $rest_api_namespace The REST API namespace.
	 */
	private $rest_api_namespace = 'easy-charts/v1';

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    2.0.0
	 */
	public function __construct() {

	}

	/**
	 * Permission callback for REST Endpoints.
	 *
	 * @since      2.0.0
	 *
	 * @param WP_REST_Request $request REST Request.
	 *
	 * @return bool|WP_Error
	 */
	public function rest_permission_callback( WP_REST_Request $request ) {
		if ( ! wp_verify_nonce( $request->get_header( 'X-Easy-Charts-Fetch-Nonce' ), 'easy-charts-fetch-nonce' ) ) {
			return new WP_Error( 'rest_invalid_nonce', __( 'Invalid nonce', 'easy-charts' ), array( 'status' => 403 ) );
		}
		return current_user_can( 'read' );
	}
	/**
	 * Register rest route for the chart data.
	 *
	 * @since      2.0.0
	 *
	 * @return void
	 */
	public function register_rest_routes() {
		register_rest_route(
			$this->rest_api_namespace,
			'/chart/(?P<id>\d+)/?$',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'get_chart_data_for_easy_charts' ),
				'permission_callback' => array( $this, 'rest_permission_callback' ),
			)
		);

		register_rest_route(
			$this->rest_api_namespace,
			'/chart/(?P<id>\d+)/title/?$',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'get_chart_title' ),
				'permission_callback' => array( $this, 'rest_permission_callback' ),
			)
		);

		register_rest_route(
			$this->rest_api_namespace,
			'/chart/',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'get_chart_list' ),
				'permission_callback' => function ( WP_REST_Request $request ) {
					if ( ! wp_verify_nonce( $request->get_header( 'X-Easy-Charts-Fetch-Nonce' ), 'easy-charts-fetch-nonce' ) ) {
						return new WP_Error( 'rest_invalid_nonce', __( 'Invalid nonce', 'easy-charts' ), array( 'status' => 403 ) );
					}
					return current_user_can( 'read' );
				},
				'args'                => array(
					'search'   => array(
						'description'       => 'Search term.',
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_text_field',
					),
					'per_page' => array(
						'description'       => 'Posts per page.',
						'type'              => 'integer',
						'default'           => 10,
						'sanitize_callback' => 'absint',
					),
					'page'     => array(
						'description'       => 'Page number.',
						'type'              => 'integer',
						'default'           => 1,
						'sanitize_callback' => 'absint',
					),
					'fields'   => array(
						'description'       => 'Fields to include (comma-separated).',
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_key',
					),
				),
			)
		);
	}

	/**
	 * Get chart title.
	 *
	 * @since 2.0.0
	 *
	 * @param WP_REST_Request $request Request object.
	 *
	 * @return WP_Error|WP_HTTP_Response|WP_REST_Response
	 */
	public function get_chart_title( WP_REST_Request $request ) {
		$chart_id = intval( $request['id'] );
		$chart    = get_post( $chart_id );

		return rest_ensure_response( $chart->post_title );
	}
	/**
	 * Get chart list.
	 *
	 * @param WP_REST_Request $request REST request.
	 *
	 * @return WP_Error|WP_HTTP_Response|WP_REST_Response
	 */
	public function get_chart_list( WP_REST_Request $request ) {
		$search   = $request->get_param( 'search' );
		$per_page = $request->get_param( 'per_page' );
		$page     = $request->get_param( 'page' );
		$fields   = $request->get_param( 'fields' );

		$args = array(
			'post_type'      => 'easy_charts',
			's'              => $search,
			'posts_per_page' => $per_page,
			'paged'          => $page,
			'fields'         => 'ids',
		);

		$query = new WP_Query( $args );
		$ids   = $query->posts;
		$data  = array();

		foreach ( $ids as $id ) {
			$item = array();
			if ( empty( $fields ) || strpos( $fields, 'id' ) !== false ) {
				$item['id'] = $id;
			}
			if ( empty( $fields ) || strpos( $fields, 'title' ) !== false ) {
				$item['title'] = get_the_title( $id );
			}
			$data[] = $item;
		}

		$response = rest_ensure_response( $data );

		$response->header( 'X-WP-Total', (int) $query->found_posts );
		$response->header( 'X-WP-TotalPages', (int) $query->max_num_pages );

		return $response;
	}


	/**
	 * Get chart data by id.
	 *
	 * @since 2.0.0
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
