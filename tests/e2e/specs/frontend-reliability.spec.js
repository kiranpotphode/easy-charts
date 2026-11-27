/**
 * WordPress dependencies
 */
const { test, expect } = require( '@wordpress/e2e-test-utils-playwright' );

/**
 * Internal dependencies
 */
const { findChart } = require( '../utils' );

const FIGURE = 'figure.easy-charts-chart';

/**
 * A raw HTML block (the page is created by an administrator, who may post HTML).
 *
 * @param {string} markup HTML.
 * @return {string} Block markup.
 */
const html = ( markup ) => `<!-- wp:html -->${ markup }<!-- /wp:html -->`;

/**
 * A chart figure whose config can't be drawn, with its data table.
 *
 * @param {string} config Contents of the config script.
 * @return {string} HTML.
 */
const brokenChart = ( config ) =>
	`<figure class="easy-charts-chart"><div class="easy-charts-chart__canvas" style="height:300px;"></div>` +
	`<script type="application/json" class="easy-charts-chart__config">${ config }</script>` +
	`<div class="easy-charts-chart__data"><table class="easy-charts-chart__table"><caption>Broken</caption>` +
	`<tbody><tr><th scope="row">A</th><td>1</td></tr></tbody></table></div></figure>`;

/**
 * Width of a chart's SVG and of its box.
 *
 * @param {import('@playwright/test').Locator} wrapper Element holding one chart.
 * @return {Promise<{svg: number, box: number}>} Widths in px.
 */
const widths = ( wrapper ) =>
	wrapper.locator( '.easy-charts-chart__canvas' ).evaluate( ( box ) => ( {
		svg: box.querySelector( 'svg' )?.getBoundingClientRect().width ?? 0,
		box: box.getBoundingClientRect().width,
	} ) );

test.describe( 'Charts drawn in difficult places', () => {
	// Visitors: no admin bar, no editor notices.
	test.use( { storageState: { cookies: [], origins: [] } } );

	let page_;

	test.beforeAll( async ( { requestUtils } ) => {
		const id = await findChart( requestUtils, 'Browser share' );
		const chart = `[easy_chart chart_id="${ id }"]`;
		page_ = await requestUtils.createPage( {
			title: 'E2E chart reliability',
			status: 'publish',
			content: [
				html( `<div id="ec-visible">${ chart }</div>` ),
				html(
					`<div id="ec-hidden" style="display:none">${ chart }</div>`
				),
				html(
					`<details id="ec-details"><summary>More</summary>${ chart }</details>`
				),
				html( `<template id="ec-template">${ chart }</template>` ),
				html( '<div id="ec-target"></div>' ),
				html(
					`<div id="ec-broken-json">${ brokenChart( '{oops' ) }</div>`
				),
				html(
					`<div id="ec-broken-data">${ brokenChart(
						'{"type":"bar","data":{"columns":["","A"],"rows":"boom"},"settings":{},"title":"Broken"}'
					) }</div>`
				),
				html( '<div style="height:3000px"></div>' ),
				html( `<div id="ec-below">${ chart }</div>` ),
			].join( '' ),
		} );
	} );

	test.afterAll( async ( { requestUtils } ) => {
		await requestUtils.rest( {
			method: 'DELETE',
			path: `/wp/v2/pages/${ page_.id }`,
			params: { force: true },
		} );
	} );

	test( 'draws a hidden chart once it is shown, at the right width', async ( {
		page,
	} ) => {
		await page.goto( page_.link );
		await expect( page.locator( `#ec-visible ${ FIGURE }` ) ).toHaveClass(
			/is-rendered/
		);

		const hidden = page.locator( '#ec-hidden' );
		await expect( hidden.locator( FIGURE ) ).not.toHaveClass(
			/is-rendered/
		);
		await hidden.evaluate( ( element ) => {
			element.style.display = 'block';
		} );
		await expect( hidden.locator( FIGURE ) ).toHaveClass( /is-rendered/ );
		await expect
			.poll( async () => {
				const { svg, box } = await widths( hidden );
				return Math.abs( svg - box ) <= 1 && box > 0;
			} )
			.toBe( true );

		// Chrome lays out a closed <details>, so its chart may draw early: check it fits once open.
		const details = page.locator( '#ec-details' );
		await details.locator( 'summary' ).click();
		await expect( details.locator( FIGURE ) ).toHaveClass( /is-rendered/ );
		await expect
			.poll( async () => {
				const { svg, box } = await widths( details );
				return Math.abs( svg - box ) <= 1 && box > 0;
			} )
			.toBe( true );
	} );

	test( 'draws a chart far below the fold without scrolling', async ( {
		page,
	} ) => {
		await page.goto( page_.link );
		await expect( page.locator( `#ec-below ${ FIGURE }` ) ).toHaveClass(
			/is-rendered/
		);
		expect( await page.evaluate( () => window.scrollY ) ).toBe( 0 );
	} );

	test( 'draws charts added later, and copies of drawn charts as charts of their own', async ( {
		page,
	} ) => {
		await page.goto( page_.link );
		await page.evaluate( () => {
			const template = document.getElementById( 'ec-template' );
			document
				.getElementById( 'ec-target' )
				.append( template.content.cloneNode( true ) );
		} );
		const added = page.locator( `#ec-target ${ FIGURE }` );
		await expect( added ).toHaveClass( /is-rendered/ );

		// A carousel in loop mode copies slides, with the drawing and ECharts' id.
		await page.evaluate( () => {
			const figure = document.querySelector( '#ec-target figure' );
			figure.after( figure.cloneNode( true ) );
		} );
		await expect(
			page.locator( `#ec-target ${ FIGURE }.is-rendered` )
		).toHaveCount( 2 );
		await expect
			.poll( () =>
				page.evaluate( () => {
					const boxes = [
						...document.querySelectorAll(
							'#ec-target .easy-charts-chart__canvas'
						),
					];
					const instances = boxes.map( ( box ) =>
						window.easyChartsECharts.getInstanceByDom( box )
					);
					return (
						instances.every(
							( instance, i ) => instance?.getDom() === boxes[ i ]
						) && instances[ 0 ] !== instances[ 1 ]
					);
				} )
			)
			.toBe( true );

		// The public hook takes no input and is read-only.
		expect(
			await page.evaluate( () => {
				window.easyCharts.refresh( 'ignored' );
				return Object.isFrozen( window.easyCharts );
			} )
		).toBe( true );
	} );

	test( 'redraws charts at the new size when the phone layout switches back', async ( {
		page,
	} ) => {
		await page.setViewportSize( { width: 375, height: 800 } );
		await page.goto( '/easy-charts-examples/' );
		const figures = page.locator( `${ FIGURE }.is-rendered` );
		await expect( figures ).toHaveCount( 12 );

		// Rotating a phone, or widening the window past 480 px.
		await page.setViewportSize( { width: 1280, height: 800 } );
		await expect
			.poll( () =>
				figures.evaluateAll( ( elements ) =>
					elements.every( ( figure ) => {
						const box = figure.querySelector(
							'.easy-charts-chart__canvas'
						);
						const svg = box.querySelector( 'svg' );
						return (
							box.clientWidth > 480 &&
							Math.abs(
								svg.getBoundingClientRect().width -
									box.clientWidth
							) <= 1
						);
					} )
				)
			)
			.toBe( true );
	} );

	test( 'prints charts at the page width', async ( { page } ) => {
		await page.goto( page_.link );
		await expect( page.locator( `#ec-below ${ FIGURE }` ) ).toHaveClass(
			/is-rendered/
		);
		await page.evaluate( () =>
			window.dispatchEvent( new window.Event( 'beforeprint' ) )
		);
		await page.emulateMedia( { media: 'print' } );

		// Narrow the page and measure in the same task, as printing does: no script runs in between.
		const charts = await page.evaluate( () => {
			document.body.style.setProperty( 'width', '400px', 'important' );
			return [
				...document.querySelectorAll(
					'figure.easy-charts-chart.is-rendered'
				),
			].map( ( figure ) => {
				const svg = figure
					.querySelector( '.easy-charts-chart__canvas svg' )
					.getBoundingClientRect();
				return {
					fits: svg.right <= figure.getBoundingClientRect().right + 1,
					height: svg.height,
				};
			} );
		} );

		expect( charts.length ).toBeGreaterThan( 0 );
		for ( const chart of charts ) {
			expect( chart ).toMatchObject( { fits: true } );
			expect( chart.height ).toBeGreaterThan( 100 );
		}
	} );

	test( 'keeps the data table of a chart that fails, and draws the others', async ( {
		page,
	} ) => {
		const errors = [];
		page.on( 'console', ( message ) => {
			if ( message.type() === 'error' ) {
				errors.push( message.text() );
			}
		} );
		await page.goto( page_.link );
		await expect( page.locator( `#ec-below ${ FIGURE }` ) ).toHaveClass(
			/is-rendered/
		);

		for ( const id of [ '#ec-broken-json', '#ec-broken-data' ] ) {
			const figure = page.locator( `${ id } ${ FIGURE }` );
			await expect( figure ).toHaveClass( /is-failed/ );
			await expect( figure ).not.toHaveClass( /is-rendered/ );
			await expect( figure.locator( 'table' ) ).toBeVisible();
			await expect(
				figure.locator( '.easy-charts-chart__canvas' )
			).toBeHidden();
		}
		expect(
			errors.filter( ( text ) => text.startsWith( 'Easy Charts:' ) )
		).toHaveLength( 2 );
	} );

	test( 'draws charts when the ECharts runtime loads late', async ( {
		page,
	} ) => {
		// An optimiser that delays JavaScript: echarts.js runs well after frontend.js.
		await page.route( /\/build\/echarts\.js/, async ( route ) => {
			const url = route.request().url();
			if ( url.includes( 'late=1' ) ) {
				await route.continue();
				return;
			}
			const late = url + ( url.includes( '?' ) ? '&' : '?' ) + 'late=1';
			await route.fulfill( {
				contentType: 'text/javascript',
				body: `setTimeout( () => { const s = document.createElement( 'script' ); s.src = ${ JSON.stringify(
					late
				) }; document.head.append( s ); }, 1500 );`,
			} );
		} );

		await page.goto( page_.link );
		const visible = page.locator( `#ec-visible ${ FIGURE }` );
		await page.waitForLoadState( 'load' );
		await expect( visible ).not.toHaveClass( /is-rendered/ );
		await expect( visible ).toHaveClass( /is-rendered/, {
			timeout: 10000,
		} );
		await expect( page.locator( `#ec-below ${ FIGURE }` ) ).toHaveClass(
			/is-rendered/
		);
	} );

	test( 'hides drawn charts’ tables without the plugin’s stylesheet', async ( {
		page,
	} ) => {
		// "Remove unused CSS" optimisers drop rules for classes only scripts add.
		await page.route( /\/build\/frontend\.css/, ( route ) =>
			route.abort()
		);
		await page.goto( page_.link );
		await expect( page.locator( `#ec-below ${ FIGURE }` ) ).toHaveClass(
			/is-rendered/
		);

		const tables = await page
			.locator( `${ FIGURE }.is-rendered .easy-charts-chart__data` )
			.evaluateAll( ( elements ) =>
				elements.map( ( element ) => {
					const box = element.getBoundingClientRect();
					return { width: box.width, height: box.height };
				} )
			);
		expect( tables.length ).toBeGreaterThan( 0 );
		for ( const table of tables ) {
			expect( table.width ).toBeLessThanOrEqual( 1 );
			expect( table.height ).toBeLessThanOrEqual( 1 );
		}
	} );
} );
