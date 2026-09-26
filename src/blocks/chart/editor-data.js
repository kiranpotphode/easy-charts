/**
 * Data the server passes to the editor script (see `EasyCharts\Block\ChartBlock::editor_data()`).
 */

/**
 * Internal dependencies
 */
import { mergeSettings } from '../../charts/settings';

/**
 * Base settings (defaults + global settings) and translated chart strings.
 *
 * @return {{baseSettings: Object, strings: Object}} Editor data.
 */
export function getEditorData() {
	return {
		baseSettings: {},
		strings: {},
		...( window.easyChartsEditor ?? {} ),
	};
}

/**
 * Effective settings of a chart, as the server resolves them.
 *
 * @param {Object} doc Chart document.
 * @return {Object} Resolved settings.
 */
export function resolveSettings( doc ) {
	return mergeSettings( getEditorData().baseSettings, doc?.settings );
}
