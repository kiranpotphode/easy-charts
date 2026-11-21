<?php
/**
 * Tests for settings layering.
 *
 * @package EasyCharts
 */

namespace EasyCharts\Tests\Integration;

use EasyCharts\Chart\ChartRepository;
use EasyCharts\Settings\Defaults;
use EasyCharts\Settings\GlobalSettings;
use EasyCharts\Settings\SettingsResolver;
use WP_UnitTestCase;

/**
 * SettingsResolver: defaults -> global -> template -> chart.
 */
class SettingsResolverTest extends WP_UnitTestCase {

	/**
	 * Repository.
	 *
	 * @var ChartRepository
	 */
	private $repo;

	/**
	 * Resolver under test.
	 *
	 * @var SettingsResolver
	 */
	private $resolver;

	public function set_up() {
		parent::set_up();
		$this->repo     = new ChartRepository();
		$this->resolver = new SettingsResolver( $this->repo );
	}

	/**
	 * A document with the given settings and template.
	 *
	 * @param array    $settings    Chart overrides.
	 * @param int|null $template_id Template chart ID.
	 * @return array
	 */
	private function doc( array $settings = array(), $template_id = null ) {
		return array(
			'schema'   => 2,
			'type'     => 'bar',
			'data'     => array(
				'columns' => array( '' ),
				'rows'    => array(),
			),
			'settings' => $settings,
			'source'   => array( 'templateId' => $template_id ),
		);
	}

	/**
	 * Create a template chart with the given settings.
	 *
	 * @param array  $settings Template settings.
	 * @param string $status   Post status.
	 * @return int
	 */
	private function template( array $settings, $status = 'publish' ) {
		$id = self::factory()->post->create(
			array(
				'post_type'   => 'easy_charts',
				'post_status' => $status,
			)
		);
		$this->repo->save( $id, $this->doc( $settings ) );

		return $id;
	}

	public function test_defaults_only() {
		$this->assertSame( Defaults::get(), $this->resolver->resolve( $this->doc() ) );
	}

	public function test_layers_apply_in_order() {
		update_option( GlobalSettings::OPTION, array( 'legend' => array( 'position' => 'top' ) ) );
		$template = $this->template(
			array(
				'legend' => array( 'position' => 'left' ),
				'size'   => array( 'height' => 250 ),
			)
		);

		$settings = $this->resolver->resolve( $this->doc( array( 'legend' => array( 'position' => 'right' ) ), $template ) );

		$this->assertSame( 'right', $settings['legend']['position'], 'chart beats template and global' );
		$this->assertTrue( $settings['legend']['show'], 'untouched default survives' );
		$this->assertSame( 250, $settings['size']['height'], 'template beats default' );
		$this->assertSame( 400, $settings['size']['width'], 'sibling default survives' );
	}

	public function test_global_settings_apply_without_template() {
		update_option( GlobalSettings::OPTION, array( 'legend' => array( 'position' => 'top' ) ) );

		$this->assertSame( 'top', $this->resolver->resolve( $this->doc() )['legend']['position'] );
	}

	public function test_lists_are_replaced_whole() {
		update_option( GlobalSettings::OPTION, array( 'palette' => array( 'colors' => array( '#111', '#222', '#333' ) ) ) );

		$settings = $this->resolver->resolve( $this->doc( array( 'palette' => array( 'colors' => array( '#abc' ) ) ) ) );

		$this->assertSame( array( '#abc' ), $settings['palette']['colors'] );
	}

	public function test_empty_group_override_changes_nothing() {
		$this->assertSame( Defaults::get()['size'], $this->resolver->resolve( $this->doc( array( 'size' => array() ) ) )['size'] );
	}

	public function test_template_is_followed_one_level_only() {
		$grandparent = $this->template( array( 'legend' => array( 'position' => 'left' ) ) );
		$parent      = self::factory()->post->create(
			array(
				'post_type'   => 'easy_charts',
				'post_status' => 'publish',
			)
		);
		$this->repo->save( $parent, $this->doc( array( 'size' => array( 'height' => 250 ) ), $grandparent ) );

		$settings = $this->resolver->resolve( $this->doc( array(), $parent ) );

		$this->assertSame( 250, $settings['size']['height'] );
		$this->assertSame( 'bottom', $settings['legend']['position'], "the template's own template is ignored" );
	}

	public function test_self_referencing_template_does_not_loop() {
		$id = self::factory()->post->create(
			array(
				'post_type'   => 'easy_charts',
				'post_status' => 'publish',
			)
		);
		$this->repo->save( $id, $this->doc( array( 'size' => array( 'height' => 250 ) ), $id ) );

		$this->assertSame( 250, $this->resolver->resolve( $this->repo->find( $id ) )['size']['height'] );
	}

	public function test_missing_template_is_skipped() {
		$this->assertSame( Defaults::get(), $this->resolver->resolve( $this->doc( array(), 999999 ) ) );
	}

	public function test_draft_template_is_skipped_for_visitors_but_used_by_editors() {
		$template = $this->template( array( 'size' => array( 'height' => 250 ) ), 'draft' );
		$doc      = $this->doc( array(), $template );

		wp_set_current_user( 0 );
		$this->assertSame( 400, $this->resolver->resolve( $doc )['size']['height'] );

		wp_set_current_user( self::factory()->user->create( array( 'role' => 'editor' ) ) );
		$this->assertSame( 250, $this->resolver->resolve( $doc )['size']['height'] );
	}

	public function test_malformed_global_settings_are_dropped_on_save() {
		update_option(
			GlobalSettings::OPTION,
			array(
				'legend' => array( 'position' => 'top' ),
				'junk'   => 'not a group',
				0        => array( 'numeric' => 'key' ),
			)
		);

		$this->assertSame( array( 'legend' => array( 'position' => 'top' ) ), GlobalSettings::get() );
	}
}
