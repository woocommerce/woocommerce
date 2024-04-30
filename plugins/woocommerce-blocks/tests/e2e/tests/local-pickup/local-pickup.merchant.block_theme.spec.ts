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
		await localPickupUtils.disableLocalPickupCosts();
		await localPickupUtils.enableLocalPickup();
	} );

	test( 'user can toggle the enabled state', async ( {
		page,
		localPickupUtils,
	} ) => {
		await expect( page.getByLabel( 'Enable local pickup' ) ).toBeChecked();

		await localPickupUtils.disableLocalPickup();

		await expect(
			page.getByLabel( 'Enable local pickup' )
		).not.toBeChecked();
	} );

	test( 'user can change the title', async ( { page, localPickupUtils } ) => {
		await page.getByPlaceholder( 'Pickup' ).fill( 'Local Pickup Test #1' );

		await localPickupUtils.saveLocalPickupSettings();

		await expect( page.getByPlaceholder( 'Pickup' ) ).toHaveValue(
			'Local Pickup Test #1'
		);

		await page.getByPlaceholder( 'Pickup' ).fill( 'Local Pickup Test #2' );

		await localPickupUtils.saveLocalPickupSettings();

		await expect( page.getByPlaceholder( 'Pickup' ) ).toHaveValue(
			'Local Pickup Test #2'
		);
	} );

	test( 'user can toggle the price field state', async ( {
		page,
		localPickupUtils,
	} ) => {
		await localPickupUtils.enableLocalPickupCosts();

		await expect(
			page.getByLabel(
				'Add a price for customers who choose local pickup'
			)
		).toBeChecked();

		await localPickupUtils.disableLocalPickupCosts();

		await expect(
			page.getByLabel(
				'Add a price for customers who choose local pickup'
			)
		).not.toBeChecked();
	} );

	test( 'user can edit costs and tax status', async ( {
		page,
		localPickupUtils,
	} ) => {
		await localPickupUtils.enableLocalPickupCosts();

		await expect(
			page.getByLabel(
				'Add a price for customers who choose local pickup'
			)
		).toBeChecked();

		await page.getByPlaceholder( 'Free' ).fill( '20' );
		await page.getByLabel( 'Taxes' ).selectOption( 'none' );

		await localPickupUtils.saveLocalPickupSettings();

		await expect( page.getByPlaceholder( 'Free' ) ).toHaveValue( '20' );
		await expect( page.getByLabel( 'Taxes' ) ).toHaveValue( 'none' );

		await page.getByPlaceholder( 'Free' ).fill( '' );
		await page.getByLabel( 'Taxes' ).selectOption( 'taxable' );

		await localPickupUtils.saveLocalPickupSettings();

		await expect( page.getByPlaceholder( 'Free' ) ).toHaveValue( '' );
		await expect( page.getByLabel( 'Taxes' ) ).toHaveValue( 'taxable' );
	} );

	test( 'user can add a new location', async ( {
		page,
		localPickupUtils,
	} ) => {
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

		await expect(
			page.getByRole( 'cell', {
				name: 'Automattic, Inc.60 29th Street, Suite 343, San Francisco, California, 94110, United States (US)',
			} )
		).toBeVisible();
	} );

	test( 'user can edit a location', async ( { page, localPickupUtils } ) => {
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

		await expect(
			page.getByRole( 'cell', {
				name: 'Automattic, Inc.60 29th Street, Suite 343, San Francisco, California, 94110, United States (US)',
			} )
		).toBeVisible();

		await localPickupUtils.editPickupLocation( {
			location: {
				name: 'Ministry of Automattic Limited',
				address: '100 New Bridge Street',
				city: 'London',
				postcode: 'EC4V 6JA',
				state: 'GB',
				details: 'British entity',
			},
		} );

		await expect(
			page.getByRole( 'cell', {
				name: 'Ministry of Automattic Limited100 New Bridge Street, London, EC4V 6JA, United Kingdom (UK)',
			} )
		).toBeVisible();
	} );

	test( 'user can delete a location', async ( {
		page,
		localPickupUtils,
	} ) => {
		await localPickupUtils.addPickupLocation( {
			location: {
				name: 'Ausomattic Pty Ltd',
				address:
					'c/o Baker And Mckenzie Level 19 Cbw, 181 William Street',
				city: 'Melbourne',
				postcode: '300',
				state: 'AU:VIC',
				details: 'Australian entity',
			},
		} );

		await expect(
			page.getByRole( 'cell', {
				name: 'Ausomattic Pty Ltdc/o Baker And Mckenzie Level 19 Cbw, 181 William Street, Melbourne, Victoria, 300, Australia',
			} )
		).toBeVisible();

		await localPickupUtils.deletePickupLocation();

		await expect(
			page.getByRole( 'cell', {
				name: 'When you add a pickup location, it will appear here.',
			} )
		).toBeVisible();
	} );

	test( 'updating the title in WC Settings updates the local pickup text in the block and vice/versa', async ( {
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
		await editorUtils.enterEditMode();

		const block = await editorUtils.getBlockByName(
			'woocommerce/checkout-shipping-method-block'
		);
		await editor.selectBlocks( block );
		const fakeInput = editor.canvas.getByLabel( 'Pickup', { exact: true } );
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

		// Now check if it's visible in the local pickup settings.
		await localPickupUtils.openLocalPickupSettings();
		await expect( page.getByLabel( 'Title' ) ).toHaveValue(
			'This is a test'
		);

		// Now update the title via local pickup settings and check it reflects in the site editor and front end.
		await localPickupUtils.setLocalPickupTitle(
			'Edited from settings page'
		);
		await localPickupUtils.saveLocalPickupSettings();

		await admin.visitSiteEditor( {
			postId: 'woocommerce/woocommerce//page-checkout',
			postType: 'wp_template',
		} );
		await editorUtils.enterEditMode();

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
