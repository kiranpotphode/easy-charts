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
	Modal,
	Notice,
	SelectControl,
} from '@wordpress/components';
import { createRoot, useEffect, useMemo, useState } from '@wordpress/element';
import { __, _n, sprintf } from '@wordpress/i18n';

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
 * Charts made with Easy Charts 1.x keep their 1.x look, and so ignore these settings, until they
 * are migrated. This card migrates all of them, in batches.
 *
 * @param {Object} props         Component props.
 * @param {number} props.pending Charts that keep their 1.x look.
 * @return {Element|null} Card, or nothing when there is nothing to migrate.
 */
function MigrationCard( { pending: initialPending } ) {
	const [ pending, setPending ] = useState( initialPending );
	const [ confirming, setConfirming ] = useState( false );
	const [ run, setRun ] = useState( { state: 'idle' } );

	if ( ! pending && run.state === 'idle' ) {
		return null;
	}

	const migrate = async () => {
		setConfirming( false );
		let migrated = 0;
		let failed = 0;
		let pages = 1;
		try {
			for ( let page = 1; page <= pages; page++ ) {
				setRun( { state: 'running', page, pages } );
				const result = await apiFetch( {
					path: '/easy-charts/v1/migration',
					method: 'POST',
					data: { page },
				} );
				migrated += result.migrated;
				failed += result.failed;
				pages = result.pages;
			}
			const status = await apiFetch( {
				path: '/easy-charts/v1/migration',
			} );
			setPending( status.pending );
			setRun( { state: 'done', migrated, failed } );
		} catch ( error ) {
			setRun( { state: 'error', message: error.message } );
		}
	};

	return (
		<Card className="easy-charts-settings__migration">
			<CardBody>
				<h2>{ __( 'Charts from Easy Charts 1.x', 'easy-charts' ) }</h2>
				{ pending > 0 && (
					<p>
						{ sprintf(
							/* translators: %d: number of charts. */
							_n(
								'%d chart made with Easy Charts 1.x keeps its 1.x look, so these settings don’t apply to it until it is migrated.',
								'%d charts made with Easy Charts 1.x keep their 1.x look, so these settings don’t apply to them until they are migrated.',
								pending,
								'easy-charts'
							),
							pending
						) }
					</p>
				) }
				{ run.state === 'running' && (
					<p role="status">
						{ sprintf(
							/* translators: 1: batch number, 2: number of batches. */
							__(
								'Migrating… batch %1$d of %2$d',
								'easy-charts'
							),
							run.page,
							run.pages
						) }
					</p>
				) }
				{ run.state === 'done' && (
					<Notice
						status={ run.failed ? 'warning' : 'success' }
						isDismissible={ false }
					>
						{ run.failed
							? sprintf(
									/* translators: 1: charts migrated, 2: charts that failed. */
									__(
										'Migrated %1$d charts. %2$d could not be migrated; try them one at a time in their editor.',
										'easy-charts'
									),
									run.migrated,
									run.failed
								)
							: sprintf(
									/* translators: %d: number of charts. */
									_n(
										'Migrated %d chart.',
										'Migrated %d charts.',
										run.migrated,
										'easy-charts'
									),
									run.migrated
								) }
					</Notice>
				) }
				{ run.state === 'error' && (
					<Notice status="error" isDismissible={ false }>
						{ run.message }
					</Notice>
				) }
				{ pending > 0 && (
					<Button
						variant="secondary"
						onClick={ () => setConfirming( true ) }
						isBusy={ run.state === 'running' }
						disabled={ run.state === 'running' }
						accessibleWhenDisabled
					>
						{ __( 'Migrate all to 2.0…', 'easy-charts' ) }
					</Button>
				) }
				<p className="description">
					{ __(
						'You can also migrate one chart at a time in its editor.',
						'easy-charts'
					) }
				</p>
			</CardBody>
			{ confirming && (
				<Modal
					title={ __( 'Migrate all 1.x charts?', 'easy-charts' ) }
					onRequestClose={ () => setConfirming( false ) }
					size="medium"
				>
					<p>
						{ __(
							'These charts will use the 2.0 styling and the site-wide chart settings for everything they didn’t change in Easy Charts 1.x, so their colours and fonts can change.',
							'easy-charts'
						) }
					</p>
					<p>
						{ __(
							'Their 1.x data is kept: going back to Easy Charts 1.x still shows the original charts.',
							'easy-charts'
						) }
					</p>
					<div className="easy-charts-settings__actions">
						<Button variant="primary" onClick={ migrate }>
							{ __( 'Migrate all', 'easy-charts' ) }
						</Button>
						<Button
							variant="tertiary"
							onClick={ () => setConfirming( false ) }
						>
							{ __( 'Cancel', 'easy-charts' ) }
						</Button>
					</div>
				</Modal>
			) }
		</Card>
	);
}

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
				<MigrationCard pending={ getEditorData().v1Pending ?? 0 } />
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
