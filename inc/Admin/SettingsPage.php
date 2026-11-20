<?php
/**
 * Global chart settings page.
 *
 * @package EasyCharts
 */

namespace EasyCharts\Admin;

use EasyCharts\Assets;
use EasyCharts\Frontend\ChartRenderer;
use EasyCharts\Migration\Migrator;
use EasyCharts\PostType\ChartPostType;
use EasyCharts\Service;
use EasyCharts\Settings\Defaults;
use EasyCharts\Settings\GlobalSettings;

/**
 * Easy Charts → Settings: edits the site-wide `easy_charts_settings` option, which every chart
 * inherits unless it sets its own value.
 *
 * The page is a small React app (src/settings) that saves through the core settings REST route.
 * It keeps the 1.x page's slug, so existing links still work.
 */
final class SettingsPage implements Service {

	const SLUG = 'easy-charts-settings';

	const SCRIPT = 'easy-charts-settings';

	/**
	 * Admin page hook suffix, set when the menu is added.
	 *
	 * @var string
	 */
	private $hook = '';

	/**
	 * Migrator, for the number of charts that keep their 1.x look.
	 *
	 * @var Migrator|null
	 */
	private $migrator;

	/**
	 * Constructor.
	 *
	 * @param Migrator|null $migrator Migrator.
	 */
	public function __construct( ?Migrator $migrator = null ) {
		$this->migrator = $migrator;
	}

	/**
	 * Attach hooks.
	 *
	 * @return void
	 */
	public function register() {
		add_action( 'admin_menu', array( $this, 'add_menu' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue' ) );
	}

	/**
	 * Add the page under the Easy Charts menu.
	 *
	 * @return void
	 */
	public function add_menu() {
		$this->hook = (string) add_submenu_page(
			'edit.php?post_type=' . ChartPostType::POST_TYPE,
			__( 'Chart settings', 'easy-charts' ),
			__( 'Settings', 'easy-charts' ),
			'manage_options',
			self::SLUG,
			array( $this, 'render' )
		);
	}

	/**
	 * Page markup: the app mounts into the root element.
	 *
	 * @return void
	 */
	public function render() {
		printf(
			'<div class="wrap"><h1>%s</h1><p>%s</p><div id="easy-charts-settings-root"></div><noscript><p>%s</p></noscript></div>',
			esc_html__( 'Chart settings', 'easy-charts' ),
			esc_html__( 'These settings apply to every chart on the site. A chart that sets its own value keeps it.', 'easy-charts' ),
			esc_html__( 'This page needs JavaScript.', 'easy-charts' )
		);
	}

	/**
	 * Load the app on this page only.
	 *
	 * @param string $hook_suffix Current admin page.
	 * @return void
	 */
	public function enqueue( $hook_suffix ) {
		$asset = Assets::get( 'settings' );
		if ( '' === $this->hook || $hook_suffix !== $this->hook || null === $asset ) {
			return;
		}

		wp_enqueue_script(
			self::SCRIPT,
			Assets::url( 'settings.js' ),
			array_merge( $asset['dependencies'], array( ChartRenderer::SCRIPT_ECHARTS ) ),
			$asset['version'],
			true
		);
		wp_enqueue_style( self::SCRIPT, Assets::url( 'settings.css' ), array( 'wp-components' ), $asset['version'] );
		wp_set_script_translations( self::SCRIPT, 'easy-charts', EASY_CHARTS_DIR . 'languages' );

		$data = array(
			'baseSettings' => Defaults::get(),
			'strings'      => ChartRenderer::strings( false ),
			'settings'     => (object) GlobalSettings::get(),
			// Charts that keep their 1.x look and so ignore these settings until migrated.
			'v1Pending'    => $this->migrator ? $this->migrator->count_pending() : 0,
		);
		wp_add_inline_script( self::SCRIPT, 'window.easyChartsEditor = ' . wp_json_encode( $data, JSON_HEX_TAG | JSON_HEX_AMP ) . ';', 'before' );
	}
}
