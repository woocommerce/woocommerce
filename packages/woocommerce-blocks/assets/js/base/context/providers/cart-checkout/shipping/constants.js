/**
 * @typedef {import('@woocommerce/type-defs/contexts').ShippingErrorTypes} ShippingErrorTypes
 * @typedef {import('@woocommerce/type-defs/shipping').ShippingAddress} ShippingAddress
 * @typedef {import('@woocommerce/type-defs/contexts').ShippingDataContext} ShippingDataContext
 */

/**
 * @type {ShippingErrorTypes}
 */
export const ERROR_TYPES = {
	NONE: 'none',
	INVALID_ADDRESS: 'invalid_address',
	UNKNOWN: 'unknown_error',
};

export const shippingErrorCodes = {
	INVALID_COUNTRY: 'woocommerce_rest_cart_shipping_rates_invalid_country',
	MISSING_COUNTRY: 'woocommerce_rest_cart_shipping_rates_missing_country',
	INVALID_STATE: 'woocommerce_rest_cart_shipping_rates_invalid_state',
};

/**
 * @type {ShippingAddress}
 */
export const DEFAULT_SHIPPING_ADDRESS = {
	first_name: '',
	last_name: '',
	company: '',
	address_1: '',
	address_2: '',
	city: '',
	state: '',
	postcode: '',
	country: '',
};

/**
 * @type {ShippingDataContext}
 */
export const DEFAULT_SHIPPING_CONTEXT_DATA = {
	shippingErrorStatus: {
		isPristine: true,
		isValid: false,
		hasInvalidAddress: false,
		hasError: false,
	},
	dispatchErrorStatus: () => null,
	shippingErrorTypes: ERROR_TYPES,
	shippingRates: [],
	shippingRatesLoading: false,
	selectedRates: [],
	setSelectedRates: () => null,
	shippingAddress: DEFAULT_SHIPPING_ADDRESS,
	setShippingAddress: () => null,
	onShippingRateSuccess: () => null,
	onShippingRateFail: () => null,
	onShippingRateSelectSuccess: () => null,
	onShippingRateSelectFail: () => null,
	needsShipping: false,
};
