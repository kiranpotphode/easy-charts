/**
 * WordPress dependencies
 */
const { test, expect } = require( '@wordpress/e2e-test-utils-playwright' );

/**
 * Internal dependencies
 */
const { axe } = require( '../utils' );

const PAGES = [
	[ 'easy-charts-examples', '2.0 examples' ],
	[ 'easy-charts-1-x-examples', '1.x examples (converted)' ],
];

test.describe( 'Charts on the front end', () => {
	// Visitors: no admin bar, no editor notices.
	test.use( { storageState: { cookies: [], origins: [] } } );

	for ( const [ slug, name ] of PAGES ) {
		test( `draws the ${ name } accessibly`, async ( { page } ) => {
			await page.goto( `/${ slug }/` );

			const figures = page.locator( 'figure.easy-charts-chart' );
			await expect( figures ).toHaveCount( 12 );
			for ( const figure of await figures.all() ) {
				await expect( figure ).toHaveClass( /is-rendered/ );
				await expect(
					figure.locator( '.easy-charts-chart__canvas svg' )
				).toBeVisible();
				// One short description; the drawing itself is hidden from screen readers.
				await expect(
					figure.getByRole( 'img', {
						name: /chart\)?\. The values are in the table that follows\.$/,
					} )
				).toHaveCount( 1 );
				// The table is for screen readers once the chart is drawn.
				const table = figure.locator( '.easy-charts-chart__table' );
				await expect( table.locator( 'caption' ) ).not.toBeEmpty();
				await expect( table ).toHaveCSS( 'position', 'absolute' );
			}

			expect( await axe( page, '.easy-charts-chart' ) ).toEqual( [] );
		} );
	}

	test( 'no uvCharts scripts are loaded', async ( { page } ) => {
		const scripts = [];
		page.on( 'request', ( request ) => {
			if ( request.resourceType() === 'script' ) {
				scripts.push( request.url() );
			}
		} );
		await page.goto( '/easy-charts-1-x-examples/' );

		const ours = scripts.filter( ( url ) =>
			url.includes( '/plugins/easy-charts/' )
		);
		expect( ours.length ).toBeGreaterThan( 0 );
		for ( const url of ours ) {
			expect( url ).toMatch( /\/build\/(echarts|frontend)\.js/ );
		}
	} );

	test( 'honours reduced motion', async ( { page } ) => {
		// The `@wordpress/scripts` config emulates prefers-reduced-motion: reduce.
		await page.goto( '/easy-charts-examples/' );
		const animation = await page
			.locator( '.easy-charts-chart__canvas' )
			.first()
			.evaluate(
				( box ) =>
					window.easyChartsECharts.getInstanceByDom( box ).getOption()
						.animation
			);

		expect( animation ).toBe( false );
	} );

	test( 'downloads a PNG', async ( { page } ) => {
		await page.goto( '/easy-charts-1-x-examples/' );
		const button = page
			.getByRole( 'button', { name: / \(PNG image of "/ } )
			.first();
		await expect( button ).toBeVisible();

		const [ download ] = await Promise.all( [
			page.waitForEvent( 'download' ),
			button.click(),
		] );

		expect( download.suggestedFilename() ).toMatch( /\.png$/ );
	} );
} );

test.describe( 'Charts without JavaScript', () => {
	test.use( {
		storageState: { cookies: [], origins: [] },
		javaScriptEnabled: false,
	} );

	test( 'show their data table', async ( { page } ) => {
		await page.goto( '/easy-charts-examples/' );

		const table = page.locator( '.easy-charts-chart__table' ).first();
		await expect( table ).toBeVisible();
		await expect( table.locator( 'th' ).first() ).toBeVisible();
		await expect(
			page.locator( '.easy-charts-chart__download' ).first()
		).toBeHidden();
	} );
} );
