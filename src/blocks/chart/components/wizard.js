/**
 * WordPress dependencies
 */
import {
	Button,
	Notice,
	Placeholder,
	TextareaControl,
} from '@wordpress/components';
import { useMemo, useState } from '@wordpress/element';
import { __, _n, sprintf } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { parseTable } from '../../../charts/parse-table';
import { toSettings } from '../../../charts/settings';
import { getChart } from '../api';
import { newChart, sampleData } from '../chart-types';
import ChartPicker from './chart-picker';
import TypePicker from './type-picker';

/**
 * Step: paste data from a spreadsheet or CSV.
 *
 * @param {Object}                 props          Component props.
 * @param {string}                 props.text     Pasted text.
 * @param {(text: string) => void} props.onChange Called with new text.
 * @return {Element} Paste step.
 */
function PasteStep( { text, onChange } ) {
	const data = useMemo( () => parseTable( text ), [ text ] );
	let status = '';
	if ( data ) {
		const series = data.columns.length - 1;
		status = sprintf(
			/* translators: 1: number of rows, 2: number of series. */
			__( 'Found %1$s and %2$s.', 'easy-charts' ),
			sprintf(
				/* translators: %d: number of rows. */
				_n( '%d row', '%d rows', data.rows.length, 'easy-charts' ),
				data.rows.length
			),
			sprintf(
				/* translators: %d: number of data series (columns). */
				_n( '%d series', '%d series', series, 'easy-charts' ),
				series
			)
		);
	} else if ( text.trim() !== '' ) {
		status = __(
			'Paste at least two columns: row labels, then one column per series.',
			'easy-charts'
		);
	}

	return (
		<>
			<TextareaControl
				__nextHasNoMarginBottom
				label={ __( 'Data', 'easy-charts' ) }
				help={ __(
					'Copy cells from Excel, Numbers or Google Sheets, or paste CSV. The first row holds the series names, the first column the row labels.',
					'easy-charts'
				) }
				rows={ 8 }
				value={ text }
				onChange={ onChange }
				className="easy-charts-wizard__paste"
			/>
			<p className="easy-charts-wizard__status" aria-live="polite">
				{ status }
			</p>
		</>
	);
}

/**
 * Step: pick an existing chart, then embed it or copy its data or settings.
 *
 * @param {Object}                                                props          Component props.
 * @param {(chart: Object, options: {editData: boolean}) => void} props.onCreate Called with a new chart.
 * @param {(chartId: number) => void}                             props.onEmbed  Called to embed a chart.
 * @param {Element}                                               props.back     Back button.
 * @return {Element} Existing-chart step.
 */
function ExistingStep( { onCreate, onEmbed, back } ) {
	const [ selected, setSelected ] = useState( null );
	const [ busy, setBusy ] = useState( false );
	const [ error, setError ] = useState( '' );

	const copy = async ( what ) => {
		setBusy( true );
		setError( '' );
		try {
			const source = await getChart( selected.id );
			const doc = source.document;
			if ( what === 'data' ) {
				onCreate( newChart( doc.type, doc.data ), { editData: false } );
			} else {
				onCreate(
					{
						...newChart( doc.type, sampleData( doc.type ) ),
						settings: toSettings( doc.settings ),
					},
					{ editData: true }
				);
			}
		} catch ( e ) {
			setError( e.message );
			setBusy( false );
		}
	};

	return (
		<div className="easy-charts-wizard__step">
			<ChartPicker selectedId={ selected?.id } onSelect={ setSelected } />
			{ error && (
				<Notice status="error" isDismissible={ false }>
					{ error }
				</Notice>
			) }
			<div className="easy-charts-wizard__choices">
				<Button
					variant="primary"
					disabled={ ! selected || busy }
					accessibleWhenDisabled
					onClick={ () => onEmbed( selected.id ) }
					description={ __(
						'Show the chart here. Later changes to it appear everywhere it is used.',
						'easy-charts'
					) }
				>
					{ __( 'Embed', 'easy-charts' ) }
				</Button>
				<Button
					variant="secondary"
					disabled={ ! selected || busy }
					accessibleWhenDisabled
					onClick={ () => copy( 'data' ) }
					description={ __(
						'Start a new chart in this block with a copy of its type and data.',
						'easy-charts'
					) }
				>
					{ __( 'Copy data', 'easy-charts' ) }
				</Button>
				<Button
					variant="secondary"
					disabled={ ! selected || busy }
					accessibleWhenDisabled
					onClick={ () => copy( 'settings' ) }
					description={ __(
						'Start a new chart in this block with its type and settings, and your own data.',
						'easy-charts'
					) }
				>
					{ __( 'Copy settings', 'easy-charts' ) }
				</Button>
			</div>
			<p className="easy-charts-wizard__status">
				{ __(
					'Embed shows the chart itself. Copies are independent: changing them never changes the original.',
					'easy-charts'
				) }
			</p>
			<div className="easy-charts-wizard__actions">{ back }</div>
		</div>
	);
}

/**
 * Placeholder wizard shown until the block has a chart.
 *
 * @param {Object}                                                props          Component props.
 * @param {(chart: Object, options: {editData: boolean}) => void} props.onCreate Called with a new chart.
 * @param {(chartId: number) => void}                             props.onEmbed  Called to embed a chart.
 * @return {Element} Wizard.
 */
export default function Wizard( { onCreate, onEmbed } ) {
	const [ step, setStep ] = useState( 'start' );
	const [ text, setText ] = useState( '' );
	const pasted = useMemo( () => parseTable( text ), [ text ] );

	const back = (
		<Button variant="tertiary" onClick={ () => setStep( 'start' ) }>
			{ __( 'Back', 'easy-charts' ) }
		</Button>
	);

	let content;
	if ( step === 'paste' ) {
		content = (
			<div className="easy-charts-wizard__step">
				<PasteStep text={ text } onChange={ setText } />
				<div className="easy-charts-wizard__actions">
					{ back }
					<Button
						variant="primary"
						disabled={ ! pasted }
						accessibleWhenDisabled
						onClick={ () => setStep( 'pasted-type' ) }
					>
						{ __( 'Continue', 'easy-charts' ) }
					</Button>
				</div>
			</div>
		);
	} else if ( step === 'new-type' || step === 'pasted-type' ) {
		const fromPaste = step === 'pasted-type' && pasted;
		content = (
			<div className="easy-charts-wizard__step">
				<TypePicker
					label={ __( 'Choose a chart type', 'easy-charts' ) }
					onChange={ ( type ) =>
						onCreate(
							newChart(
								type,
								fromPaste ? pasted : sampleData( type )
							),
							{ editData: ! fromPaste }
						)
					}
				/>
				<div className="easy-charts-wizard__actions">{ back }</div>
			</div>
		);
	} else if ( step === 'existing' ) {
		content = (
			<ExistingStep
				onCreate={ onCreate }
				onEmbed={ onEmbed }
				back={ back }
			/>
		);
	} else {
		content = (
			<div className="easy-charts-wizard__start">
				<Button
					variant="primary"
					onClick={ () => setStep( 'new-type' ) }
				>
					{ __( 'New chart', 'easy-charts' ) }
				</Button>
				<Button
					variant="secondary"
					onClick={ () => setStep( 'paste' ) }
				>
					{ __( 'Paste data', 'easy-charts' ) }
				</Button>
				<Button
					variant="secondary"
					onClick={ () => setStep( 'existing' ) }
				>
					{ __( 'Use an existing chart', 'easy-charts' ) }
				</Button>
			</div>
		);
	}

	return (
		<Placeholder
			icon="chart-bar"
			label={ __( 'Chart', 'easy-charts' ) }
			instructions={
				step === 'start'
					? __(
							'Start a new chart with sample data, paste your own data, or reuse a chart you already made.',
							'easy-charts'
						)
					: undefined
			}
			className="easy-charts-wizard"
		>
			{ content }
		</Placeholder>
	);
}
