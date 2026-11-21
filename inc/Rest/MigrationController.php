<?php
/**
 * REST route for migrating all 1.x charts.
 *
 * @package EasyCharts
 */

namespace EasyCharts\Rest;

use EasyCharts\Migration\Migrator;
use EasyCharts\Service;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;

/**
 * `GET /easy-charts/v1/migration` counts the charts that keep their 1.x look;
 * `POST /easy-charts/v1/migration` migrates one batch of them (see Migrator::migrate_batch()).
 *
 * For administrators (manage_options), like the settings page that uses it.
 */
final class MigrationController implements Service {

	/**
	 * Migrator.
	 *
	 * @var Migrator
	 */
	private $migrator;

	/**
	 * Constructor.
	 *
	 * @param Migrator $migrator Migrator.
	 */
	public function __construct( Migrator $migrator ) {
		$this->migrator = $migrator;
	}

	/**
	 * Attach hooks.
	 *
	 * @return void
	 */
	public function register() {
		add_action( 'rest_api_init', array( $this, 'register_routes' ) );
	}

	/**
	 * Register the routes.
	 *
	 * @return void
	 */
	public function register_routes() {
		register_rest_route(
			ChartsController::NAMESPACE_V1,
			'/migration',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'status' ),
					'permission_callback' => array( $this, 'can_migrate' ),
				),
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'migrate' ),
					'permission_callback' => array( $this, 'can_migrate' ),
					'args'                => array(
						'page' => array(
							'type'     => 'integer',
							'minimum'  => 1,
							'required' => true,
						),
					),
				),
			)
		);
	}

	/**
	 * Permission: administrators.
	 *
	 * @return bool
	 */
	public function can_migrate() {
		return current_user_can( 'manage_options' );
	}

	/**
	 * How many charts keep their 1.x look.
	 *
	 * @return WP_REST_Response
	 */
	public function status() {
		return new WP_REST_Response( array( 'pending' => $this->migrator->count_pending() ) );
	}

	/**
	 * Migrate one batch.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response
	 */
	public function migrate( WP_REST_Request $request ) {
		return new WP_REST_Response( $this->migrator->migrate_batch( (int) $request['page'] ) );
	}
}
