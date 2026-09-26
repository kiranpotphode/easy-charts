/**
 * WordPress dependencies
 */
const { test, expect } = require( '@wordpress/e2e-test-utils-playwright' );

/**
 * Internal dependencies
 */
const { axe, wp } = require( '../utils' );

test.describe( 'Settings page', () => {
	test.afterAll( () => {
		wp( 'option delete easy_charts_settings || true' );
	} );

	test( 'saves global settings that charts use', async ( {
		admin,
		page,
	} ) => {
		await admin.visitAdminPage(
			'edit.php',
			'post_type=easy_charts&page=easy-charts-settings'
		);
		const app = page.locator( '#easy-charts-settings-root' );
		await expect( app.locator( '.easy-charts-preview svg' ) ).toBeVisible();
		expect( await axe( page, '#wpbody-content .wrap' ) ).toEqual( [] );

		const save = app.getByRole( 'button', { name: 'Save settings' } );
		await expect( save ).toBeDisabled();
		await app
			.getByRole( 'combobox', { name: 'Legend position' } )
			.selectOption( 'left' );
		await save.click();
		await expect( app.getByText( 'Settings saved.' ) ).toBeVisible();

		await page.reload();
		await expect(
			app.getByRole( 'combobox', { name: 'Legend position' } )
		).toHaveValue( 'left' );

		// Charts without their own legend position follow the global setting.
		await page.goto( '/easy-charts-examples/' );
		const config = JSON.parse(
			await page
				.locator( '.easy-charts-chart__config' )
				.first()
				.textContent()
		);
		expect( config.settings.legend.position ).toBe( 'left' );
	} );
} );
