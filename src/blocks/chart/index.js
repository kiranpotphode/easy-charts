/**
 * WordPress dependencies
 */
import { createBlock, registerBlockType } from '@wordpress/blocks';

/**
 * Internal dependencies
 */
import metadata from './block.json';
import Edit from './edit';
import './editor.css';

const SHORTCODE =
	/^\s*\[easy_chart\s+chart_id\s*=\s*["']?(\d+)["']?\s*\/?\]\s*$/;

/**
 * Chart ID from `[easy_chart chart_id="N"]`.
 *
 * @param {string} text Shortcode text.
 * @return {number} Chart ID, or 0.
 */
function shortcodeChartId( text ) {
	const match = SHORTCODE.exec( text ?? '' );
	return match ? parseInt( match[ 1 ], 10 ) : 0;
}

registerBlockType( metadata.name, {
	edit: Edit,
	// Rendered on the server from the attributes.
	save: () => null,
	transforms: {
		from: [
			// Pasted shortcodes and converted classic content.
			{
				type: 'shortcode',
				tag: 'easy_chart',
				attributes: {
					chartId: {
						type: 'number',
						shortcode: ( { named } ) =>
							parseInt( named.chart_id, 10 ) || undefined,
					},
				},
			},
			// A Shortcode block holding just [easy_chart chart_id="N"].
			{
				type: 'block',
				blocks: [ 'core/shortcode' ],
				isMatch: ( { text } ) => shortcodeChartId( text ) > 0,
				transform: ( { text } ) =>
					createBlock( metadata.name, {
						chartId: shortcodeChartId( text ),
					} ),
			},
		],
	},
} );
