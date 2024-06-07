const addAProductToCart = async ( page, productId ) => {
	const responsePromise = page.waitForResponse(
		'**/wp-json/wc/store/v1/cart?**'
	);
	await page.goto( `/shop/?add-to-cart=${ productId }` );
	await responsePromise;
};

module.exports = {
	addAProductToCart,
};
