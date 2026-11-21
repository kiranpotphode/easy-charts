<?php
/**
 * Tests for the v2 chart document schema.
 *
 * @package EasyCharts
 */

namespace EasyCharts\Tests\Integration;

use EasyCharts\Chart\Schema;
use WP_UnitTestCase;

/**
 * Schema validation and normalization.
 */
class SchemaTest extends WP_UnitTestCase {

	/**
	 * A minimal valid document.
	 *
	 * @return array
	 */
	private function doc() {
		return array(
			'schema' => 2,
			'type'   => 'bar',
			'data'   => array(
				'columns' => array( '', 'Kia', 'Nissan' ),
				'rows'    => array( array( '2008', 10, 11.5 ) ),
			),
		);
	}

	public function test_valid_document_passes() {
		$this->assertTrue( Schema::validate( $this->doc() ) );
	}

	public function test_every_chart_type_is_accepted() {
		foreach ( Schema::TYPES as $type ) {
			$this->assertTrue( Schema::validate( array_merge( $this->doc(), array( 'type' => $type ) ) ), $type );
		}
	}

	/**
	 * Invalid documents.
	 *
	 * @return array
	 */
	public function invalid_documents() {
		$doc = array(
			'schema' => 2,
			'type'   => 'bar',
			'data'   => array(
				'columns' => array( '', 'A' ),
				'rows'    => array(),
			),
		);

		return array(
			'unknown type'       => array( array_merge( $doc, array( 'type' => 'radar' ) ) ),
			'old schema version' => array( array_merge( $doc, array( 'schema' => 1 ) ) ),
			'missing data'       => array( array( 'schema' => 2, 'type' => 'bar' ) ), // phpcs:ignore WordPress.Arrays.ArrayDeclarationSpacing.AssociativeArrayFound
			'unknown top key'    => array( array_merge( $doc, array( 'extra' => true ) ) ),
			'no columns'         => array( array_merge( $doc, array( 'data' => array( 'columns' => array(), 'rows' => array() ) ) ) ), // phpcs:ignore WordPress.Arrays.ArrayDeclarationSpacing.AssociativeArrayFound
			'nested cell'        => array( array_merge( $doc, array( 'data' => array( 'columns' => array( '' ), 'rows' => array( array( array( 'x' ) ) ) ) ) ) ), // phpcs:ignore WordPress.Arrays.ArrayDeclarationSpacing.AssociativeArrayFound
			'settings not group' => array( array_merge( $doc, array( 'settings' => array( 'legend' => 'top' ) ) ) ),
			'bad template id'    => array( array_merge( $doc, array( 'source' => array( 'templateId' => 'abc' ) ) ) ),
		);
	}

	/**
	 * Invalid documents are rejected.
	 *
	 * @dataProvider invalid_documents
	 *
	 * @param array $doc Invalid document.
	 */
	public function test_invalid_document_is_rejected( $doc ) {
		$this->assertWPError( Schema::validate( $doc ) );
		$this->assertWPError( Schema::sanitize( $doc ) );
	}

	public function test_ragged_rows_are_padded_and_trimmed() {
		$doc                 = $this->doc();
		$doc['data']['rows'] = array( array( '2008', 1 ), array( '2009', 1, 2, 3 ) );

		$result = Schema::sanitize( $doc );

		$this->assertSame( array( array( '2008', 1, null ), array( '2009', 1, 2 ) ), $result['data']['rows'] );
	}

	public function test_optional_parts_are_filled() {
		$result = Schema::sanitize( $this->doc() );

		$this->assertSame( array(), $result['settings'] );
		$this->assertSame( array( 'templateId' => null ), $result['source'] );
	}

	public function test_the_1x_look_flag_is_kept_only_when_set() {
		$doc = Schema::empty_document();
		$this->assertArrayNotHasKey( 'v1Look', Schema::sanitize( $doc )['source'] );

		$doc['source']['v1Look'] = true;
		$this->assertTrue( Schema::sanitize( $doc )['source']['v1Look'] );

		$doc['source']['v1Look'] = false;
		$this->assertArrayNotHasKey( 'v1Look', Schema::sanitize( $doc )['source'] );

		$doc['source']['v1Look'] = 'yes';
		$this->assertWPError( Schema::sanitize( $doc ) );
	}

	public function test_empty_document_is_valid() {
		$this->assertTrue( Schema::validate( Schema::empty_document() ) );
	}
}
