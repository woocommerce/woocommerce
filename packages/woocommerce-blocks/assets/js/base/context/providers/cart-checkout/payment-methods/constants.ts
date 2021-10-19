/**
 * Internal dependencies
 */
import type {
	PaymentMethodDataContextType,
	PaymentMethodDataContextState,
} from './types';

export enum STATUS {
	PRISTINE = 'pristine',
	STARTED = 'started',
	PROCESSING = 'processing',
	ERROR = 'has_error',
	FAILED = 'failed',
	SUCCESS = 'success',
	COMPLETE = 'complete',
}

export enum ACTION {
	SET_REGISTERED_PAYMENT_METHODS = 'set_registered_payment_methods',
	SET_REGISTERED_EXPRESS_PAYMENT_METHODS = 'set_registered_express_payment_methods',
	SET_SHOULD_SAVE_PAYMENT_METHOD = 'set_should_save_payment_method',
}

// Note - if fields are added/shape is changed, you may want to update PRISTINE reducer clause to preserve your new field.
export const DEFAULT_PAYMENT_DATA_CONTEXT_STATE: PaymentMethodDataContextState = {
	currentStatus: STATUS.PRISTINE,
	shouldSavePaymentMethod: false,
	paymentMethodData: {
		payment_method: '',
	},
	hasSavedToken: false,
	errorMessage: '',
	paymentMethods: {},
	expressPaymentMethods: {},
};

export const DEFAULT_PAYMENT_METHOD_DATA: PaymentMethodDataContextType = {
	setPaymentStatus: () => ( {
		pristine: () => void null,
		started: () => void null,
		processing: () => void null,
		completed: () => void null,
		error: ( errorMessage: string ) => void errorMessage,
		failed: ( errorMessage, paymentMethodData ) =>
			void [ errorMessage, paymentMethodData ],
		success: ( paymentMethodData, billingData ) =>
			void [ paymentMethodData, billingData ],
	} ),
	currentStatus: {
		isPristine: true,
		isStarted: false,
		isProcessing: false,
		isFinished: false,
		hasError: false,
		hasFailed: false,
		isSuccessful: false,
		isDoingExpressPayment: false,
	},
	paymentStatuses: STATUS,
	paymentMethodData: {},
	errorMessage: '',
	activePaymentMethod: '',
	setActivePaymentMethod: () => void null,
	activeSavedToken: '',
	setActiveSavedToken: () => void null,
	customerPaymentMethods: {},
	paymentMethods: {},
	expressPaymentMethods: {},
	paymentMethodsInitialized: false,
	expressPaymentMethodsInitialized: false,
	onPaymentProcessing: () => () => () => void null,
	setExpressPaymentError: () => void null,
	isExpressPaymentMethodActive: false,
	setShouldSavePayment: () => void null,
	shouldSavePayment: false,
};
