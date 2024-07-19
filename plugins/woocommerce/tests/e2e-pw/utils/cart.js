const addAProductToCart = async ( page, productId, quantity = 1 ) => {
	for ( let i = 0; i < quantity; i++ ) {
		const responsePromise = page.waitForResponse(
			'**/wp-json/wc/store/v1/cart?**'
		);
		await page.goto( `/shop/?add-to-cart=${ productId }` );
		await responsePromise;
		await page.getByRole( 'alert' ).waitFor( { state: 'visible' } );
	}
};

module.exports = {
	addAProductToCart,
};
