const { expect } = require( '@playwright/test' );

/**
 * Util helper made for adding multiple same products to cart
 *
 * @param page
 * @param productName
 * @param quantityCount
 */
export async function addProductsToCart( page, productName, quantityCount ) {
	await page.goto(
		`product/${ productName.replace( / /gi, '-' ).toLowerCase() }`
	);
	await expect( page.locator( '.product_title' ) ).toContainText(
		productName
	);
	await page.getByLabel( 'Product quantity' ).fill( quantityCount );
	await page.getByRole( 'button', { name: 'Add to cart' } ).click();
	await expect(
		page
			.getByRole( 'alert' )
			.getByText(
				`${ quantityCount } × “${ productName }” have been added to your cart.`
			)
	).toBeVisible();
}
