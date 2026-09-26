<?php
/**
 * Tests for the capability checks on the 1.x AJAX handlers.
 *
 * @package EasyCharts
 */

namespace EasyCharts\Tests\Integration;

use WP_Ajax_UnitTestCase;
use WPAjaxDieContinueException;
use WPAjaxDieStopException;

/**
 * The 1.x handlers stay live until the new editor replaces them, so they must check permissions.
 */
class LegacyAjaxTest extends WP_Ajax_UnitTestCase {

	/**
	 * Call an AJAX action and return its decoded JSON response.
	 *
	 * @param string $action AJAX action.
	 * @return array|null
	 */
	private function ajax( $action ) {
		try {
			$this->_handleAjax( $action );
		} catch ( WPAjaxDieContinueException $e ) {
			unset( $e );
		} catch ( WPAjaxDieStopException $e ) {
			unset( $e );
		}

		return json_decode( $this->_last_response, true );
	}

	/**
	 * Create a chart with 1.x data.
	 *
	 * @return int
	 */
	private function chart() {
		$id = self::factory()->post->create( array( 'post_type' => 'easy_charts' ) );
		update_post_meta( $id, '_easy_charts_chart_data', '[["","Kia"],["2008",10]]' );

		return $id;
	}

	public function test_subscriber_cannot_save_chart_data() {
		$id = $this->chart();
		$this->_setRole( 'subscriber' );
		$_POST = array(
			'action'       => 'easy_charts_save_chart_data',
			'chart_id'     => $id,
			'_nonce_check' => wp_create_nonce( 'ec-ajax-nonce' ),
			'chart_data'   => '[["","Evil"],["x",1]]',
		);

		$response = $this->ajax( 'easy_charts_save_chart_data' );

		$this->assertFalse( $response['success'] );
		$this->assertSame( '[["","Kia"],["2008",10]]', get_post_meta( $id, '_easy_charts_chart_data', true ) );
	}

	public function test_editor_cannot_save_data_into_a_non_chart_post() {
		$page = self::factory()->post->create( array( 'post_type' => 'page' ) );
		$this->_setRole( 'editor' );
		$_POST = array(
			'action'       => 'easy_charts_save_chart_data',
			'chart_id'     => $page,
			'_nonce_check' => wp_create_nonce( 'ec-ajax-nonce' ),
			'chart_data'   => '[["","A"],["x",1]]',
		);

		$response = $this->ajax( 'easy_charts_save_chart_data' );

		$this->assertFalse( $response['success'] );
		$this->assertSame( '', get_post_meta( $page, '_easy_charts_chart_data', true ) );
	}

	public function test_subscriber_cannot_list_charts() {
		$this->chart();
		$this->_setRole( 'subscriber' );
		$_POST = array( 'action' => 'easy_charts_get_published_charts' );

		$response = $this->ajax( 'easy_charts_get_published_charts' );

		$this->assertFalse( $response['success'] );
	}
}
