/**
 * WordPress dependencies
 */
import {
	BaseControl,
	Button,
	ColorIndicator,
	ColorPicker,
	Dropdown,
} from '@wordpress/components';
import { __, sprintf } from '@wordpress/i18n';

/**
 * Colours ECharts uses when a chart has no palette of its own.
 */
export const ECHARTS_COLORS = [
	'#5070dd',
	'#b6d634',
	'#505372',
	'#ff994d',
	'#0ca8df',
	'#ffd10a',
	'#fb628b',
	'#785db0',
	'#3fbe95',
];

/**
 * Edit the list of series colours.
 *
 * @param {Object}                     props          Component props.
 * @param {string[]}                   props.colors   Current colours (empty: ECharts defaults).
 * @param {(colors: string[]) => void} props.onChange Called with the new list.
 * @return {Element} Palette control.
 */
export default function PaletteControl( { colors, onChange } ) {
	const shown = colors.length ? colors : ECHARTS_COLORS;
	const replace = ( index, color ) =>
		onChange( shown.map( ( c, i ) => ( i === index ? color : c ) ) );

	return (
		<BaseControl
			__nextHasNoMarginBottom
			id="easy-charts-palette"
			label={ __( 'Series colours', 'easy-charts' ) }
			help={
				colors.length
					? __(
							'Used in order, one per series or slice.',
							'easy-charts'
						)
					: __(
							'Default colours. Pick one to customise.',
							'easy-charts'
						)
			}
		>
			<div className="easy-charts-palette">
				{ shown.map( ( color, index ) => (
					<Dropdown
						key={ index }
						popoverProps={ { placement: 'left-start' } }
						renderToggle={ ( { isOpen, onToggle } ) => (
							<Button
								className="easy-charts-palette__swatch"
								onClick={ onToggle }
								aria-expanded={ isOpen }
								label={ sprintf(
									/* translators: 1: colour position, 2: colour value. */
									__( 'Colour %1$d: %2$s', 'easy-charts' ),
									index + 1,
									color
								) }
							>
								<ColorIndicator colorValue={ color } />
							</Button>
						) }
						renderContent={ () => (
							<div className="easy-charts-palette__picker">
								<ColorPicker
									color={ color }
									onChange={ ( value ) =>
										replace( index, value )
									}
								/>
								{ colors.length > 1 && (
									<Button
										variant="secondary"
										isDestructive
										onClick={ () =>
											onChange(
												shown.filter(
													( _, i ) => i !== index
												)
											)
										}
									>
										{ __( 'Remove colour', 'easy-charts' ) }
									</Button>
								) }
							</div>
						) }
					/>
				) ) }
				{ colors.length > 0 && (
					<Button
						variant="secondary"
						size="small"
						onClick={ () =>
							onChange( [
								...colors,
								ECHARTS_COLORS[
									colors.length % ECHARTS_COLORS.length
								],
							] )
						}
					>
						{ __( 'Add colour', 'easy-charts' ) }
					</Button>
				) }
			</div>
		</BaseControl>
	);
}
