<?php
/**
 * Tests for the chart post type and its document in the REST API.
 *
 * @package EasyCharts
 */

namespace EasyCharts\Tests\Integration;

use EasyCharts\Chart\ChartMeta;
use EasyCharts\Chart\ChartRepository;
use EasyCharts\Plugin;
use WP_REST_Request;
use WP_UnitTestCase;

/**
 * Post type registration and /wp/v2/easy-charts permissions.
 */
class RestTest extends WP_UnitTestCase {

	/**
	 * The WordPress test case unregisters all meta keys after each test; register ours again.
	 */
	public function set_up() {
		parent::set_up();
		Plugin::instance()->get( ChartMeta::class )->register_meta();
	}

	/**
	 * A valid document.
	 *
	 * @return array
	 */
	private function doc() {
		return array(
			'schema' => 2,
			'type'   => 'pie',
			'data'   => array(
				'columns' => array( '', 'Share' ),
				'rows'    => array( array( 'A', 60 ), array( 'B', 40 ) ),
			),
		);
	}

	/**
	 * Create a chart post.
	 *
	 * @param string $status Post status.
	 * @return int
	 */
	private function chart( $status = 'publish' ) {
		return self::factory()->post->create(
			array(
				'post_type'   => 'easy_charts',
				'post_status' => $status,
			)
		);
	}

	/**
	 * Send a request to update a chart's document.
	 *
	 * @param int   $id  Chart ID.
	 * @param mixed $doc Document to send.
	 * @return \WP_REST_Response
	 */
	private function update( $id, $doc ) {
		$request = new WP_REST_Request( 'POST', '/wp/v2/easy-charts/' . $id );
		$request->set_body_params( array( 'meta' => array( ChartMeta::KEY => $doc ) ) );

		return rest_get_server()->dispatch( $request );
	}

	public function test_post_type_keeps_1x_arguments_and_adds_rest() {
		$type = get_post_type_object( 'easy_charts' );

		$this->assertSame( 'page', $type->capability_type );
		$this->assertTrue( $type->hierarchical );
		$this->assertFalse( $type->public );
		$this->assertTrue( $type->show_ui );
		$this->assertTrue( $type->show_in_rest );
		$this->assertSame( 'easy-charts', $type->rest_base );
		$this->assertTrue( post_type_supports( 'easy_charts', 'custom-fields' ) );
	}

	public function test_charts_stay_on_the_classic_editor_for_now() {
		$this->assertFalse( use_block_editor_for_post_type( 'easy_charts' ) );
		$this->assertTrue( use_block_editor_for_post_type( 'post' ) );
	}

	public function test_editor_can_update_the_document() {
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'editor' ) ) );
		$id = $this->chart();

		$response = $this->update( $id, $this->doc() );

		$this->assertSame( 200, $response->get_status() );
		$this->assertSame( 'pie', ( new ChartRepository() )->find( $id )['type'] );
	}

	public function test_invalid_document_is_rejected() {
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'editor' ) ) );
		$id = $this->chart();

		$response = $this->update( $id, array_merge( $this->doc(), array( 'type' => 'radar' ) ) );

		$this->assertSame( 400, $response->get_status() );
		$this->assertNull( ( new ChartRepository() )->find( $id ) );
	}

	/**
	 * Roles that cannot edit charts (capability_type page).
	 *
	 * @return array
	 */
	public function roles_without_access() {
		return array(
			'anonymous'  => array( null ),
			'subscriber' => array( 'subscriber' ),
			'author'     => array( 'author' ),
		);
	}

	/**
	 * Users without edit_pages cannot change a chart's document.
	 *
	 * @dataProvider roles_without_access
	 *
	 * @param string|null $role Role, or null for a visitor.
	 */
	public function test_users_without_access_cannot_update( $role ) {
		$id = $this->chart();
		wp_set_current_user( $role ? self::factory()->user->create( array( 'role' => $role ) ) : 0 );

		$response = $this->update( $id, $this->doc() );

		$this->assertContains( $response->get_status(), array( 401, 403 ) );
		$this->assertNull( ( new ChartRepository() )->find( $id ) );
	}

	public function test_published_chart_is_readable_with_its_document() {
		$id = $this->chart();
		( new ChartRepository() )->save( $id, $this->doc() );
		wp_set_current_user( 0 );

		$response = rest_get_server()->dispatch( new WP_REST_Request( 'GET', '/wp/v2/easy-charts/' . $id ) );

		$this->assertSame( 200, $response->get_status() );
		$this->assertSame( 'pie', $response->get_data()['meta'][ ChartMeta::KEY ]['type'] );
	}

	public function test_draft_chart_is_hidden_from_visitors() {
		$id = $this->chart( 'draft' );
		wp_set_current_user( 0 );

		$response = rest_get_server()->dispatch( new WP_REST_Request( 'GET', '/wp/v2/easy-charts/' . $id ) );

		$this->assertContains( $response->get_status(), array( 401, 403 ) );
	}
}
