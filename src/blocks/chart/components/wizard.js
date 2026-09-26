/**
 * WordPress dependencies
 */
import { Button, Placeholder, TextareaControl } from '@wordpress/components';
import { useMemo, useState } from '@wordpress/element';
import { __, _n, sprintf } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { parseTable } from '../../../charts/parse-table';
import { newChart, sampleData } from '../chart-types';
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
 * Placeholder wizard shown until the block has a chart.
 *
 * @param {Object}                  props          Component props.
 * @param {(chart: Object) => void} props.onCreate Called with the new chart document.
 * @return {Element} Wizard.
 */
export default function Wizard( { onCreate } ) {
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
		content = (
			<div className="easy-charts-wizard__step">
				<TypePicker
					label={ __( 'Choose a chart type', 'easy-charts' ) }
					onChange={ ( type ) =>
						onCreate(
							newChart(
								type,
								step === 'pasted-type' && pasted
									? pasted
									: sampleData( type )
							)
						)
					}
				/>
				<div className="easy-charts-wizard__actions">{ back }</div>
			</div>
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
							'Start a new chart with sample data, or paste your own data.',
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
