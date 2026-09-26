/**
 * Settings layering in the browser, mirroring `EasyCharts\Settings\SettingsResolver::merge()` so
 * the block editor preview resolves settings exactly like the server.
 */

/**
 * Whether a value is a plain keyed object (not an array or null).
 *
 * @param {unknown} value Value.
 * @return {boolean} True for plain objects.
 */
function isObject( value ) {
	return (
		typeof value === 'object' && value !== null && ! Array.isArray( value )
	);
}

/**
 * Chart settings as an object. PHP sends an empty settings array as `[]` in JSON.
 *
 * @param {unknown} settings Stored settings.
 * @return {Object} Settings object.
 */
export function toSettings( settings ) {
	return isObject( settings ) ? settings : {};
}

/**
 * Merge overrides into a base: objects merge key by key, lists and scalars replace the base value,
 * and an empty override (`{}` or `[]`) of a group changes nothing.
 *
 * @param {Object} base      Lower layer.
 * @param {Object} overrides Higher layer.
 * @return {Object} Merged settings (inputs are not modified).
 */
export function mergeSettings( base, overrides ) {
	const result = { ...toSettings( base ) };
	Object.entries( toSettings( overrides ) ).forEach( ( [ key, value ] ) => {
		const current = result[ key ];
		if ( isObject( current ) ) {
			if ( isObject( value ) ) {
				result[ key ] = mergeSettings( current, value );
				return;
			}
			if ( Array.isArray( value ) && value.length === 0 ) {
				return;
			}
		}
		result[ key ] = value;
	} );
	return result;
}
