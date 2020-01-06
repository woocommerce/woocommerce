// @todo this should be a value object. Provided via wc-settings?
const currencyObject = {
	code: 'USD',
	precision: 2,
	symbol: '$',
	symbolPosition: 'left',
	decimalSeparator: '.',
	priceFormat: '%1$s%2$s',
	thousandSeparator: ',',
};

const useCheckoutData = () => {
	// @todo this will likely be a global wp.data store state so that things
	// like shipping selection, quantity changes, etc that affect totals etc
	// will automatically update the payment data. For POC this is hardcoded
	const checkoutData = {
		// this likely should be a float.
		total: 10.123,
		currency: currencyObject,
		// @todo, should this be a standard format of items in the checkout/cart
		// provided to ALL payment methods? Line items includes taxes/shipping
		// costs? Coupons?
		lineItems: [],
	};
	const updateCheckoutData = () => {};
	return [ checkoutData, updateCheckoutData ];
};

export default useCheckoutData;
