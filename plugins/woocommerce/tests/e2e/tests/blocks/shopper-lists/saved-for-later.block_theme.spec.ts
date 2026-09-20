/**
 * External dependencies
 */
import { test, expect, customerFile } from '@woocommerce/e2e-utils';

/**
 * Internal dependencies
 */
import { enableSaveForLaterFeature, shopperListRow } from './utils';

/**
 * E2E flow for the Saved for Later block, gated behind the
 * `cart_save_for_later` feature flag. The block auto-inserts after the Cart
 * block via the Block Hooks API declared in its `block.json`
 * (`blockHooks: { "woocommerce/cart": "after" }`), so no site editor setup
 * is needed beyond turning the feature on. Both the "Save for later" link
 * and the block itself are hidden for guests, so the whole flow runs as the
 * shared logged-in customer.
 */

test.describe( 'Saved for Later', () => {
	test.use( { storageState: customerFile } );

	test.beforeEach( async () => {
		await enableSaveForLaterFeature();
	} );

	test( '"Save for later" in the Cart block populates the Saved for Later block', async ( {
		page,
		frontendUtils,
	} ) => {
		await frontendUtils.goToShop();
		await frontendUtils.addToCart( 'Beanie' );
		await frontendUtils.goToCart();

		const quantityInput = page.getByLabel(
			'Quantity of Beanie in your cart.'
		);
		const proceedToCheckout = page.getByRole( 'link', {
			name: 'Proceed to Checkout',
		} );
		await expect( quantityInput ).toHaveValue( '1' );

		// Raise the line to quantity 3 before saving it for later. The
		// checkout link disabling then re-enabling is this cart's own signal
		// that the debounced quantity request has reached the server —
		// `saveForLater` reads the server's current cart, so the click below
		// must wait for it.
		await quantityInput.fill( '3' );
		await expect( proceedToCheckout ).toBeDisabled();
		await expect( proceedToCheckout ).toBeEnabled();
		await expect( quantityInput ).toHaveValue( '3' );

		await page.getByRole( 'button', { name: 'Save for later' } ).click();

		// The cart line is gone…
		await expect(
			page.locator( '.wc-block-cart-items__row', { hasText: 'Beanie' } )
		).toHaveCount( 0 );

		// …and the Saved for Later block shows it, at the saved quantity.
		const savedRow = shopperListRow( page, 'Beanie' );
		await expect( savedRow ).toHaveCount( 1 );
		await expect( savedRow.getByText( 'Quantity: 3' ) ).toBeVisible();

		// Moving it back adds it to the cart at the saved quantity and
		// removes the saved row.
		await savedRow.getByRole( 'button', { name: 'Move to cart' } ).click();
		await expect( savedRow ).toHaveCount( 0 );
		await expect( quantityInput ).toHaveValue( '3' );
	} );
} );
