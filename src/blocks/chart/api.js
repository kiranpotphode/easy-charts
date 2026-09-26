/**
 * Requests to the plugin's read-only chart routes (EasyCharts\Rest\ChartsController).
 */

/**
 * WordPress dependencies
 */
import apiFetch from '@wordpress/api-fetch';
import { addQueryArgs } from '@wordpress/url';

/**
 * Search charts.
 *
 * @param {string} search Search text.
 * @return {Promise<Array<{id: number, title: string, status: string, type: string|null}>>} Charts.
 */
export function searchCharts( search ) {
	return apiFetch( {
		path: addQueryArgs( '/easy-charts/v1/charts', {
			search,
			per_page: 20,
		} ),
	} );
}

/**
 * Load one chart: its document, effective settings and edit link.
 *
 * @param {number} id Chart post ID.
 * @return {Promise<{id: number, title: string, status: string, document: Object, settings: Object, editLink: string|null}>} Chart.
 */
export function getChart( id ) {
	return apiFetch( { path: `/easy-charts/v1/charts/${ id }` } );
}
