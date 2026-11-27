<?php
/**
 * Read-only REST routes for picking existing charts in the block editor.
 *
 * @package EasyCharts
 */

namespace EasyCharts\Rest;

use EasyCharts\Chart\ChartRepository;
use EasyCharts\PostType\ChartPostType;
use EasyCharts\Service;
use EasyCharts\Settings\SettingsResolver;
use WP_Error;
use WP_Post;
use WP_Query;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;

/**
 * `GET /easy-charts/v1/charts` and `GET /easy-charts/v1/charts/<id>`.
 *
 * Anyone who can edit posts can list and read published charts, the same reach as typing the
 * `[easy_chart]` shortcode. Charts in other states need the `read_post` capability for that chart.
 * Core's `/wp/v2/easy-charts` routes also need `edit_posts` to read (ChartPostsController) and
 * show the document only when editing. Charts without a stored v2 document are returned converted
 * from 1.x (not stored).
 */
final class ChartsController implements Service {

	const NAMESPACE_V1 = 'easy-charts/v1';

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
		add_action( 'rest_api_init', array( $this, 'register_routes' ) );
	}

	/**
	 * Register the routes.
	 *
	 * @return void
	 */
	public function register_routes() {
		register_rest_route(
			self::NAMESPACE_V1,
			'/charts',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'list_charts' ),
				'permission_callback' => array( $this, 'can_use_charts' ),
				'args'                => array(
					'search'   => array(
						'type'              => 'string',
						'default'           => '',
						'sanitize_callback' => 'sanitize_text_field',
					),
					'page'     => array(
						'type'    => 'integer',
						'default' => 1,
						'minimum' => 1,
					),
					'per_page' => array(
						'type'    => 'integer',
						'default' => 20,
						'minimum' => 1,
						'maximum' => 50,
					),
				),
			)
		);

		register_rest_route(
			self::NAMESPACE_V1,
			'/charts/(?P<id>\d+)',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_chart' ),
				'permission_callback' => array( $this, 'can_use_charts' ),
				'args'                => array(
					'id' => array(
						'type'     => 'integer',
						'required' => true,
					),
				),
			)
		);
	}

	/**
	 * Permission: users who can edit posts (and so insert chart blocks).
	 *
	 * @return bool
	 */
	public function can_use_charts() {
		return current_user_can( 'edit_posts' );
	}

	/**
	 * List charts, newest first.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response
	 */
	public function list_charts( WP_REST_Request $request ) {
		// Editors see every chart they could open; everyone else only published ones.
		$statuses = current_user_can( 'edit_pages' ) ? array( 'publish', 'draft', 'pending', 'future', 'private' ) : array( 'publish' );

		$query = new WP_Query(
			array(
				'post_type'      => ChartPostType::POST_TYPE,
				'post_status'    => $statuses,
				's'              => $request['search'],
				'paged'          => $request['page'],
				'posts_per_page' => $request['per_page'],
				'orderby'        => 'date',
				'order'          => 'DESC',
				'no_found_rows'  => false,
			)
		);

		$items = array();
		foreach ( $query->posts as $post ) {
			if ( ! $this->can_read( $post ) ) {
				continue;
			}
			$doc     = $this->charts->find( $post->ID );
			$items[] = array(
				'id'     => $post->ID,
				'title'  => $post->post_title,
				'status' => $post->post_status,
				'type'   => null !== $doc ? $doc['type'] : null,
			);
		}

		$response = new WP_REST_Response( $items );
		$response->header( 'X-WP-Total', (string) $query->found_posts );
		$response->header( 'X-WP-TotalPages', (string) $query->max_num_pages );

		return $response;
	}

	/**
	 * One chart with its document and effective settings.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response|WP_Error
	 */
	public function get_chart( WP_REST_Request $request ) {
		$post = get_post( (int) $request['id'] );
		if ( ! $post instanceof WP_Post || ChartPostType::POST_TYPE !== $post->post_type || ! $this->can_read( $post ) ) {
			return new WP_Error( 'easy_charts_not_found', __( 'Chart not found.', 'easy-charts' ), array( 'status' => 404 ) );
		}

		$doc = $this->charts->find( $post->ID );
		if ( null === $doc ) {
			return new WP_Error( 'easy_charts_no_data', __( 'This chart has no data that can be shown.', 'easy-charts' ), array( 'status' => 422 ) );
		}

		return new WP_REST_Response(
			array(
				'id'       => $post->ID,
				'title'    => $post->post_title,
				'status'   => $post->post_status,
				'document' => $doc,
				'settings' => $this->settings->resolve( $doc ),
				'editLink' => current_user_can( 'edit_post', $post->ID ) ? get_edit_post_link( $post->ID, 'raw' ) : null,
			)
		);
	}

	/**
	 * Published charts are readable by chart users; others need read_post.
	 *
	 * @param WP_Post $post Chart.
	 * @return bool
	 */
	private function can_read( WP_Post $post ) {
		return 'publish' === $post->post_status || current_user_can( 'read_post', $post->ID );
	}
}
