export const getProductColumnExpression = ( productTitle ) => (
	'td[@class="product-name" and ' +
	`a[contains(text(), "${ productTitle }")]` +
	']'
);

export const getQtyColumnExpression = ( args ) => (
	'td[@class="product-quantity" and ' +
	'.//' + getQtyInputExpression( args ) +
	']'
);

export const getQtyInputExpression = ( args = {} ) => {
	let qtyValue = '';

	if ( args.checkQty ) {
		qtyValue = ` and @value="${ args.qty }"`;
	}

	return 'input[contains(@class, "input-text")' + qtyValue + ']';
};

export const getCartItemExpression = ( productTitle, args ) => (
	'//tr[contains(@class, "cart_item") and ' +
	getProductColumnExpression( productTitle ) +
	' and ' +
	getQtyColumnExpression( args ) +
	']'
);

export const getRemoveExpression = () => (
	'td[@class="product-remove"]//a[@class="remove"]'
);
