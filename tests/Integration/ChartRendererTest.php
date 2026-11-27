<?php
/**
 * Tests for front-end chart output.
 *
 * @package EasyCharts
 */

namespace EasyCharts\Tests\Integration;

use EasyCharts\Chart\ChartRepository;
use EasyCharts\Frontend\ChartRenderer;
use EasyCharts\Plugin;
use WP_UnitTestCase;

/**
 * ChartRenderer markup, escaping and visibility rules.
 */
class ChartRendererTest extends WP_UnitTestCase {

	/**
	 * Renderer under test.
	 *
	 * @var ChartRenderer
	 */
	private $renderer;

	public function set_up() {
		parent::set_up();
		$this->renderer = Plugin::instance()->get( ChartRenderer::class );
	}

	/**
	 * Create a chart with a v2 document.
	 *
	 * @param array  $overrides Document overrides.
	 * @param string $status    Post status.
	 * @param string $title     Post title.
	 * @return int
	 */
	private function chart( array $overrides = array(), $status = 'publish', $title = 'Sales' ) {
		$id = self::factory()->post->create(
			array(
				'post_type'   => 'easy_charts',
				'post_status' => $status,
				'post_title'  => $title,
			)
		);
		( new ChartRepository() )->save(
			$id,
			array_merge(
				array(
					'schema' => 2,
					'type'   => 'bar',
					'data'   => array(
						'columns' => array( '', 'Kia' ),
						'rows'    => array( array( '2008', 10 ) ),
					),
				),
				$overrides
			)
		);

		return $id;
	}

	/**
	 * Decode the JSON config embedded in rendered HTML.
	 *
	 * @param string $html Rendered chart.
	 * @return array
	 */
	private function config( $html ) {
		$this->assertSame( 1, preg_match( '#<script type="application/json" class="easy-charts-chart__config">(.*?)</script>#s', $html, $match ) );

		return json_decode( $match[1], true );
	}

	public function test_chart_without_v2_document_renders_nothing() {
		$id = self::factory()->post->create( array( 'post_type' => 'easy_charts' ) );

		$this->assertSame( '', $this->renderer->render( $id ) );
		$this->assertSame( '', $this->renderer->render( 0 ) );
	}

	public function test_renders_figure_with_config_and_resolved_settings() {
		$id   = $this->chart( array( 'settings' => array( 'legend' => array( 'position' => 'top' ) ) ) );
		$html = $this->renderer->render( $id );

		$this->assertStringContainsString( 'class="easy-charts-chart easy-charts-chart--bar"', $html );
		$this->assertStringContainsString( 'data-chart-id="' . $id . '"', $html );

		$config = $this->config( $html );
		$this->assertSame( 'bar', $config['type'] );
		$this->assertSame( 'Sales', $config['title'] );
		$this->assertSame( array( array( '2008', 10 ) ), $config['data']['rows'] );
		$this->assertSame( 'top', $config['settings']['legend']['position'], 'chart override' );
		$this->assertTrue( $config['settings']['tooltip']['show'], 'default filled in' );
	}

	public function test_hostile_labels_cannot_break_out_of_the_markup() {
		$label = '</script><script>alert(1)</script><img src=x onerror=alert(2)>';
		$id    = $this->chart(
			array(
				'data' => array(
					'columns' => array( '', $label ),
					'rows'    => array( array( $label, 1 ) ),
				),
			),
			'publish',
			$label
		);

		$html = $this->renderer->render( $id );

		$this->assertSame( 1, substr_count( strtolower( $html ), '<script' ), 'only the config script tag' );
		$this->assertStringNotContainsString( '<img', $html );
		$this->assertSame( $label, $this->config( $html )['data']['columns'][1], 'JSON round-trips the label' );
	}

	public function test_data_table_is_accessible_and_escaped() {
		$id   = $this->chart(
			array(
				'data' => array(
					'columns' => array( '', 'A & B' ),
					'rows'    => array( array( '<b>2008</b>', null ) ),
				),
			)
		);
		$html = $this->renderer->render( $id );

		$this->assertStringContainsString( '<caption>Sales</caption>', $html );
		$this->assertStringContainsString( '<th scope="col">A &amp; B</th>', $html );
		$this->assertStringContainsString( '<th scope="row">&lt;b&gt;2008&lt;/b&gt;</th><td></td>', $html );
	}

	public function test_custom_title_text_replaces_the_post_title() {
		$html = $this->renderer->render( $this->chart( array( 'settings' => array( 'title' => array( 'text' => 'Q1 & Q2' ) ) ) ) );

		$this->assertSame( 'Q1 & Q2', $this->config( $html )['title'] );
		$this->assertStringContainsString( '<caption>Q1 &amp; Q2</caption>', $html );
	}

	public function test_download_button_only_when_enabled() {
		$this->assertStringNotContainsString( 'easy-charts-chart__download', $this->renderer->render( $this->chart() ) );

		$html = $this->renderer->render( $this->chart( array( 'settings' => array( 'download' => array( 'enabled' => true ) ) ) ) );
		$this->assertStringContainsString( '<button type="button" class="easy-charts-chart__download" hidden>Download<span class="easy-charts-chart__sr"> (PNG image of &quot;Sales&quot;)</span></button>', $html );
	}

	/**
	 * Download labels that are not text.
	 *
	 * @return array
	 */
	public function invalid_labels() {
		return array(
			'array'  => array( array( '<b>x</b>' ) ),
			'number' => array( 42 ),
			'null'   => array( null ),
		);
	}

	/**
	 * A block attribute can hold any JSON; a download label that is not text falls back to the default.
	 *
	 * @dataProvider invalid_labels
	 *
	 * @param mixed $label Label from the chart's settings.
	 */
	public function test_download_label_that_is_not_text_falls_back( $label ) {
		$html = $this->renderer->render_document(
			array(
				'schema'   => 2,
				'type'     => 'bar',
				'data'     => array(
					'columns' => array( '', 'Kia' ),
					'rows'    => array( array( '2008', 10 ) ),
				),
				'settings' => array(
					'download' => array(
						'enabled' => true,
						'label'   => $label,
					),
				),
			)
		);

		$this->assertStringContainsString( 'hidden>Download<span', $html );
	}

	public function test_charts_get_a_short_translated_description() {
		$html    = $this->renderer->render( $this->chart() );
		$strings = $this->config( $html )['strings'];

		$this->assertSame( 'Bar', $strings['types']['bar'] );
		$this->assertSame( '%1$s (%2$s chart).', $strings['chartLabel'] );
		$this->assertSame( 'The values are in the table that follows.', $strings['tableNote'] );
		// The editor preview has no data table to point to.
		$this->assertSame( '', ChartRenderer::strings( false )['tableNote'] );
	}

	public function test_fixed_size_charts_get_a_width() {
		$html = $this->renderer->render(
			$this->chart(
				array(
					'settings' => array(
						'size' => array(
							'responsive' => false,
							'width'      => 500,
							'height'     => 300,
						),
					),
				)
			)
		);

		$this->assertStringContainsString( 'style="height:300px;width:500px;"', $html );
	}

	public function test_private_chart_is_only_shown_to_readers() {
		$id = $this->chart( array(), 'private' );

		wp_set_current_user( 0 );
		$this->assertSame( '', $this->renderer->render( $id ) );

		wp_set_current_user( self::factory()->user->create( array( 'role' => 'editor' ) ) );
		$this->assertStringContainsString( 'easy-charts-chart', $this->renderer->render( $id ) );
	}

	/**
	 * A chart by a given user, optionally with a password.
	 *
	 * @param string $status   Post status.
	 * @param int    $author   Chart author.
	 * @param string $password Post password.
	 * @return int
	 */
	private function chart_by( $status, $author, $password = '' ) {
		$id   = $this->chart( array(), $status );
		$date = 'future' === $status ? time() + DAY_IN_SECONDS : time();
		wp_update_post(
			array(
				'ID'            => $id,
				'post_author'   => $author,
				'post_password' => $password,
				'post_status'   => $status,
				'post_date'     => get_date_from_gmt( gmdate( 'Y-m-d H:i:s', $date ) ),
				'post_date_gmt' => gmdate( 'Y-m-d H:i:s', $date ),
				'edit_date'     => true,
			)
		);
		$this->assertSame( $status, get_post_status( $id ) );

		return $id;
	}

	/**
	 * Render a chart through the content of a published post by a given author, as a visitor.
	 *
	 * @param int $chart_id Chart.
	 * @param int $author   Author of the post that embeds the chart.
	 * @return string
	 */
	private function render_in_post_by( $chart_id, $author ) {
		$host = self::factory()->post->create(
			array(
				'post_author'  => $author,
				'post_content' => '[easy_chart chart_id="' . $chart_id . '"]',
			)
		);
		wp_set_current_user( 0 );
		$GLOBALS['post'] = get_post( $host ); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- the post being viewed.

		return apply_filters( 'the_content', get_post_field( 'post_content', $host ) ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- core filter.
	}

	/**
	 * Unpublished statuses.
	 *
	 * @return array
	 */
	public function unpublished_statuses() {
		return array(
			'draft'     => array( 'draft' ),
			'pending'   => array( 'pending' ),
			'scheduled' => array( 'future' ),
		);
	}

	/**
	 * Unpublished charts are hidden from visitors.
	 *
	 * @dataProvider unpublished_statuses
	 *
	 * @param string $status Post status.
	 */
	public function test_unpublished_chart_is_hidden_from_visitors( $status ) {
		$id = $this->chart_by( $status, self::factory()->user->create( array( 'role' => 'editor' ) ) );
		wp_set_current_user( 0 );

		$this->assertSame( '', $this->renderer->render( $id ) );
	}

	/**
	 * Unpublished charts are shown to users who can read them.
	 *
	 * @dataProvider unpublished_statuses
	 *
	 * @param string $status Post status.
	 */
	public function test_unpublished_chart_is_shown_to_its_readers( $status ) {
		$editor = self::factory()->user->create( array( 'role' => 'editor' ) );
		$id     = $this->chart_by( $status, $editor );
		wp_set_current_user( $editor );

		$this->assertStringContainsString( 'easy-charts-chart', $this->renderer->render( $id ) );
	}

	public function test_own_draft_chart_shows_to_visitors_in_its_authors_post() {
		$editor = self::factory()->user->create( array( 'role' => 'editor' ) );
		$id     = $this->chart_by( 'draft', $editor );

		$this->assertStringContainsString( 'easy-charts-chart', $this->render_in_post_by( $id, $editor ) );
	}

	public function test_nobody_can_show_a_chart_they_could_not_open() {
		$id     = $this->chart_by( 'draft', self::factory()->user->create( array( 'role' => 'editor' ) ) );
		$author = self::factory()->user->create( array( 'role' => 'author' ) );

		$this->assertStringNotContainsString( 'easy-charts-chart', $this->render_in_post_by( $id, $author ) );
	}

	public function test_outside_post_content_only_the_viewer_counts() {
		$editor = self::factory()->user->create( array( 'role' => 'editor' ) );
		$id     = $this->chart_by( 'draft', $editor );
		$this->render_in_post_by( $id, $editor );

		// The editor's post is the current post, but this is not its content (a widget, say).
		$this->assertSame( '', $this->renderer->render( $id ) );
	}

	public function test_password_protected_chart_needs_someone_who_can_edit_it() {
		$admin  = self::factory()->user->create( array( 'role' => 'administrator' ) );
		$author = self::factory()->user->create( array( 'role' => 'author' ) );
		$id     = $this->chart_by( 'publish', $admin, 'secret' );

		wp_set_current_user( 0 );
		$this->assertSame( '', $this->renderer->render( $id ) );
		$this->assertStringNotContainsString( 'easy-charts-chart', $this->render_in_post_by( $id, $author ) );
		$this->assertStringContainsString( 'easy-charts-chart', $this->render_in_post_by( $id, $admin ) );

		wp_set_current_user( $admin );
		$this->assertStringContainsString( 'easy-charts-chart', $this->renderer->render( $id ) );
	}

	public function test_private_chart_stays_hidden_from_visitors_in_its_authors_post() {
		$editor = self::factory()->user->create( array( 'role' => 'editor' ) );
		$id     = $this->chart_by( 'private', $editor );

		$this->assertStringNotContainsString( 'easy-charts-chart', $this->render_in_post_by( $id, $editor ) );
	}

	public function test_1x_render_filter_is_applied() {
		$id = $this->chart();
		add_filter(
			'easy_charts_render_chart',
			static function ( $html, $chart_id ) {
				return '<div class="wrapped" data-id="' . $chart_id . '">' . $html . '</div>';
			},
			10,
			2
		);

		$this->assertStringStartsWith( '<div class="wrapped" data-id="' . $id . '">', $this->renderer->render( $id ) );
	}

	public function test_scripts_load_only_when_a_chart_renders() {
		wp_dequeue_script( ChartRenderer::SCRIPT_FRONTEND );
		$this->assertFalse( wp_script_is( ChartRenderer::SCRIPT_FRONTEND, 'enqueued' ) );

		$this->renderer->render( $this->chart() );

		$this->assertTrue( wp_script_is( ChartRenderer::SCRIPT_FRONTEND, 'enqueued' ) );
		$this->assertContains( ChartRenderer::SCRIPT_ECHARTS, wp_scripts()->registered[ ChartRenderer::SCRIPT_FRONTEND ]->deps );
	}
}
