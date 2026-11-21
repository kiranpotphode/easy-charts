<?php
/**
 * Tests for the read-only chart REST routes.
 *
 * @package EasyCharts
 */

namespace EasyCharts\Tests\Integration;

use EasyCharts\Chart\ChartRepository;
use WP_REST_Request;
use WP_UnitTestCase;

/**
 * ChartsController permissions and responses.
 */
class ChartsControllerTest extends WP_UnitTestCase {

	/**
	 * Create a chart.
	 *
	 * @param string $status Post status.
	 * @param string $title  Post title.
	 * @param bool   $v1     Store 1.x meta instead of a v2 document.
	 * @return int
	 */
	private function chart( $status = 'publish', $title = 'Sales', $v1 = false ) {
		$id = self::factory()->post->create(
			array(
				'post_type'   => 'easy_charts',
				'post_status' => $status,
				'post_title'  => $title,
			)
		);
		if ( $v1 ) {
			update_post_meta( $id, '_easy_charts_chart_data', '[["","Kia"],["2008","10"]]' );
			update_post_meta( $id, '_ec_chart_type', 'ec_donut_chart' );
		} else {
			( new ChartRepository() )->save(
				$id,
				array(
					'schema'   => 2,
					'type'     => 'line',
					'data'     => array(
						'columns' => array( '', 'A' ),
						'rows'    => array( array( 'x', 1 ) ),
					),
					'settings' => array( 'legend' => array( 'position' => 'top' ) ),
				)
			);
		}

		return $id;
	}

	/**
	 * Log in as a new user with a role.
	 *
	 * @param string $role Role.
	 * @return void
	 */
	private function login( $role ) {
		wp_set_current_user( self::factory()->user->create( array( 'role' => $role ) ) );
	}

	/**
	 * Run a GET request.
	 *
	 * @param string $route  Route below /easy-charts/v1.
	 * @param array  $params Query parameters.
	 * @return \WP_REST_Response
	 */
	private function get( $route, array $params = array() ) {
		$request = new WP_REST_Request( 'GET', '/easy-charts/v1' . $route );
		$request->set_query_params( $params );

		return rest_do_request( $request );
	}

	public function test_only_users_who_can_edit_posts_may_use_the_routes() {
		$id = $this->chart();

		wp_set_current_user( 0 );
		$this->assertSame( 401, $this->get( '/charts' )->get_status() );

		$this->login( 'subscriber' );
		$this->assertSame( 403, $this->get( '/charts' )->get_status() );
		$this->assertSame( 403, $this->get( '/charts/' . $id )->get_status() );

		$this->login( 'contributor' );
		$this->assertSame( 200, $this->get( '/charts' )->get_status() );
	}

	public function test_authors_list_published_charts_including_converted_1x_charts() {
		$published = $this->chart();
		$legacy    = $this->chart( 'publish', 'Old chart', true );
		$this->chart( 'draft', 'Draft' );
		$this->chart( 'private', 'Private' );
		$this->login( 'author' );

		$items = $this->get( '/charts' )->get_data();

		$this->assertEqualSets( array( $published, $legacy ), wp_list_pluck( $items, 'id' ) );
		$types = wp_list_pluck( $items, 'type', 'id' );
		$this->assertSame( 'donut', $types[ $legacy ] );
	}

	public function test_editors_also_see_drafts_and_private_charts() {
		$this->chart();
		$this->chart( 'draft', 'Draft' );
		$this->chart( 'private', 'Private' );
		$this->login( 'editor' );

		$this->assertCount( 3, $this->get( '/charts' )->get_data() );
	}

	public function test_search_filters_by_title() {
		$this->chart( 'publish', 'Revenue by region' );
		$this->chart( 'publish', 'Headcount' );
		$this->login( 'author' );

		$items = $this->get( '/charts', array( 'search' => 'revenue' ) )->get_data();

		$this->assertSame( array( 'Revenue by region' ), wp_list_pluck( $items, 'title' ) );
	}

	public function test_single_chart_returns_document_and_effective_settings() {
		$id = $this->chart();
		update_option( 'easy_charts_settings', array( 'labels' => array( 'suffix' => '%' ) ) );
		$this->login( 'author' );

		$data = $this->get( '/charts/' . $id )->get_data();

		$this->assertSame( 'Sales', $data['title'] );
		$this->assertSame( 'line', $data['document']['type'] );
		$this->assertSame( array( 'legend' => array( 'position' => 'top' ) ), $data['document']['settings'] );
		$this->assertSame( 'top', $data['settings']['legend']['position'] );
		$this->assertSame( '%', $data['settings']['labels']['suffix'] );
		$this->assertNull( $data['editLink'], 'authors cannot edit charts' );

		$this->login( 'editor' );
		$this->assertStringContainsString( 'post.php?post=' . $id, $this->get( '/charts/' . $id )->get_data()['editLink'] );
	}

	public function test_1x_chart_is_returned_converted_and_not_stored() {
		$id = $this->chart( 'publish', 'Old chart', true );
		$this->login( 'author' );

		$data = $this->get( '/charts/' . $id )->get_data();

		$this->assertSame( 'donut', $data['document']['type'] );
		$this->assertSame( '', get_post_meta( $id, '_easy_charts_chart', true ) );
	}

	public function test_unreadable_or_missing_charts_are_not_found() {
		$private = $this->chart( 'private' );
		$page    = self::factory()->post->create( array( 'post_type' => 'page' ) );
		$this->login( 'author' );

		$this->assertSame( 404, $this->get( '/charts/' . $private )->get_status() );
		$this->assertSame( 404, $this->get( '/charts/' . $page )->get_status() );
		$this->assertSame( 404, $this->get( '/charts/999999' )->get_status() );

		$this->login( 'editor' );
		$this->assertSame( 200, $this->get( '/charts/' . $private )->get_status() );
	}
}
