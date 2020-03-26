/**
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

export const ACTION_TYPES = {
	...STATUS,
	SET_REGISTERED_PAYMENT_METHOD: 'set_registered_payment_method',
	SET_REGISTERED_EXPRESS_PAYMENT_METHOD:
		'set_registered_express_payment_method',
};

/**
 * @todo do typedefs for the payment event state.
 */

export const DEFAULT_PAYMENT_DATA = {
	currentStatus: STATUS.PRISTINE,
	paymentMethodData: {
		payment_method: '',
		// arbitrary data the payment method
		// wants to pass along for payment
		// processing server side.
	},
	errorMessage: '',
	paymentMethods: {},
	expressPaymentMethods: {},
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
	paymentMethodData: {},
	errorMessage: '',
	activePaymentMethod: '',
	setActivePaymentMethod: () => void null,
	customerPaymentMethods: {},
	paymentMethods: {},
	expressPaymentMethods: {},
	paymentMethodsInitialized: false,
	expressPaymentMethodsInitialized: false,
};
