/**
 * External dependencies
 */
import { getSetting } from '@woocommerce/settings';

/**
 * @type {import("@woocommerce/type-defs/checkout").CheckoutStatusConstants}
 */
export const STATUS = {
	PRISTINE: 'pristine',
	IDLE: 'idle',
	PROCESSING: 'processing',
	COMPLETE: 'complete',
	BEFORE_PROCESSING: 'before_processing',
	AFTER_PROCESSING: 'after_processing',
};

const checkoutData = getSetting( 'checkoutData', {
	order_id: 0,
	customer_id: 0,
} );

export const DEFAULT_STATE = {
	redirectUrl: '',
	status: STATUS.PRISTINE,
	hasError: false,
	calculatingCount: 0,
	orderId: checkoutData.order_id,
	orderNotes: '',
	customerId: checkoutData.customer_id,
	shouldCreateAccount: false,
	processingResponse: null,
};

export const TYPES = {
	SET_IDLE: 'set_idle',
	SET_PRISTINE: 'set_pristine',
	SET_REDIRECT_URL: 'set_redirect_url',
	SET_COMPLETE: 'set_checkout_complete',
	SET_BEFORE_PROCESSING: 'set_before_processing',
	SET_AFTER_PROCESSING: 'set_after_processing',
	SET_PROCESSING_RESPONSE: 'set_processing_response',
	SET_PROCESSING: 'set_checkout_is_processing',
	SET_HAS_ERROR: 'set_checkout_has_error',
	SET_NO_ERROR: 'set_checkout_no_error',
	SET_ORDER_ID: 'set_checkout_order_id',
	SET_ORDER_NOTES: 'set_checkout_order_notes',
	INCREMENT_CALCULATING: 'increment_calculating',
	DECREMENT_CALCULATING: 'decrement_calculating',
};
