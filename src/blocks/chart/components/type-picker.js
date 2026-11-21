/**
 * WordPress dependencies
 */
import { Button } from '@wordpress/components';

/**
 * Internal dependencies
 */
import { CHART_TYPE_OPTIONS } from '../chart-types';

/**
 * Grid of chart types.
 *
 * @param {Object}                 props          Component props.
 * @param {string}                 props.label    Group label.
 * @param {string}                 props.value    Selected type.
 * @param {(type: string) => void} props.onChange Called with the chosen type.
 * @return {Element} Type picker.
 */
export default function TypePicker( { label, value, onChange } ) {
	return (
		<fieldset className="easy-charts-type-picker">
			<legend className="easy-charts-type-picker__legend">
				{ label }
			</legend>
			<div className="easy-charts-type-picker__grid">
				{ CHART_TYPE_OPTIONS.map( ( option ) => (
					<Button
						key={ option.value }
						className="easy-charts-type-picker__option"
						isPressed={ option.value === value }
						onClick={ () => onChange( option.value ) }
					>
						{ option.icon }
						<span>{ option.label }</span>
					</Button>
				) ) }
			</div>
		</fieldset>
	);
}
