/**
 * External dependencies
 */
import { Page, Locator } from '@playwright/test';
import { wpCLI } from '@woocommerce/e2e-utils';

/**
 * Option key backing the `product_wishlist` feature (`FeaturesController.php`).
 * The feature is off by default, so every spec turns it on for the fixture
 * rather than relying on a global default.
 */
const PRODUCT_WISHLIST_OPTION = 'woocommerce_product_wishlist_enabled';

/**
 * Option key backing the `cart_save_for_later` feature (`FeaturesController.php`).
 */
const CART_SAVE_FOR_LATER_OPTION = 'woocommerce_cart_save_for_later_enabled';

/**
 * Turn the `product_wishlist` feature on for the fixture.
 */
export async function enableWishlistFeature(): Promise< void > {
	await wpCLI( `option update ${ PRODUCT_WISHLIST_OPTION } yes` );
}

/**
 * Turn the `cart_save_for_later` feature on for the fixture.
 */
export async function enableSaveForLaterFeature(): Promise< void > {
	await wpCLI( `option update ${ CART_SAVE_FOR_LATER_OPTION } yes` );
}

/**
 * Locator for a shopper-list row (Wishlist or Saved for Later) matching a
 * product name.
 *
 * Every row renders as an `<li class="wc-block-shopper-list-item">`
 * (`ShopperListRenderer::ROW_CLASS`) regardless of which block renders it,
 * so scoping by that class and the product's visible name uniquely targets
 * one row's controls (remove button, price, and the block-specific action
 * button).
 *
 * @param page        The Playwright page, already on a page rendering the list.
 * @param productName The product's display name.
 */
export function shopperListRow( page: Page, productName: string ): Locator {
	return page.locator( 'li.wc-block-shopper-list-item', {
		hasText: productName,
	} );
}

/**
 * Locator for the dismissible error notice shown inside a shopper-list
 * block's own notices region (`ShopperListRenderer::render_interactivity_notices_region()`).
 *
 * @param page The Playwright page, already on a page rendering the list.
 */
export function shopperListErrorNotice( page: Page ): Locator {
	return page.locator(
		'.wc-block-components-notices .wc-block-components-notice-banner.is-error.is-dismissible'
	);
}
