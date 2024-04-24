const addAProductToCart = async ( page, productId ) => {
	const requestPromise = page.waitForRequest(
		'**/wp-json/wc/store/v1/cart?**'
	);
	await page.goto( `/shop/?add-to-cart=${ productId }` );
	await requestPromise;
};

module.exports = {
	addAProductToCart,
};
