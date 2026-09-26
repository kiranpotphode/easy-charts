/**
 * Easy Charts → Settings: the site-wide chart settings (`easy_charts_settings` option).
 */

/**
 * WordPress dependencies
 */
import apiFetch from '@wordpress/api-fetch';
import {
	Button,
	Card,
	CardBody,
	Notice,
	SelectControl,
} from '@wordpress/components';
import { createRoot, useEffect, useMemo, useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { CHART_TYPE_OPTIONS, sampleData } from '../blocks/chart/chart-types';
import { mergeSettings, toSettings } from '../charts/settings';
import ChartPreview from '../components/chart-preview';
import { getEditorData } from '../components/editor-data';
import SettingsPanels from '../components/settings-panels';
import './settings.css';

/**
 * Settings page app.
 *
 * @return {Element} App.
 */
function SettingsApp() {
	const { baseSettings: defaults, settings: initial } = getEditorData();
	const [ saved, setSaved ] = useState( () => toSettings( initial ) );
	const [ overrides, setOverrides ] = useState( saved );
	const [ type, setType ] = useState( 'bar' );
	const [ status, setStatus ] = useState( { state: 'idle' } );
	const isDirty = JSON.stringify( overrides ) !== JSON.stringify( saved );

	useEffect( () => {
		if ( ! isDirty ) {
			return;
		}
		const warn = ( event ) => {
			event.preventDefault();
			event.returnValue = '';
		};
		window.addEventListener( 'beforeunload', warn );
		return () => window.removeEventListener( 'beforeunload', warn );
	}, [ isDirty ] );

	const sample = useMemo(
		() => ( {
			schema: 2,
			type,
			data: sampleData( type ),
			settings: {},
		} ),
		[ type ]
	);

	const save = async () => {
		setStatus( { state: 'saving' } );
		try {
			const response = await apiFetch( {
				path: '/wp/v2/settings',
				method: 'POST',
				data: { easy_charts_settings: overrides },
			} );
			const stored = toSettings( response.easy_charts_settings );
			setSaved( stored );
			setOverrides( stored );
			setStatus( {
				state: 'success',
				message: __( 'Settings saved.', 'easy-charts' ),
			} );
		} catch ( error ) {
			setStatus( { state: 'error', message: error.message } );
		}
	};

	return (
		<div className="easy-charts-settings">
			<div className="easy-charts-settings__panels">
				<SettingsPanels
					base={ defaults }
					overrides={ overrides }
					onChange={ ( next ) => {
						setOverrides( next );
						setStatus( { state: 'idle' } );
					} }
					initialOpen
				/>
			</div>
			<div className="easy-charts-settings__side">
				<Card>
					<CardBody>
						<SelectControl
							__next40pxDefaultSize
							__nextHasNoMarginBottom
							label={ __( 'Preview as', 'easy-charts' ) }
							value={ type }
							options={ CHART_TYPE_OPTIONS.map( ( option ) => ( {
								value: option.value,
								label: option.label,
							} ) ) }
							onChange={ setType }
						/>
						<div className="easy-charts-settings__preview">
							<ChartPreview
								doc={ sample }
								settings={ mergeSettings(
									defaults,
									overrides
								) }
								title={ __( 'Sample chart', 'easy-charts' ) }
							/>
						</div>
					</CardBody>
				</Card>
				{ status.message && (
					<Notice
						status={
							status.state === 'error' ? 'error' : 'success'
						}
						onRemove={ () => setStatus( { state: 'idle' } ) }
					>
						{ status.message }
					</Notice>
				) }
				<div className="easy-charts-settings__actions">
					<Button
						variant="primary"
						onClick={ save }
						isBusy={ status.state === 'saving' }
						disabled={ ! isDirty || status.state === 'saving' }
						accessibleWhenDisabled
					>
						{ __( 'Save settings', 'easy-charts' ) }
					</Button>
					<Button
						variant="tertiary"
						onClick={ () => setOverrides( saved ) }
						disabled={ ! isDirty }
						accessibleWhenDisabled
					>
						{ __( 'Discard changes', 'easy-charts' ) }
					</Button>
				</div>
			</div>
		</div>
	);
}

const root = document.getElementById( 'easy-charts-settings-root' );
if ( root ) {
	createRoot( root ).render( <SettingsApp /> );
}
