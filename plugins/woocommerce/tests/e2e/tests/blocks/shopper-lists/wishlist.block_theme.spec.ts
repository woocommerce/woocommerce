/**
 * External dependencies
 */
import {
	test as base,
	expect,
	customerFile,
	wpCLI,
} from '@woocommerce/e2e-utils';

/**
 * Internal dependencies
 */
import AddToCartWithOptionsPage from '../add-to-cart-with-options/add-to-cart-with-options.page';
import {
	enableWishlistFeature,
	shopperListErrorNotice,
	shopperListRow,
} from './utils';

/**
 * E2E flows for the Wishlist and Add to Wishlist Button blocks, gated behind
 * the `product_wishlist` feature flag. `AddToCartWithOptions::render()`
 * injects the Add to Wishlist Button as the last child of the Add to Cart +
 * Options block's template part at render time whenever the feature is on
 * (never persisted to the shipped template parts), so the only site editor
 * setup a fixture needs is swapping the single-product template's legacy Add
 * to Cart Form for the Add to Cart + Options block — the same swap
 * `cart-store/cart-line-identity.block_theme.spec.ts` performs for its own
 * variation-handling flow. Both blocks are hidden for guests, so the
 * shopper-facing steps run in a dedicated customer browser context while the
 * site editor setup runs as the admin the `page` fixture defaults to.
 */

const test = base.extend< {
	addToCartWithOptionsPage: AddToCartWithOptionsPage;
} >( {
	addToCartWithOptionsPage: async ( { page, admin, editor }, use ) => {
		await use( new AddToCartWithOptionsPage( { page, admin, editor } ) );
	},
} );

test.describe( 'Wishlist', () => {
	test.beforeEach( async () => {
		await enableWishlistFeature();
	} );

	test( 'A wishlist row is removed only when its add succeeds', async ( {
		editor,
		browser,
		addToCartWithOptionsPage,
	} ) => {
		await addToCartWithOptionsPage.updateSingleProductTemplate();
		await editor.saveSiteEditorEntities( {
			isOnlyCurrentEntityDirty: true,
		} );

		// Shopper: save two products to the wishlist.
		const customerContext = await browser.newContext( {
			storageState: customerFile,
		} );
		const customerPage = await customerContext.newPage();

		try {
			await customerPage.goto( '/product/beanie/' );
			await customerPage
				.getByRole( 'button', { name: 'Add to wishlist' } )
				.click();
			await expect(
				customerPage.getByRole( 'button', {
					name: 'Saved to wishlist',
				} )
			).toBeVisible();

			await customerPage.goto( '/product/cap/' );
			await customerPage
				.getByRole( 'button', { name: 'Add to wishlist' } )
				.click();
			await expect(
				customerPage.getByRole( 'button', {
					name: 'Saved to wishlist',
				} )
			).toBeVisible();

			await customerPage.goto( '/my-account/wishlist/' );

			const beanieRow = shopperListRow( customerPage, 'Beanie' );
			const capRow = shopperListRow( customerPage, 'Cap' );
			await expect( beanieRow ).toHaveCount( 1 );
			await expect( capRow ).toHaveCount( 1 );

			// Cap goes out of stock after being saved (and after this page
			// already fetched it as purchasable) — the row's own "Add to
			// cart" button is still visible client-side, and the server
			// rejects the add when it's actually clicked.
			const capIdResult = await wpCLI(
				'post list --post_type=product --name="Cap" --field=ID'
			);
			const capId = capIdResult.stdout.trim().split( '\n' ).at( -1 );
			await wpCLI(
				`wc product update ${ capId } --in_stock=false --user=1`
			);

			// Beanie's add succeeds: its row disappears from the wishlist.
			await beanieRow
				.getByRole( 'button', { name: 'Add to cart' } )
				.click();
			await expect( beanieRow ).toHaveCount( 0 );

			// Cap's add is rejected by the server: the row stays, the
			// server's error message is shown once as a dismissible notice.
			await capRow.getByRole( 'button', { name: 'Add to cart' } ).click();
			const notice = shopperListErrorNotice( customerPage );
			await expect( notice ).toBeVisible();
			await expect( notice ).toContainText( /out of stock/i );
			await expect( notice ).toHaveCount( 1 );
			await expect( capRow ).toHaveCount( 1 );

			// The cart gained Beanie, not Cap.
			await customerPage.goto( '/cart/' );
			await expect(
				customerPage.getByLabel( 'Quantity of Beanie in your cart.' )
			).toHaveValue( '1' );
			await expect(
				customerPage.locator( '.wc-block-cart-items__row', {
					hasText: 'Cap',
				} )
			).toHaveCount( 0 );
		} finally {
			await customerContext.close();
		}
	} );

	test( 'The star follows the resolved variation', async ( {
		editor,
		browser,
		addToCartWithOptionsPage,
	} ) => {
		await addToCartWithOptionsPage.updateSingleProductTemplate();
		await editor.saveSiteEditorEntities( {
			isOnlyCurrentEntityDirty: true,
		} );

		const customerContext = await browser.newContext( {
			storageState: customerFile,
		} );
		const customerPage = await customerContext.newPage();

		try {
			await customerPage.goto( '/product/v-neck-t-shirt/' );

			const colorBlueOption = customerPage
				.getByRole( 'radiogroup', { name: 'Color' } )
				.getByRole( 'radio', { name: 'Blue', exact: true } );
			const sizeLargeOption = customerPage
				.getByRole( 'radiogroup', { name: 'Size' } )
				.getByRole( 'radio', { name: 'Large', exact: true } );
			const wishlistToggle = customerPage.getByRole( 'button', {
				name: /Add to wishlist|Saved to wishlist|Select options first/,
			} );

			// No variation resolved yet: the button can't target a product.
			await expect( wishlistToggle ).toHaveText( 'Select options first' );
			await expect( wishlistToggle ).toBeDisabled();

			// A full attribute selection resolves a variation: the star
			// saves it and shows pressed.
			await colorBlueOption.click();
			await sizeLargeOption.click();
			await expect( wishlistToggle ).toHaveText( 'Add to wishlist' );
			await expect( wishlistToggle ).toBeEnabled();

			await wishlistToggle.click();
			await expect( wishlistToggle ).toHaveText( 'Saved to wishlist' );
			await expect( wishlistToggle ).toHaveAttribute(
				'aria-pressed',
				'true'
			);

			// Clearing the selection (clicking the selected option again
			// deselects it) leaves no variation resolved again.
			await colorBlueOption.click();
			await expect( wishlistToggle ).toHaveText( 'Select options first' );
			await expect( wishlistToggle ).toBeDisabled();
		} finally {
			await customerContext.close();
		}
	} );
} );
