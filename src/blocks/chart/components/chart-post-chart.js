/**
 * WordPress dependencies
 */
import { Button, PanelBody, Spinner } from '@wordpress/components';
import { useEntityProp } from '@wordpress/core-data';
import { useSelect } from '@wordpress/data';
import { useEffect, useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { keeps1xLook } from '../../../components/editor-data';
import { getChart } from '../api';
import ChartEditor from './chart-editor';

export const CHART_POST_TYPE = 'easy_charts';

const META_KEY = '_easy_charts_chart';

/**
 * Whether a meta value is a stored chart document.
 *
 * @param {unknown} value Meta value.
 * @return {boolean} True for a v2 document.
 */
function isDocument( value ) {
	return (
		typeof value === 'object' &&
		value !== null &&
		value.schema === 2 &&
		typeof value.type === 'string'
	);
}

/**
 * The chart block on a chart post's own edit screen, bound to the post's `_easy_charts_chart`
 * meta (saved with the post).
 *
 * A 1.x chart has no stored document yet: it opens converted from its 1.x data, and is stored in
 * the 2.0 format only when the user changes something and saves. It keeps its 1.x look (and so
 * ignores the site-wide settings) until the user migrates it. Its 1.x data is never changed.
 *
 * @param {Object} props        Component props.
 * @param {number} props.postId Chart post ID.
 * @return {Element} Chart editor.
 */
export default function ChartPostChart( { postId } ) {
	const [ meta, setMeta ] = useEntityProp(
		'postType',
		CHART_POST_TYPE,
		'meta',
		postId
	);
	const [ title ] = useEntityProp(
		'postType',
		CHART_POST_TYPE,
		'title',
		postId
	);
	const stored = isDocument( meta?.[ META_KEY ] ) ? meta[ META_KEY ] : null;
	const isNew = useSelect(
		( select ) => select( 'core/editor' ).isEditedPostNew(),
		[]
	);
	// Only a saved chart without a stored document can have 1.x data to convert.
	// Set when "Migrate to 2.0" is used in this editor session, until the page reloads.
	const [ migrated, setMigrated ] = useState( false );
	const [ legacy, setLegacy ] = useState( () =>
		stored || isNew ? { status: 'none' } : { status: 'loading' }
	);

	useEffect( () => {
		if ( legacy.status !== 'loading' ) {
			return;
		}
		let active = true;
		getChart( postId )
			.then(
				( chart ) =>
					active &&
					setLegacy( { status: 'ready', chart: chart.document } )
			)
			// No usable 1.x data: a new chart.
			.catch( () => active && setLegacy( { status: 'none' } ) );
		return () => {
			active = false;
		};
		// Checked once when the editor opens.
		// eslint-disable-next-line react-hooks/exhaustive-deps
	}, [ postId ] );

	if ( ! stored && legacy.status === 'loading' ) {
		return <Spinner />;
	}

	const converted = ! stored && legacy.status === 'ready';
	const chart = stored ?? ( converted ? legacy.chart : null );
	const save = ( next ) => setMeta( { ...meta, [ META_KEY ]: next } );

	let notice = null;
	if ( keeps1xLook( chart ) ) {
		notice = (
			<PanelBody>
				<p className="easy-charts-converted-notice">
					{ __(
						'This chart was made with Easy Charts 1.x and keeps its 1.x look: the site-wide chart settings don’t apply to it. You can edit and save it without changing that.',
						'easy-charts'
					) }
				</p>
				<p className="easy-charts-converted-notice">
					{ __(
						'Migrate it to use the 2.0 styling and the site-wide settings for everything it didn’t change in 1.x. Its 1.x data is kept either way.',
						'easy-charts'
					) }
				</p>
				<Button
					variant="secondary"
					onClick={ () => {
						const { v1Look, ...source } = chart.source ?? {};
						save( { ...chart, source } );
						setMigrated( true );
					} }
				>
					{ __( 'Migrate to 2.0', 'easy-charts' ) }
				</Button>
			</PanelBody>
		);
	} else if ( migrated ) {
		notice = (
			<PanelBody>
				<p className="easy-charts-converted-notice" role="status">
					{ __(
						'Migrated to 2.0. Save the chart to keep the change.',
						'easy-charts'
					) }
				</p>
			</PanelBody>
		);
	}

	return (
		<ChartEditor
			chart={ chart }
			title={ title }
			onChange={ save }
			notice={ notice }
		/>
	);
}
