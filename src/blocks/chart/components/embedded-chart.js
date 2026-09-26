/**
 * WordPress dependencies
 */
import { InspectorControls } from '@wordpress/block-editor';
import {
	Button,
	ExternalLink,
	PanelBody,
	Placeholder,
	Spinner,
} from '@wordpress/components';
import { useEffect, useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { toSettings } from '../../../charts/settings';
import { getChart } from '../api';
import ChartPreview from './chart-preview';

/**
 * Block view for an embedded chart post: read-only preview, with a link to edit the chart itself
 * and actions to copy it into the block or choose another chart.
 *
 * @param {Object}                  props           Component props.
 * @param {number}                  props.chartId   Embedded chart post ID.
 * @param {(chart: Object) => void} props.onDetach  Called with a copy of the chart document.
 * @param {() => void}              props.onReplace Called to choose another chart.
 * @return {Element} Embedded chart.
 */
export default function EmbeddedChart( { chartId, onDetach, onReplace } ) {
	const [ state, setState ] = useState( { status: 'loading' } );

	useEffect( () => {
		let active = true;
		setState( { status: 'loading' } );
		getChart( chartId )
			.then(
				( chart ) => active && setState( { status: 'ready', chart } )
			)
			.catch(
				( error ) =>
					active &&
					setState( { status: 'error', message: error.message } )
			);
		return () => {
			active = false;
		};
	}, [ chartId ] );

	if ( state.status === 'loading' ) {
		return <Spinner />;
	}

	if ( state.status === 'error' ) {
		return (
			<Placeholder
				icon="chart-bar"
				label={ __( 'Chart', 'easy-charts' ) }
				instructions={ state.message }
			>
				<Button variant="secondary" onClick={ onReplace }>
					{ __( 'Choose another chart', 'easy-charts' ) }
				</Button>
			</Placeholder>
		);
	}

	const { chart } = state;
	const makeCopy = () => {
		const settings = toSettings( chart.document.settings );
		// Keep the title: an embedded chart shows its post title unless it sets its own.
		const title =
			settings.title?.text || ! chart.title
				? settings
				: {
						...settings,
						title: { ...settings.title, text: chart.title },
					};
		onDetach( {
			...chart.document,
			settings: title,
			source: { templateId: null },
		} );
	};

	return (
		<>
			<InspectorControls>
				<PanelBody title={ __( 'Embedded chart', 'easy-charts' ) }>
					<p>
						<strong>{ chart.title }</strong>
					</p>
					<p>
						{ __(
							'This block shows a chart saved on its own. Changes to that chart appear everywhere it is used.',
							'easy-charts'
						) }
					</p>
					{ chart.editLink && (
						<p>
							<ExternalLink href={ chart.editLink }>
								{ __( 'Edit chart', 'easy-charts' ) }
							</ExternalLink>
						</p>
					) }
					<div className="easy-charts-embedded__actions">
						<Button
							variant="secondary"
							onClick={ makeCopy }
							description={ __(
								'Copy the chart into this block so you can change it here without affecting other pages.',
								'easy-charts'
							) }
						>
							{ __( 'Make a copy', 'easy-charts' ) }
						</Button>
						<Button variant="tertiary" onClick={ onReplace }>
							{ __( 'Choose another chart', 'easy-charts' ) }
						</Button>
					</div>
				</PanelBody>
			</InspectorControls>
			<ChartPreview
				doc={ chart.document }
				settings={ chart.settings }
				title={ chart.title }
			/>
		</>
	);
}
