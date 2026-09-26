/**
 * WordPress dependencies
 */
const { test, expect } = require( '@wordpress/e2e-test-utils-playwright' );

/**
 * Internal dependencies
 */
const { axe, EDITOR_CHART, findChart, wp } = require( '../utils' );

/**
 * How many 2.0 documents a chart has stored (0 or 1).
 *
 * @param {number} chartId Chart post ID.
 * @return {string} Count.
 */
const storedDocuments = ( chartId ) =>
	wp(
		`post meta list ${ chartId } --keys=_easy_charts_chart --format=count`
	);

test.describe( 'Chart post editor', () => {
	test( 'opens a 1.x chart converted and stores it as 2.0 on save', async ( {
		admin,
		editor,
		page,
		requestUtils,
	} ) => {
		const chartId = await findChart( requestUtils, '1.x donut' );
		const v1Data = wp(
			`post meta get ${ chartId } _easy_charts_chart_data`
		);
		await admin.editPost( chartId );

		const block = editor.canvas.getByRole( 'document', {
			name: 'Block: Chart',
		} );
		await expect(
			block.locator( '.easy-charts-preview svg' )
		).toBeVisible();

		await editor.selectBlocks( block );
		await editor.openDocumentSettingsSidebar();
		const sidebar = page.getByRole( 'region', { name: 'Editor settings' } );
		await expect(
			sidebar.getByText( 'This chart was made with Easy Charts 1.x' )
		).toBeVisible();
		expect( await axe( page, EDITOR_CHART ) ).toEqual( [] );

		// Opening a 1.x chart changes nothing.
		expect( storedDocuments( chartId ) ).toBe( '0' );

		await sidebar
			.getByRole( 'button', { name: 'Legend & tooltip' } )
			.click();
		await sidebar.getByRole( 'checkbox', { name: 'Show legend' } ).click();
		await page
			.getByRole( 'region', { name: 'Editor top bar' } )
			.getByRole( 'button', { name: 'Save', exact: true } )
			.click();
		await expect(
			page
				.getByRole( 'button', { name: 'Dismiss this notice' } )
				.filter( {
					hasText: 'Chart updated.',
				} )
		).toBeVisible();

		const saved = await requestUtils.rest( {
			path: `/easy-charts/v1/charts/${ chartId }`,
		} );
		expect( saved.document.type ).toBe( 'donut' );
		expect( saved.settings.legend.show ).toBe( false );
		expect( storedDocuments( chartId ) ).toBe( '1' );
		// The 1.x data is never touched.
		expect(
			wp( `post meta get ${ chartId } _easy_charts_chart_data` )
		).toBe( v1Data );
	} );

	test( 'shows the shortcode for the chart', async ( {
		admin,
		editor,
		page,
		requestUtils,
	} ) => {
		const chartId = await findChart( requestUtils, 'Budget allocation' );
		await admin.editPost( chartId );
		await editor.openDocumentSettingsSidebar();

		const panel = page.getByRole( 'button', { name: 'Use this chart' } );
		if ( ( await panel.getAttribute( 'aria-expanded' ) ) === 'false' ) {
			await panel.click();
		}
		await expect(
			page.getByRole( 'textbox', { name: 'Shortcode' } )
		).toHaveValue( `[easy_chart chart_id="${ chartId }"]` );
	} );

	test( 'creates a new chart', async ( { admin, editor, page } ) => {
		await admin.createNewPost( {
			postType: 'easy_charts',
			title: 'E2E new chart',
		} );
		const block = editor.canvas.getByRole( 'document', {
			name: 'Block: Chart',
		} );

		// Embedding a chart into a chart makes no sense: only copies are offered.
		await block
			.getByRole( 'button', { name: 'Use an existing chart' } )
			.click();
		await expect(
			block.getByRole( 'button', { name: 'Embed' } )
		).toHaveCount( 0 );
		await block.getByRole( 'button', { name: 'Back' } ).click();

		await block.getByRole( 'button', { name: 'New chart' } ).click();
		await block
			.getByRole( 'button', { name: 'Waterfall', exact: true } )
			.click();
		await page
			.getByRole( 'dialog', { name: 'Edit chart data' } )
			.getByRole( 'button', { name: 'Apply' } )
			.click();
		const postId = await editor.publishPost();

		expect(
			JSON.parse(
				wp(
					`post meta get ${ postId } _easy_charts_chart --format=json`
				)
			).type
		).toBe( 'waterfall' );
	} );
} );
