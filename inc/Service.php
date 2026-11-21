<?php
/**
 * Service contract.
 *
 * @package EasyCharts
 */

namespace EasyCharts;

/**
 * A unit of plugin behaviour that attaches itself to WordPress hooks.
 */
interface Service {

	/**
	 * Attach the service's callbacks to WordPress hooks.
	 *
	 * @return void
	 */
	public function register();
}
