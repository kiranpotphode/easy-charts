/**
 * Data the server passes to the chart scripts in wp-admin (`window.easyChartsEditor`, see
 * `EasyCharts\Block\ChartBlock::editor_data()` and `EasyCharts\Admin\SettingsPage`).
 */

/**
 * Internal dependencies
 */
import { mergeSettings } from '../charts/settings';

/**
 * Base settings (defaults + global settings), the base of 1.x charts that keep their 1.x look,
 * and translated chart strings.
 *
 * @return {{baseSettings: Object, v1BaseSettings: Object, strings: Object}} Editor data.
 */
export function getEditorData() {
	return {
		baseSettings: {},
		v1BaseSettings: {},
		strings: {},
		...( window.easyChartsEditor ?? {} ),
	};
}

/**
 * Whether a chart is a converted 1.x chart that keeps its 1.x look until it is migrated.
 *
 * @param {Object} doc Chart document.
 * @return {boolean} True until migrated.
 */
export function keeps1xLook( doc ) {
	return doc?.source?.v1Look === true;
}

/**
 * The settings a chart's own settings apply on top of: the 1.x look for unmigrated 1.x charts
 * (site-wide settings don't apply to them), the defaults and site-wide settings otherwise.
 *
 * @param {Object} doc Chart document.
 * @return {Object} Base settings.
 */
export function baseSettingsFor( doc ) {
	const data = getEditorData();
	return keeps1xLook( doc ) ? data.v1BaseSettings : data.baseSettings;
}

/**
 * Effective settings of a chart, as the server resolves them.
 *
 * @param {Object} doc Chart document.
 * @return {Object} Resolved settings.
 */
export function resolveSettings( doc ) {
	return mergeSettings( baseSettingsFor( doc ), doc?.settings );
}
