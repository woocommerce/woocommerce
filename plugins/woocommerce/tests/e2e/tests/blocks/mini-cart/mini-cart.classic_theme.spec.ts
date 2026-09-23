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

		await page.goto( `/?p=${ testPage.id }` );

		const miniCartButton = page.locator( '.wc-block-mini-cart__button' );
		const miniCartBadge = page.locator( '.wc-block-mini-cart__badge' );
		await expect( miniCartButton ).toBeVisible();

		const addToCartLink = page.getByLabel( /Add to cart/ ).first();
		const ajaxCall = page.waitForResponse( '**wc-ajax=add_to_cart**' );
		await addToCartLink.click();
		// Wait for the AJAX call to complete.
		await ajaxCall;
		const dialog = page.getByRole( 'dialog' );
		await expect( dialog ).toBeVisible();
		await expect( miniCartButton ).toBeVisible();
		await expect( miniCartBadge ).toBeVisible();
		await expect( miniCartBadge ).toHaveText( '1' );
	} );
} );
