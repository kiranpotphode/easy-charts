/**
 * WordPress dependencies
 */
import {
	BaseControl,
	Button,
	ColorPalette,
	PanelBody,
	RangeControl,
	SelectControl,
	TextControl,
	ToggleControl,
} from '@wordpress/components';
import { __, sprintf } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import {
	hasOverride,
	mergeSettings,
	removeOverrides,
	setOverride,
	toSettings,
} from '../charts/settings';
import PaletteControl from './palette-control';

const PIE_TYPES = [ 'pie', 'donut', 'polar-area' ];

/**
 * Panels for chart settings, shared by the chart block sidebar and the global settings page.
 *
 * Each control shows the effective value: `base` with `overrides` applied. Changing a control
 * stores that value in `overrides`; "Reset" removes it so the base value applies again.
 *
 * @param {Object}                      props               Component props.
 * @param {Object}                      props.base          Settings underneath (defaults, and for
 *                                                          a chart also the global settings).
 * @param {Object}                      props.overrides     Overrides being edited.
 * @param {(overrides: Object) => void} props.onChange      Called with the new overrides.
 * @param {string}                      [props.type]        Chart type; omitted on the global page.
 * @param {boolean}                     [props.initialOpen] Whether panels start open.
 * @return {Element} Panels.
 */
export default function SettingsPanels( {
	base,
	overrides: rawOverrides,
	onChange,
	type,
	initialOpen = false,
} ) {
	const overrides = toSettings( rawOverrides );
	const settings = mergeSettings( base, overrides );
	const isGlobal = ! type;
	const isAxis = isGlobal || ! PIE_TYPES.includes( type );

	const update = ( group, key, value ) =>
		onChange( setOverride( overrides, group, key, value ) );
	const reset = ( entries ) =>
		onChange( removeOverrides( overrides, entries ) );
	const value = ( group, key ) => settings[ group ]?.[ key ];

	/**
	 * One setting, with a "Reset" button while the chart overrides it.
	 *
	 * @param {string}  group   Settings group.
	 * @param {string}  key     Setting key.
	 * @param {string}  label   Setting name, for the reset button.
	 * @param {Element} control The control.
	 * @return {Element} Setting.
	 */
	const item = ( group, key, label, control ) => (
		<div key={ `${ group }.${ key }` } className="easy-charts-setting">
			{ control }
			{ hasOverride( overrides, group, key ) && (
				<Button
					variant="link"
					className="easy-charts-setting__reset"
					label={ sprintf(
						/* translators: %s: setting name. */
						__( 'Reset %s to the default', 'easy-charts' ),
						label
					) }
					showTooltip={ false }
					onClick={ () => reset( [ [ group, key ] ] ) }
				>
					{ __( 'Reset', 'easy-charts' ) }
				</Button>
			) }
		</div>
	);

	/**
	 * A collapsible panel of settings with "Reset all" while any is overridden.
	 *
	 * @param {string}                  label   Panel title.
	 * @param {Array<[string, string]>} entries Settings in the panel.
	 * @param {Element[]}               items   Settings.
	 * @return {Element} Panel.
	 */
	const panel = ( label, entries, items ) => (
		<PanelBody title={ label } initialOpen={ initialOpen }>
			{ items }
			{ entries.some( ( [ group, key ] ) =>
				hasOverride( overrides, group, key )
			) && (
				<Button
					variant="secondary"
					size="small"
					onClick={ () => reset( entries ) }
				>
					{ sprintf(
						/* translators: %s: panel title. */
						__( 'Reset all %s settings', 'easy-charts' ),
						label.toLowerCase()
					) }
				</Button>
			) }
		</PanelBody>
	);

	const toggle = ( group, key, label ) =>
		item(
			group,
			key,
			label,
			<ToggleControl
				__nextHasNoMarginBottom
				label={ label }
				checked={ !! value( group, key ) }
				onChange={ ( checked ) => update( group, key, checked ) }
			/>
		);
	const text = ( group, key, label, help ) =>
		item(
			group,
			key,
			label,
			<TextControl
				__next40pxDefaultSize
				__nextHasNoMarginBottom
				label={ label }
				help={ help }
				value={ value( group, key ) ?? '' }
				onChange={ ( next ) => update( group, key, next ) }
			/>
		);
	const range = ( group, key, label, min, max, step = 1 ) =>
		item(
			group,
			key,
			label,
			<RangeControl
				__next40pxDefaultSize
				__nextHasNoMarginBottom
				label={ label }
				min={ min }
				max={ max }
				step={ step }
				value={ Number( value( group, key ) ) || min }
				onChange={ ( next ) =>
					update(
						group,
						key,
						Math.min( max, Math.max( min, Number( next ) || min ) )
					)
				}
			/>
		);
	const select = ( group, key, label, options ) =>
		item(
			group,
			key,
			label,
			<SelectControl
				__next40pxDefaultSize
				__nextHasNoMarginBottom
				label={ label }
				value={ value( group, key ) }
				options={ options }
				onChange={ ( next ) => update( group, key, next ) }
			/>
		);

	return (
		<>
			{ panel(
				__( 'Title', 'easy-charts' ),
				isGlobal
					? [ [ 'title', 'show' ] ]
					: [
							[ 'title', 'text' ],
							[ 'title', 'subtext' ],
							[ 'title', 'show' ],
						],
				[
					! isGlobal &&
						text( 'title', 'text', __( 'Title', 'easy-charts' ) ),
					! isGlobal &&
						text(
							'title',
							'subtext',
							__( 'Subtitle', 'easy-charts' )
						),
					toggle(
						'title',
						'show',
						__( 'Show title', 'easy-charts' )
					),
				]
			) }

			{ panel(
				__( 'Size', 'easy-charts' ),
				[
					[ 'size', 'height' ],
					[ 'size', 'responsive' ],
					[ 'size', 'width' ],
				],
				[
					range(
						'size',
						'height',
						__( 'Height (px)', 'easy-charts' ),
						150,
						1200,
						10
					),
					toggle(
						'size',
						'responsive',
						__( 'Use the full available width', 'easy-charts' )
					),
					! value( 'size', 'responsive' ) &&
						range(
							'size',
							'width',
							__( 'Width (px)', 'easy-charts' ),
							150,
							2000,
							10
						),
				]
			) }

			{ isAxis &&
				panel(
					__( 'Layout & axes', 'easy-charts' ),
					[
						[ 'layout', 'orientation' ],
						[ 'axis', 'xLabel' ],
						[ 'axis', 'yLabel' ],
					],
					[
						select(
							'layout',
							'orientation',
							__( 'Orientation', 'easy-charts' ),
							[
								{
									value: 'vertical',
									label: __( 'Vertical', 'easy-charts' ),
								},
								{
									value: 'horizontal',
									label: __( 'Horizontal', 'easy-charts' ),
								},
							]
						),
						text(
							'axis',
							'xLabel',
							__( 'Category axis label', 'easy-charts' ),
							__( 'The axis with the row labels.', 'easy-charts' )
						),
						text(
							'axis',
							'yLabel',
							__( 'Value axis label', 'easy-charts' )
						),
					]
				) }

			{ panel(
				__( 'Legend & tooltip', 'easy-charts' ),
				[
					[ 'legend', 'show' ],
					[ 'legend', 'position' ],
					[ 'tooltip', 'show' ],
				],
				[
					toggle(
						'legend',
						'show',
						__( 'Show legend', 'easy-charts' )
					),
					select(
						'legend',
						'position',
						__( 'Legend position', 'easy-charts' ),
						[
							{ value: 'top', label: __( 'Top', 'easy-charts' ) },
							{
								value: 'bottom',
								label: __( 'Bottom', 'easy-charts' ),
							},
							{
								value: 'left',
								label: __( 'Left', 'easy-charts' ),
							},
							{
								value: 'right',
								label: __( 'Right', 'easy-charts' ),
							},
						]
					),
					toggle(
						'tooltip',
						'show',
						__( 'Show tooltips', 'easy-charts' )
					),
				]
			) }

			{ panel(
				__( 'Value labels', 'easy-charts' ),
				[
					[ 'labels', 'show' ],
					[ 'labels', 'precision' ],
					[ 'labels', 'prefix' ],
					[ 'labels', 'suffix' ],
				],
				[
					toggle(
						'labels',
						'show',
						__( 'Show value labels', 'easy-charts' )
					),
					range(
						'labels',
						'precision',
						__( 'Decimal places', 'easy-charts' ),
						0,
						10
					),
					text(
						'labels',
						'prefix',
						__( 'Prefix', 'easy-charts' ),
						__( 'For example $', 'easy-charts' )
					),
					text(
						'labels',
						'suffix',
						__( 'Suffix', 'easy-charts' ),
						__( 'For example % or " kg"', 'easy-charts' )
					),
				]
			) }

			{ panel(
				__( 'Colours', 'easy-charts' ),
				[
					[ 'palette', 'colors' ],
					[ 'frame', 'background' ],
				],
				[
					item(
						'palette',
						'colors',
						__( 'Series colours', 'easy-charts' ),
						<PaletteControl
							colors={ value( 'palette', 'colors' ) ?? [] }
							onChange={ ( colors ) =>
								update( 'palette', 'colors', colors )
							}
						/>
					),
					item(
						'frame',
						'background',
						__( 'Background', 'easy-charts' ),
						<BaseControl
							__nextHasNoMarginBottom
							id="easy-charts-background"
							label={ __( 'Background', 'easy-charts' ) }
						>
							<ColorPalette
								value={ value( 'frame', 'background' ) }
								colors={ [
									{
										name: __( 'White', 'easy-charts' ),
										color: '#ffffff',
									},
									{
										name: __( 'Light grey', 'easy-charts' ),
										color: '#f4f4f4',
									},
									{
										name: __( 'Dark', 'easy-charts' ),
										color: '#1e1e1e',
									},
								] }
								onChange={ ( color ) =>
									color
										? update( 'frame', 'background', color )
										: reset( [ [ 'frame', 'background' ] ] )
								}
							/>
						</BaseControl>
					),
				]
			) }

			{ panel(
				__( 'Download', 'easy-charts' ),
				[
					[ 'download', 'enabled' ],
					[ 'download', 'label' ],
				],
				[
					toggle(
						'download',
						'enabled',
						__( 'Show a "Download PNG" button', 'easy-charts' )
					),
					value( 'download', 'enabled' ) &&
						text(
							'download',
							'label',
							__( 'Button label', 'easy-charts' )
						),
				]
			) }
		</>
	);
}
