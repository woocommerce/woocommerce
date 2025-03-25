/**
 * Adds a specified quantity of a product by ID to the WooCommerce cart.
 *
 * @param {import('playwright').Page} page
 * @param {string}                    productId
 * @param {number}                    [quantity=1]
 */
export const addAProductToCart = async ( page, productId, quantity = 1 ) => {
	for ( let i = 0; i < quantity; i++ ) {
		const responsePromise = page.waitForResponse(
			'**/wp-json/wc/store/v1/cart?**'
		);
		await page.goto( `shop/?add-to-cart=${ productId }` );
		await responsePromise;
		await page.getByRole( 'alert' ).waitFor( { state: 'visible' } );
	}
};

/**
 * Util helper made for adding multiple same products to cart
 *
 * @param {import('playwright').Page} page
 * @param {string}                    productName
 * @param {number}                    quantityCount
 */
export async function addOneOrMoreProductToCart(
	page,
	productName,
	quantityCount = 1
) {
	await page.goto(
		`product/${ productName.replace( / /gi, '-' ).toLowerCase() }`
	);
	await page
		.getByLabel( 'Product quantity' )
		.fill( quantityCount.toString() );
	await page.locator( 'button[name="add-to-cart"]' ).click();
}
