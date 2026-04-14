/**
 * External dependencies
 */
import { test, expect, CLASSIC_THEME_SLUG } from '@woocommerce/e2e-utils';

test.describe( 'Mini-Cart: classic theme', () => {
	test.use( {
		storageState: {
			origins: [],
			cookies: [],
		},
	} );

	test.beforeEach( async ( { requestUtils } ) => {
		await requestUtils.activateTheme( CLASSIC_THEME_SLUG );
	} );

	test( 'opens the drawer when a classic AJAX add-to-cart button is clicked', async ( {
		page,
		requestUtils,
	} ) => {
		// Create a page with a mini-cart block (open_drawer) and a classic
		// [products] shortcode that renders AJAX add-to-cart links.
		const testPage = await requestUtils.rest( {
			method: 'POST',
			path: '/wp/v2/pages',
			data: {
				status: 'publish',
				title: 'Classic add-to-cart test page',
				content:
					'<!-- wp:woocommerce/mini-cart {"addToCartBehaviour":"open_drawer"} /-->\n\n<!-- wp:shortcode -->\n[products limit="3"]\n<!-- /wp:shortcode -->',
			},
		} );

		// Wait for networkidle to ensure the IAPI mini-cart store has
		// hydrated and the jQuery event bridge is set up.
		await page.goto( `/?p=${ testPage.id }` );

		const miniCartButton = page.locator( '.wc-block-mini-cart__button' );
		await expect( miniCartButton ).toBeVisible();

		const addToCartLink = page.getByLabel( /Add to cart/ ).first();
		// Wait for the GET /cart request (refreshCartItems) to complete.
		const cartResponse = page.waitForResponse( '**/wc/store/v1/cart**' );
		await addToCartLink.click();
		await cartResponse;
		const dialog = page.getByRole( 'dialog' );
		await expect( dialog ).toBeVisible();
	} );
} );
