/**
 * @typedef {import('@woocommerce/type-defs/cart').CartBillingAddress} CartBillingAddress
 * @typedef {import('@woocommerce/type-defs/contexts').PaymentMethodDataContext} PaymentMethodDataContext
 */

export const STATUS = {
	PRISTINE: 'pristine',
	STARTED: 'started',
	PROCESSING: 'processing',
	ERROR: 'has_error',
	FAILED: 'failed',
	SUCCESS: 'success',
	COMPLETE: 'complete',
};

/**
 * @type {CartBillingAddress}
 */
const DEFAULT_BILLING_DATA = {
	first_name: '',
	last_name: '',
	company: '',
	email: '',
	phone: '',
	country: '',
	address_1: '',
	address_2: '',
	city: '',
	state: '',
	postcode: '',
};

/**
 * @todo do typedefs for the payment event state.
 */

export const DEFAULT_PAYMENT_DATA = {
	currentStatus: STATUS.PRISTINE,
	billingData: DEFAULT_BILLING_DATA,
	paymentMethodData: {
		payment_method: '',
		// arbitrary data the payment method
		// wants to pass along for payment
		// processing server side.
	},
	errorMessage: '',
};

/**
 * @type {PaymentMethodDataContext}
 */
export const DEFAULT_PAYMENT_METHOD_DATA = {
	setPaymentStatus: () => void null,
	currentStatus: {
		isPristine: true,
		isStarted: false,
		isProcessing: false,
		isFinished: false,
		hasError: false,
		hasFailed: false,
		isSuccessful: false,
	},
	paymentStatuses: STATUS,
	setBillingData: () => void null,
	billingData: DEFAULT_BILLING_DATA,
	paymentMethodData: {},
	errorMessage: '',
	activePaymentMethod: '',
	setActivePaymentMethod: () => void null,
};
