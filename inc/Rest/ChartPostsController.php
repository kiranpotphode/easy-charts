<?php
/**
 * Core REST controller for the chart post type, limited to users who can edit posts.
 *
 * @package EasyCharts
 */

namespace EasyCharts\Rest;

use WP_Error;
use WP_REST_Posts_Controller;

/**
 * `/wp/v2/easy-charts`: core's posts controller, but reading needs the `edit_posts` capability.
 *
 * Charts are read over REST only by users who can edit posts. The block editor reads charts
 * through this route and the chart picker through ChartsController, which has the same reach.
 */
final class ChartPostsController extends WP_REST_Posts_Controller {

	/**
	 * Listing charts needs `edit_posts`, on top of core's checks.
	 *
	 * @param \WP_REST_Request $request Request.
	 * @return true|WP_Error
	 */
	public function get_items_permissions_check( $request ) {
		$allowed = $this->can_read_charts();

		return true === $allowed ? parent::get_items_permissions_check( $request ) : $allowed;
	}

	/**
	 * Reading one chart needs `edit_posts`, on top of core's checks.
	 *
	 * @param \WP_REST_Request $request Request.
	 * @return true|WP_Error
	 */
	public function get_item_permissions_check( $request ) {
		$allowed = $this->can_read_charts();

		return true === $allowed ? parent::get_item_permissions_check( $request ) : $allowed;
	}

	/**
	 * Whether the current user may read charts through this route.
	 *
	 * @return true|WP_Error
	 */
	private function can_read_charts() {
		if ( current_user_can( 'edit_posts' ) ) {
			return true;
		}

		return new WP_Error(
			'rest_forbidden',
			__( 'Sorry, you are not allowed to view charts.', 'easy-charts' ),
			array( 'status' => rest_authorization_required_code() )
		);
	}
}
