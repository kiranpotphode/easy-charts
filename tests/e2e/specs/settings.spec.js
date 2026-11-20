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
		// Back to unmigrated 1.x examples for the next run's other tests.
		wp( 'easy-charts seed-examples --v1' );
	} );

	test( 'migrates all 1.x charts', async ( {
		admin,
		page,
		requestUtils,
	} ) => {
		await admin.visitAdminPage(
			'edit.php',
			'post_type=easy_charts&page=easy-charts-settings'
		);
		const card = page.locator( '.easy-charts-settings__migration' );
		await expect( card ).toContainText( 'keep their 1.x look' );

		await card
			.getByRole( 'button', { name: 'Migrate all to 2.0…' } )
			.click();
		const dialog = page.getByRole( 'dialog', {
			name: 'Migrate all 1.x charts?',
		} );
		expect( await axe( page, '.components-modal__frame' ) ).toEqual( [] );
		await dialog.getByRole( 'button', { name: 'Migrate all' } ).click();

		await expect(
			card.getByText( /^Migrated \d+ charts?\.$/ )
		).toBeVisible();
		const status = await requestUtils.rest( {
			path: '/easy-charts/v1/migration',
		} );
		expect( status.pending ).toBe( 0 );
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
