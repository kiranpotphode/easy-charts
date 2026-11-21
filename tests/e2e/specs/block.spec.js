/**
 * WordPress dependencies
 */
const { test, expect } = require( '@wordpress/e2e-test-utils-playwright' );

/**
 * Internal dependencies
 */
const { axe, EDITOR_CHART, findChart } = require( '../utils' );

/**
 * The chart block in the editor canvas.
 *
 * @param {Object} editor Editor utils.
 * @return {import('@playwright/test').Locator} Block.
 */
const chartBlock = ( editor ) =>
	editor.canvas.getByRole( 'document', { name: 'Block: Chart' } );

/**
 * Decode the JSON config of the first chart on a front-end page.
 *
 * @param {import('@playwright/test').Page} page Page.
 * @return {Promise<Object>} Chart config.
 */
const frontendConfig = async ( page ) =>
	JSON.parse(
		await page.locator( '.easy-charts-chart__config' ).first().textContent()
	);

test.describe( 'Chart block', () => {
	test.beforeEach( async ( { admin } ) => {
		await admin.createNewPost( { title: 'E2E chart block' } );
	} );

	test( 'new chart: wizard, data editor, sidebar, published', async ( {
		editor,
		page,
	} ) => {
		await editor.insertBlock( { name: 'easy-charts/chart' } );
		const block = chartBlock( editor );

		await block.getByRole( 'button', { name: 'New chart' } ).click();
		await block
			.getByRole( 'button', { name: 'Line', exact: true } )
			.click();

		// New charts open the data editor with sample data.
		const modal = page.getByRole( 'dialog', { name: 'Edit chart data' } );
		await expect( modal ).toBeVisible();
		expect( await axe( page, '.components-modal__frame' ) ).toEqual( [] );
		await modal.getByRole( 'button', { name: 'Apply' } ).click();
		await expect( modal ).toBeHidden();

		await expect(
			block.locator( '.easy-charts-preview svg' )
		).toBeVisible();
		expect( await axe( page, EDITOR_CHART ) ).toEqual( [] );

		// A sidebar change is stored as the chart's own setting.
		await editor.openDocumentSettingsSidebar();
		const sidebar = page.getByRole( 'region', { name: 'Editor settings' } );
		await sidebar
			.getByRole( 'button', { name: 'Legend & tooltip' } )
			.click();
		await sidebar
			.getByRole( 'combobox', { name: 'Legend position' } )
			.selectOption( 'top' );

		const postId = await editor.publishPost();
		await page.goto( `/?p=${ postId }` );

		await expect(
			page.locator( 'figure.easy-charts-chart--line.is-rendered' )
		).toBeVisible();
		expect(
			( await frontendConfig( page ) ).settings.legend.position
		).toBe( 'top' );
	} );

	test( 'pasted data', async ( { editor, page } ) => {
		await editor.insertBlock( { name: 'easy-charts/chart' } );
		const block = chartBlock( editor );

		await block.getByRole( 'button', { name: 'Paste data' } ).click();
		await block
			.getByRole( 'textbox', { name: 'Data' } )
			.fill( 'Fruit,Sold\nApples,12\nPears,7\nPlums,3' );
		await expect(
			block.getByText( 'Found 3 rows and 1 series.' )
		).toBeVisible();
		await block.getByRole( 'button', { name: 'Continue' } ).click();
		await block.getByRole( 'button', { name: 'Pie', exact: true } ).click();

		// Pasted data goes straight to the preview.
		await expect(
			page.getByRole( 'dialog', { name: 'Edit chart data' } )
		).toBeHidden();
		await expect(
			block.locator( '.easy-charts-preview svg' )
		).toBeVisible();

		const [ attributes ] = await editor.getBlocks();
		expect( attributes.attributes.chart.data.rows ).toEqual( [
			[ 'Apples', 12 ],
			[ 'Pears', 7 ],
			[ 'Plums', 3 ],
		] );
	} );

	test( 'embeds an existing chart', async ( {
		editor,
		page,
		requestUtils,
	} ) => {
		const chartId = await findChart( requestUtils, 'Browser share' );
		await editor.insertBlock( { name: 'easy-charts/chart' } );
		const block = chartBlock( editor );

		await block
			.getByRole( 'button', { name: 'Use an existing chart' } )
			.click();
		await block
			.getByRole( 'searchbox', { name: /search/i } )
			.fill( 'Browser share' );
		await block.getByRole( 'button', { name: /Browser share/ } ).click();
		await block.getByRole( 'button', { name: 'Embed' } ).click();

		await expect(
			block.locator( '.easy-charts-preview svg' )
		).toBeVisible();
		const [ embedded ] = await editor.getBlocks();
		expect( embedded.attributes.chartId ).toBe( chartId );

		const postId = await editor.publishPost();
		await page.goto( `/?p=${ postId }` );
		await expect(
			page.locator( `figure[data-chart-id="${ chartId }"].is-rendered` )
		).toBeVisible();
	} );

	test( 'data editor works with the keyboard', async ( { editor, page } ) => {
		await editor.insertBlock( { name: 'easy-charts/chart' } );
		const block = chartBlock( editor );

		await block.getByRole( 'button', { name: 'New chart' } ).focus();
		await page.keyboard.press( 'Enter' );
		await block.getByRole( 'button', { name: 'Bar', exact: true } ).focus();
		await page.keyboard.press( 'Enter' );

		const modal = page.getByRole( 'dialog', { name: 'Edit chart data' } );
		await expect( modal ).toBeVisible();
		await modal.getByRole( 'tab', { name: 'Paste as text' } ).focus();
		await page.keyboard.press( 'Enter' );
		const text = modal.getByRole( 'textbox', { name: 'Data as text' } );
		await text.fill( 'Year\tSales\n2024\t5\n2025\t8' );
		await modal.getByRole( 'button', { name: 'Apply' } ).focus();
		await page.keyboard.press( 'Enter' );
		await expect( modal ).toBeHidden();

		const [ chart ] = await editor.getBlocks();
		expect( chart.attributes.chart.data ).toEqual( {
			columns: [ 'Year', 'Sales' ],
			rows: [
				[ '2024', 5 ],
				[ '2025', 8 ],
			],
		} );

		// Escape closes the modal without applying changes.
		await editor.clickBlockToolbarButton( 'Edit data' );
		await expect( modal ).toBeVisible();
		await page.keyboard.press( 'Escape' );
		await expect( modal ).toBeHidden();
	} );

	test( 'shortcode transforms into a block', async ( {
		editor,
		requestUtils,
	} ) => {
		const chartId = await findChart( requestUtils, 'Browser share' );
		await editor.insertBlock( {
			name: 'core/shortcode',
			attributes: { text: `[easy_chart chart_id="${ chartId }"]` },
		} );
		await editor.transformBlockTo( 'easy-charts/chart' );

		const [ block ] = await editor.getBlocks();
		expect( block.name ).toBe( 'easy-charts/chart' );
		expect( block.attributes.chartId ).toBe( chartId );
	} );
} );
