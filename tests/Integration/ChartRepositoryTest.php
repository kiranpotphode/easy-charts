<?php
/**
 * Tests for chart document storage.
 *
 * @package EasyCharts
 */

namespace EasyCharts\Tests\Integration;

use EasyCharts\Chart\ChartMeta;
use EasyCharts\Chart\ChartRepository;
use WP_UnitTestCase;

/**
 * ChartRepository read/write behaviour.
 */
class ChartRepositoryTest extends WP_UnitTestCase {

	/**
	 * Repository under test.
	 *
	 * @var ChartRepository
	 */
	private $repo;

	public function set_up() {
		parent::set_up();
		$this->repo = new ChartRepository();
	}

	/**
	 * A valid document.
	 *
	 * @return array
	 */
	private function doc() {
		return array(
			'schema'   => 2,
			'type'     => 'line',
			'data'     => array(
				'columns' => array( '', 'Kia', 'Back\\slash "quoted"' ),
				'rows'    => array( array( '2008', 10, 11.5 ), array( '2009', 20, null ) ),
			),
			'settings' => array( 'legend' => array( 'position' => 'top' ) ),
			'source'   => array( 'templateId' => null ),
		);
	}

	/**
	 * Create a chart post.
	 *
	 * @return int
	 */
	private function chart() {
		return self::factory()->post->create( array( 'post_type' => 'easy_charts' ) );
	}

	public function test_save_and_find_round_trip() {
		$id = $this->chart();

		$this->assertTrue( $this->repo->save( $id, $this->doc() ) );
		$this->assertSame( $this->doc(), $this->repo->find( $id ) );
	}

	public function test_saving_an_identical_document_again_succeeds() {
		$id = $this->chart();
		$this->repo->save( $id, $this->doc() );

		$this->assertTrue( $this->repo->save( $id, $this->doc() ) );
	}

	public function test_find_returns_null_without_a_document_or_1x_data() {
		$this->assertNull( $this->repo->find( $this->chart() ) );
	}

	public function test_find_ignores_an_invalid_stored_value() {
		$id = $this->chart();
		update_post_meta( $id, ChartMeta::KEY, array( 'schema' => 2, 'type' => 'radar' ) ); // phpcs:ignore WordPress.Arrays.ArrayDeclarationSpacing.AssociativeArrayFound

		$this->assertNull( $this->repo->find( $id ) );
	}

	public function test_invalid_document_is_not_saved() {
		$id  = $this->chart();
		$doc = array_merge( $this->doc(), array( 'type' => 'radar' ) );

		$this->assertWPError( $this->repo->save( $id, $doc ) );
		$this->assertSame( '', get_post_meta( $id, ChartMeta::KEY, true ) );
	}

	public function test_non_chart_posts_are_refused() {
		$page = self::factory()->post->create( array( 'post_type' => 'page' ) );

		$this->assertWPError( $this->repo->save( $page, $this->doc() ) );
		$this->assertNull( $this->repo->find( $page ) );
		$this->assertNull( $this->repo->find( 0 ) );
	}

	public function test_saving_never_touches_1x_meta() {
		$id     = $this->chart();
		$legacy = array(
			'_easy_charts_chart_data' => '[["","Kia"],["2008",10]]',
			'_ec_chart_type'          => 'ec_bar_chart',
			'_ec_chart_legend'        => array( 'position' => 'bottom' ),
		);
		foreach ( $legacy as $key => $value ) {
			update_post_meta( $id, $key, $value );
		}

		$this->repo->save( $id, $this->doc() );

		foreach ( $legacy as $key => $value ) {
			$this->assertSame( $value, get_post_meta( $id, $key, true ), $key );
		}
	}

	/**
	 * Create a chart that only has 1.x meta.
	 *
	 * @return int
	 */
	private function v1_chart() {
		$id = self::factory()->post->create(
			array(
				'post_type'   => 'easy_charts',
				'post_status' => 'publish',
			)
		);
		update_post_meta( $id, '_easy_charts_chart_data', '[["","Kia","Nissan"],["2008","10","11"]]' );
		update_post_meta( $id, '_ec_chart_type', 'ec_pie_chart' );
		update_post_meta( $id, '_ec_chart_meta', array( 'caption' => 'Sales &#38; more' ) );

		return $id;
	}

	public function test_find_converts_1x_charts_without_storing_them() {
		$id  = $this->v1_chart();
		$doc = $this->repo->find( $id );

		$this->assertSame( 'pie', $doc['type'] );
		$this->assertSame( array( array( 'Kia', 10 ), array( 'Nissan', 11 ) ), $doc['data']['rows'] );
		$this->assertNull( $this->repo->find_stored( $id ) );
		$this->assertSame( '', get_post_meta( $id, ChartMeta::KEY, true ) );
	}

	public function test_a_stored_document_wins_over_1x_meta() {
		$id = $this->v1_chart();
		$this->repo->save( $id, $this->doc() );

		$this->assertSame( $this->doc(), $this->repo->find( $id ) );
		$this->assertSame( $this->doc(), $this->repo->find_stored( $id ) );
	}

	public function test_reading_and_rendering_1x_charts_never_changes_any_meta() {
		$id     = $this->v1_chart();
		$before = get_post_meta( $id );

		$this->repo->find( $id );
		do_shortcode( '[easy_chart chart_id="' . $id . '"]' );
		rest_do_request( new \WP_REST_Request( 'GET', '/wp/v2/easy-charts/' . $id ) );

		wp_cache_flush();
		$this->assertSame( $before, get_post_meta( $id ) );
	}
}
