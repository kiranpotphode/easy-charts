<?php
/**
 * Tests for the example charts.
 *
 * @package EasyCharts
 */

namespace EasyCharts\Tests\Integration;

use EasyCharts\Chart\ChartRepository;
use EasyCharts\Demo\Examples;
use WP_UnitTestCase;

/**
 * Examples creates the demo charts without WP-CLI (the Playground blueprint runs it with runPHP).
 */
class ExamplesTest extends WP_UnitTestCase {

	/**
	 * Example chart IDs.
	 *
	 * @return int[]
	 */
	private function charts() {
		return get_posts(
			array(
				'post_type'      => 'easy_charts',
				'posts_per_page' => -1,
				'fields'         => 'ids',
			)
		);
	}

	public function test_creates_one_chart_per_type_and_a_page_once() {
		$log = ( new Examples() )->create();

		$this->assertCount( 13, $log );
		$this->assertStringStartsWith( 'Example page: ', end( $log ) );
		$this->assertCount( 12, $this->charts() );
		$this->assertNotNull( get_page_by_path( 'easy-charts-examples' ) );

		( new Examples() )->create();
		$this->assertCount( 12, $this->charts(), 'running again updates the examples' );
	}

	public function test_1x_examples_are_stored_in_the_1x_format() {
		( new Examples() )->create( true );
		$repository = new ChartRepository();

		foreach ( $this->charts() as $id ) {
			$this->assertNull( $repository->find_stored( $id ) );
			$this->assertNotNull( $repository->find( $id ), 'converted when drawn' );
		}
		$this->assertCount( 12, $this->charts() );
	}
}
