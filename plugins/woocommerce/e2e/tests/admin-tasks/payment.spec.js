const { test, expect } = require( '@playwright/test' );

test.describe( 'Payment setup task', () => {
	test.use( { storageState: 'e2e/storage/adminState.json' } );

	test.beforeEach( async ( { page } ) => {
		await page.goto(
			'wp-admin/admin.php?page=wc-admin&path=/setup-wizard'
		);
		await page.click( 'text=Skip setup store details' );
		await page.click( 'text=No thanks' );
		await page.waitForLoadState( 'networkidle' );
	} );

	test( 'Can visit the payment setup task from the homescreen if the setup wizard has been skipped', async ( {
		page,
	} ) => {
		await page.goto( 'wp-admin/admin.php?page=wc-admin' );
		await page.click( 'text=Set up payments' );
		await expect( page.locator( 'h1' ) ).toHaveText( 'Set up payments' );
	} );

	test( 'Saving valid bank account transfer details enables the payment method', async ( {
		page,
	} ) => {
		await page.goto(
			'wp-admin/admin.php?page=wc-admin&task=payments&id=bacs'
		);
		page.locator( '.components-button.is-small.has-icon' )
			.click()
			.catch( () => {} );
		// purposely no await -- close the help dialog if/when it appears
		await page.fill( '//input[@placeholder="Account name"]', 'Savings' );
		await page.fill( '//input[@placeholder="Account number"]', '1234' );
		await page.fill( '//input[@placeholder="Bank name"]', 'Test Bank' );
		await page.fill( '//input[@placeholder="Sort code"]', '12' );
		await page.fill( '//input[@placeholder="IBAN"]', '12 3456 7890' );
		await page.fill( '//input[@placeholder="BIC / Swift"]', 'ABBA' );
		await page.click( 'text=Save' );
		await expect(
			page.locator( 'div.components-snackbar__content' )
		).toHaveText( 'Direct bank transfer details added successfully' );
		await expect( page.locator( 'h1' ) ).toHaveText( 'Set up payments' );
		await expect(
			page.locator(
				'a:right-of(h3:has-text("Direct bank transfer")) >> nth=0'
			)
		).toHaveText( 'Manage' );

		// clean up
		await page.goto(
			'wp-admin/admin.php?page=wc-settings&tab=checkout&section=bacs'
		);
		await page.click( 'text="Enable bank transfer"' );
		await page.click( 'text="Save changes"' );
	} );

	test( 'Enabling cash on delivery enables the payment method', async ( {
		page,
	} ) => {
		await page.goto( 'wp-admin/admin.php?page=wc-admin&task=payments' );
		page.locator( '.components-button.is-small.has-icon' )
			.click()
			.catch( () => {} );
		// purposely no await -- close the help dialog if/when it appears
		await page.click( 'button:has-text("Enable")' ); // enable COD payment option
		await page.goto( 'wp-admin/admin.php?page=wc-admin&task=payments' );
		await expect( page.locator( 'h1' ) ).toHaveText( 'Set up payments' );
		await expect(
			page.locator(
				'a:right-of(h3:has-text("Cash on delivery")) >> nth=0'
			)
		).toHaveText( 'Manage' );

		// clean up
		await page.goto(
			'wp-admin/admin.php?page=wc-settings&tab=checkout&section=cod'
		);
		await page.click( 'text="Enable cash on delivery"' );
		await page.click( 'text="Save changes"' );
	} );
} );
