/**
 * Chart post edit screen: a "Use this chart" panel with the chart's shortcode
 * (script handle: easy-charts-chart-post, loaded on chart posts only).
 */

/**
 * WordPress dependencies
 */
import { Button, TextControl } from '@wordpress/components';
import { useCopyToClipboard } from '@wordpress/compose';
import { useSelect } from '@wordpress/data';
import {
	PluginDocumentSettingPanel,
	store as editorStore,
} from '@wordpress/editor';
import { useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { registerPlugin } from '@wordpress/plugins';

/**
 * Panel with the chart's shortcode and a copy button.
 *
 * @return {Element|null} Panel.
 */
function UseThisChartPanel() {
	const { postId, postType } = useSelect(
		( select ) => ( {
			postId: select( editorStore ).getCurrentPostId(),
			postType: select( editorStore ).getCurrentPostType(),
		} ),
		[]
	);
	const [ copied, setCopied ] = useState( false );
	const shortcode = `[easy_chart chart_id="${ postId }"]`;
	const copyRef = useCopyToClipboard( shortcode, () => {
		setCopied( true );
		setTimeout( () => setCopied( false ), 2000 );
	} );

	if ( postType !== 'easy_charts' ) {
		return null;
	}

	return (
		<PluginDocumentSettingPanel
			name="easy-charts-use-this-chart"
			title={ __( 'Use this chart', 'easy-charts' ) }
		>
			<p>
				{ __(
					'In the block editor, add a Chart block and choose "Use an existing chart". In classic content, use the shortcode:',
					'easy-charts'
				) }
			</p>
			<TextControl
				__next40pxDefaultSize
				__nextHasNoMarginBottom
				label={ __( 'Shortcode', 'easy-charts' ) }
				value={ shortcode }
				readOnly
				onChange={ () => {} }
				onFocus={ ( event ) => event.target.select() }
			/>
			<Button variant="secondary" ref={ copyRef }>
				{ copied
					? __( 'Copied!', 'easy-charts' )
					: __( 'Copy shortcode', 'easy-charts' ) }
			</Button>
		</PluginDocumentSettingPanel>
	);
}

registerPlugin( 'easy-charts-chart-post', { render: UseThisChartPanel } );
