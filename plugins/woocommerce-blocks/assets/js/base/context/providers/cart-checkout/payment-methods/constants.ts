/**
 * Internal dependencies
 */
import type { PaymentMethodDataContextType } from '../../../../../data/payment-methods/types';

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
	SET_ACTIVE_PAYMENT_METHOD = 'set_active_payment_method',
}

export const DEFAULT_PAYMENT_METHOD_DATA: PaymentMethodDataContextType = {
	savedPaymentMethods: {},
	onPaymentProcessing: () => () => () => void null,
};
