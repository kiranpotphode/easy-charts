/**
 * WordPress dependencies
 */
import {
	Button,
	Modal,
	TabPanel,
	TextareaControl,
} from '@wordpress/components';
import { useMemo, useRef, useState } from '@wordpress/element';
import { __, _n, sprintf } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import {
	fromGrid,
	parseTable,
	toGrid,
	toText,
} from '../../../charts/parse-table';
import DataGrid from './data-grid';

/**
 * Summary of a data grid for screen readers and sighted users.
 *
 * @param {Object|null} data Data grid.
 * @return {string} Status text.
 */
function summary( data ) {
	if ( ! data ) {
		return __(
			'The data needs row labels in the first column and at least one series column with a name.',
			'easy-charts'
		);
	}
	const series = data.columns.length - 1;
	return sprintf(
		/* translators: 1: "N rows", 2: "N series". */
		__( '%1$s, %2$s.', 'easy-charts' ),
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
}

/**
 * Modal for editing a chart's data, as a spreadsheet grid or as pasted text.
 *
 * @param {Object}                 props         Component props.
 * @param {Object}                 props.data    Current data grid.
 * @param {(data: Object) => void} props.onApply Called with the edited data grid.
 * @param {() => void}             props.onClose Called when the modal closes without changes.
 * @return {Element} Modal.
 */
export default function DataEditorModal( { data, onApply, onClose } ) {
	const [ draft, setDraft ] = useState( data );
	const [ text, setText ] = useState( () => toText( data ) );
	const [ gridKey, setGridKey ] = useState( 0 );
	const worksheetRef = useRef( null );
	const initialGrid = useMemo( () => toGrid( draft ), [ gridKey ] ); // eslint-disable-line react-hooks/exhaustive-deps

	const onSelectTab = ( tab ) => {
		// Hand the latest data to the tab being opened.
		if ( tab === 'text' ) {
			setText( toText( draft ) );
		} else {
			setGridKey( ( key ) => key + 1 );
		}
	};

	return (
		<Modal
			title={ __( 'Edit chart data', 'easy-charts' ) }
			onRequestClose={ onClose }
			size="large"
			className="easy-charts-data-editor"
		>
			<TabPanel
				className="easy-charts-data-editor__tabs"
				onSelect={ onSelectTab }
				tabs={ [
					{ name: 'table', title: __( 'Table', 'easy-charts' ) },
					{
						name: 'text',
						title: __( 'Paste as text', 'easy-charts' ),
					},
				] }
			>
				{ ( tab ) =>
					tab.name === 'table' ? (
						<div className="easy-charts-data-editor__table">
							<p className="easy-charts-data-editor__help">
								{ __(
									'First row: series names. First column: row labels. Right-click for more options; paste cells straight from a spreadsheet.',
									'easy-charts'
								) }
							</p>
							<DataGrid
								key={ gridKey }
								grid={ initialGrid }
								apiRef={ worksheetRef }
								label={ __( 'Chart data', 'easy-charts' ) }
								onChange={ ( grid ) =>
									setDraft( fromGrid( grid ) )
								}
							/>
							<div className="easy-charts-data-editor__grid-actions">
								<Button
									variant="secondary"
									onClick={ () =>
										worksheetRef.current?.insertRow()
									}
								>
									{ __( 'Add row', 'easy-charts' ) }
								</Button>
								<Button
									variant="secondary"
									onClick={ () =>
										worksheetRef.current?.insertColumn()
									}
								>
									{ __( 'Add series', 'easy-charts' ) }
								</Button>
							</div>
						</div>
					) : (
						<TextareaControl
							__nextHasNoMarginBottom
							label={ __( 'Data as text', 'easy-charts' ) }
							help={ __(
								'Tab-, comma- or semicolon-separated. Paste from a spreadsheet or CSV to replace all data.',
								'easy-charts'
							) }
							rows={ 14 }
							value={ text }
							onChange={ ( value ) => {
								setText( value );
								setDraft( parseTable( value ) );
							} }
							className="easy-charts-data-editor__text"
						/>
					)
				}
			</TabPanel>

			<p className="easy-charts-data-editor__status" aria-live="polite">
				{ summary( draft ) }
			</p>

			<div className="easy-charts-data-editor__footer">
				<Button variant="tertiary" onClick={ onClose }>
					{ __( 'Cancel', 'easy-charts' ) }
				</Button>
				<Button
					variant="primary"
					disabled={ ! draft }
					accessibleWhenDisabled
					onClick={ () => onApply( draft ) }
				>
					{ __( 'Apply', 'easy-charts' ) }
				</Button>
			</div>
		</Modal>
	);
}
