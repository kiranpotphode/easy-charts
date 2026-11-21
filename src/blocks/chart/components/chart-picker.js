/**
 * WordPress dependencies
 */
import { Button, SearchControl, Spinner } from '@wordpress/components';
import { useEffect, useState } from '@wordpress/element';
import { __, sprintf } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { searchCharts } from '../api';
import { CHART_TYPE_OPTIONS } from '../chart-types';

/**
 * Search the site's charts and pick one.
 *
 * @param {Object}                                       props            Component props.
 * @param {number|undefined}                             props.selectedId Selected chart ID.
 * @param {(chart: {id: number, title: string}) => void} props.onSelect   Called with the chosen chart.
 * @return {Element} Picker.
 */
export default function ChartPicker( { selectedId, onSelect } ) {
	const [ search, setSearch ] = useState( '' );
	const [ results, setResults ] = useState( null );
	const [ error, setError ] = useState( '' );

	useEffect( () => {
		let active = true;
		const timer = setTimeout( () => {
			searchCharts( search )
				.then(
					( items ) =>
						active && ( setResults( items ), setError( '' ) )
				)
				.catch( ( e ) => active && setError( e.message ) );
		}, 250 );
		return () => {
			active = false;
			clearTimeout( timer );
		};
	}, [ search ] );

	const typeLabel = ( type ) =>
		CHART_TYPE_OPTIONS.find( ( option ) => option.value === type )?.label ??
		'';

	let list;
	if ( error ) {
		list = <p className="easy-charts-picker__message">{ error }</p>;
	} else if ( results === null ) {
		list = <Spinner />;
	} else if ( results.length === 0 ) {
		list = (
			<p className="easy-charts-picker__message">
				{ __( 'No charts found.', 'easy-charts' ) }
			</p>
		);
	} else {
		list = (
			<ul className="easy-charts-picker__list">
				{ results.map( ( item ) => (
					<li key={ item.id }>
						<Button
							className="easy-charts-picker__item"
							isPressed={ item.id === selectedId }
							onClick={ () => onSelect( item ) }
						>
							<span className="easy-charts-picker__title">
								{ item.title ||
									sprintf(
										/* translators: %d: chart ID. */
										__(
											'Untitled chart #%d',
											'easy-charts'
										),
										item.id
									) }
							</span>
							<span className="easy-charts-picker__meta">
								{ [
									typeLabel( item.type ),
									item.status !== 'publish'
										? item.status
										: '',
								]
									.filter( Boolean )
									.join( ' · ' ) }
							</span>
						</Button>
					</li>
				) ) }
			</ul>
		);
	}

	return (
		<div className="easy-charts-picker">
			<SearchControl
				__nextHasNoMarginBottom
				label={ __( 'Search charts', 'easy-charts' ) }
				value={ search }
				onChange={ setSearch }
			/>
			<div aria-live="polite">{ list }</div>
		</div>
	);
}
