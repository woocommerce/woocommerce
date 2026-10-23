export interface AddToCartEventProduct {
	// Product or variation ID.
	id: number;
	// Quantity added to the cart.
	quantity: number;
}

export interface AddToCartEventDetail {
	// Whether cart data in the data store should be preserved.
	preserveCartData?: boolean;
	// Products added to the cart, with the quantity added for each one.
	products?: AddToCartEventProduct[];
}
