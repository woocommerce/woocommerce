/**
 * External dependencies
 */
import type { Page } from '@playwright/test';
import { expect } from '@playwright/test';

/**
 * Util helper made for adding multiple same products to cart
 *
 * @param page
 * @param productName
 * @param quantityCount
 */
export async function addProductsToCart(
	page: Page,
	productName: string,
	quantityCount: string
) {
	await page.goto(
		`product/${ productName.replace( / /gi, '-' ).toLowerCase() }`
	);
	await expect(
		await page.getByRole( 'heading', { name: productName } ).count()
	).toBeGreaterThan( 0 );
	await page.getByLabel( 'Product quantity' ).fill( quantityCount );
	await page.locator( 'button[name="add-to-cart"]' ).click();
	await expect(
		page.getByText(
			`${ quantityCount } × “${ productName }” have been added to your cart.`
		)
	).toBeVisible();
}
