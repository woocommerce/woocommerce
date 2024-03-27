/**
 * External dependencies
 */
import { test, expect } from '@woocommerce/e2e-playwright-utils';

/**
 * Internal dependencies
 */
import { REGULAR_PRICED_PRODUCT_NAME } from '../checkout/constants';

test.describe( 'Merchant → Local Pickup Settings', () => {
	test.beforeEach( async ( { localPickupUtils } ) => {
		await localPickupUtils.deleteLocations();
		await localPickupUtils.addPickupLocation( {
			location: {
				name: 'Automattic, Inc.',
				address: '60 29th Street, Suite 343',
				city: 'San Francisco',
				postcode: '94110',
				state: 'US:CA',
				details: 'American entity',
			},
		} );
		await localPickupUtils.disableLocalPickupCosts();
		await localPickupUtils.enableLocalPickup();
	} );

	test.afterEach( async ( { localPickupUtils } ) => {
		await localPickupUtils.deleteLocations();
		await localPickupUtils.disableLocalPickupCosts();
		await localPickupUtils.setTitle( 'Local Pickup' );
		await localPickupUtils.enableLocalPickup();
	} );

	test( 'Updating the title in WC Settings updates the local pickup text in the block and vice/versa', async ( {
		page,
		localPickupUtils,
		admin,
		editor,
		editorUtils,
		frontendUtils,
	} ) => {
		// First update the title via the site editor then check the local pickup settings.
		await admin.visitSiteEditor( {
			postId: 'woocommerce/woocommerce//page-checkout',
			postType: 'wp_template',
		} );
		await editor.canvas.click( 'body' );

		const block = await editorUtils.getBlockByName(
			'woocommerce/checkout-shipping-method-block'
		);
		await editor.selectBlocks( block );
		const fakeInput = editor.canvas.getByLabel( 'Local Pickup' );
		await fakeInput.click();

		const isMacOS = process.platform === 'darwin'; // darwin is macOS

		// eslint-disable-next-line playwright/no-conditional-in-test
		if ( isMacOS ) {
			await fakeInput.press( 'Meta+a' );
		} else {
			await fakeInput.press( 'Control+a' );
		}
		await fakeInput.press( 'Backspace' );
		await fakeInput.pressSequentially( 'This is a test' );
		await editor.canvas.getByText( 'This is a test' ).isVisible();
		await editor.saveSiteEditorEntities();
		await localPickupUtils.openLocalPickupSettings();
		await expect( page.getByLabel( 'Title' ) ).toHaveValue(
			'This is a test'
		);

		// Now update the title via local pickup settings and check it reflects in the site editor and front end.
		await localPickupUtils.setTitle( 'Edited from settings page' );
		await localPickupUtils.saveLocalPickupSettings();

		await admin.visitSiteEditor( {
			postId: 'woocommerce/woocommerce//page-checkout',
			postType: 'wp_template',
		} );
		await editor.canvas.click( 'body' );
		await expect(
			editor.canvas.getByText( 'Edited from settings page' )
		).toBeVisible();

		await frontendUtils.emptyCart();
		await frontendUtils.goToShop();
		await frontendUtils.addToCart( REGULAR_PRICED_PRODUCT_NAME );
		await frontendUtils.goToCheckout();

		await expect(
			page.getByText( 'Edited from settings page' )
		).toBeVisible();
	} );
} );
