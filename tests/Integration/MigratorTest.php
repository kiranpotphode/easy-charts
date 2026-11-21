<?php
/**
 * Tests for migrating 1.x charts.
 *
 * @package EasyCharts
 */

namespace EasyCharts\Tests\Integration;

use EasyCharts\Chart\ChartMeta;
use EasyCharts\Chart\ChartRepository;
use EasyCharts\Migration\Migrator;
use WP_REST_Request;
use WP_UnitTestCase;

/**
 * Migrator and the /easy-charts/v1/migration route.
 */
class MigratorTest extends WP_UnitTestCase {

	/**
	 * Migrator under test.
	 *
	 * @var Migrator
	 */
	private $migrator;

	public function set_up() {
		parent::set_up();
		( new ChartMeta() )->register_meta();
		$this->migrator = new Migrator( new ChartRepository() );
	}

	/**
	 * A chart with 1.x meta.
	 *
	 * @param string $palette 1.x palette.
	 * @return int
	 */
	private function v1_chart( $palette = 'Default' ) {
		$id = self::factory()->post->create( array( 'post_type' => 'easy_charts' ) );
		update_post_meta( $id, '_easy_charts_chart_data', '[["","Kia"],["2008","10"]]' );
		update_post_meta( $id, '_ec_chart_type', 'ec_line_chart' );
		update_post_meta( $id, '_ec_chart_graph', array( 'palette' => $palette ) );

		return $id;
	}

	/**
	 * Every 1.x meta value of a chart.
	 *
	 * @param int $id Chart ID.
	 * @return array
	 */
	private function v1_meta( $id ) {
		return array_filter(
			get_post_meta( $id ),
			static function ( $key ) {
				return 0 === strpos( $key, '_ec_chart_' ) || '_easy_charts_chart_data' === $key;
			},
			ARRAY_FILTER_USE_KEY
		);
	}

	public function test_migrating_stores_the_chart_without_the_1x_look_and_keeps_1x_meta() {
		$id     = $this->v1_chart( 'Android' );
		$before = $this->v1_meta( $id );

		$this->assertTrue( $this->migrator->keeps_1x_look( $id ) );
		$this->assertTrue( $this->migrator->migrate( $id ) );

		$stored = ( new ChartRepository() )->find_stored( $id );
		$this->assertSame( array( 'templateId' => null ), $stored['source'] );
		$this->assertSame( '#33B5E5', $stored['settings']['palette']['colors'][0], 'chosen palette kept' );
		$this->assertFalse( $this->migrator->keeps_1x_look( $id ) );
		$this->assertFalse( $this->migrator->migrate( $id ), 'nothing left to migrate' );
		$this->assertSame( $before, $this->v1_meta( $id ) );
	}

	public function test_a_chart_edited_in_2x_keeps_its_1x_look_until_migrated() {
		$id                    = $this->v1_chart();
		$doc                   = ( new ChartRepository() )->find( $id );
		$doc['data']['rows'][] = array( '2009', 20 );
		( new ChartRepository() )->save( $id, $doc );

		$this->assertTrue( $this->migrator->keeps_1x_look( $id ) );
		$this->assertTrue( $this->migrator->migrate( $id ) );
		$this->assertCount( 2, ( new ChartRepository() )->find_stored( $id )['data']['rows'], 'edits kept' );
	}

	public function test_batches_migrate_every_1x_chart_and_skip_others() {
		$ids = array();
		for ( $i = 0; $i < Migrator::BATCH + 3; $i++ ) {
			$ids[] = $this->v1_chart();
		}
		$broken = $this->v1_chart();
		update_post_meta( $broken, '_easy_charts_chart_data', 'not a grid' );
		$v2 = self::factory()->post->create( array( 'post_type' => 'easy_charts' ) );

		$this->assertSame( Migrator::BATCH + 3, $this->migrator->count_pending() );

		$first = $this->migrator->migrate_batch( 1 );
		$this->assertSame( 2, $first['pages'] );
		$this->assertSame( Migrator::BATCH, $first['migrated'] );

		$second = $this->migrator->migrate_batch( 2 );
		$this->assertSame( 3, $second['migrated'] );
		$this->assertSame( 0, $second['failed'] );
		$this->assertSame( 0, $this->migrator->count_pending() );
		$this->assertSame( '', get_post_meta( $v2, '_easy_charts_chart', true ), 'charts without 1.x data are untouched' );
	}

	public function test_route_is_for_administrators_only() {
		$this->v1_chart();

		wp_set_current_user( self::factory()->user->create( array( 'role' => 'editor' ) ) );
		$this->assertSame( 403, rest_do_request( new WP_REST_Request( 'GET', '/easy-charts/v1/migration' ) )->get_status() );

		wp_set_current_user( self::factory()->user->create( array( 'role' => 'administrator' ) ) );
		$status = rest_do_request( new WP_REST_Request( 'GET', '/easy-charts/v1/migration' ) );
		$this->assertSame( array( 'pending' => 1 ), $status->get_data() );

		$request = new WP_REST_Request( 'POST', '/easy-charts/v1/migration' );
		$request->set_param( 'page', 1 );
		$result = rest_do_request( $request )->get_data();
		$this->assertSame( 1, $result['migrated'] );
		$this->assertSame( 0, rest_do_request( new WP_REST_Request( 'GET', '/easy-charts/v1/migration' ) )->get_data()['pending'] );
	}
}
