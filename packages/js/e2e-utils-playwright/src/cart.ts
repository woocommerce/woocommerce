/**
 * External dependencies
 */
import type { Page } from '@playwright/test';

/**
 * Adds a specified quantity of a product by ID to the WooCommerce cart.
 *
 * @param page      - Playwright page object
 * @param productId - The product ID to add
 * @param quantity  - Number of items to add (default: 1)
 */
export const addAProductToCart = async (
	page: Page,
	productId: string,
	quantity = 1
): Promise< void > => {
	for ( let i = 0; i < quantity; i++ ) {
		const responsePromise = page.waitForResponse(
			'**/wp-json/wc/store/v1/cart**'
		);
		await page.goto( `shop/?add-to-cart=${ productId }` );
		await responsePromise;
		await page.getByRole( 'alert' ).waitFor( { state: 'visible' } );
	}
};

/**
 * Util helper made for adding multiple same products to cart.
 *
 * @param page          - Playwright page object
 * @param productName   - Name of the product to add
 * @param quantityCount - Number of items to add (default: 1)
 */
export async function addOneOrMoreProductToCart(
	page: Page,
	productName: string,
	quantityCount = 1
): Promise< void > {
	await page.goto(
		`product/${ productName.replace( / /gi, '-' ).toLowerCase() }`
	);
	await page
		.getByLabel( 'Product quantity' )
		.fill( quantityCount.toString() );
	await page.locator( 'button[name="add-to-cart"]' ).click();
}
