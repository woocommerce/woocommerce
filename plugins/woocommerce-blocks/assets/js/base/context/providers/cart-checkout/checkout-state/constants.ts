/**
 * External dependencies
 */
import { getSetting, EnteredAddress } from '@woocommerce/settings';
import { isSameAddress } from '@woocommerce/base-utils';

/**
 * Internal dependencies
 */
import type {
	CheckoutStateContextType,
	CheckoutStateContextState,
} from './types';

export enum STATUS {
	// Checkout is in it's initialized state.
	PRISTINE = 'pristine',
	// When checkout state has changed but there is no activity happening.
	IDLE = 'idle',
	// After BEFORE_PROCESSING status emitters have finished successfully. Payment processing is started on this checkout status.
	PROCESSING = 'processing',
	// After the AFTER_PROCESSING event emitters have completed. This status triggers the checkout redirect.
	COMPLETE = 'complete',
	// This is the state before checkout processing begins after the checkout button has been pressed/submitted.
	BEFORE_PROCESSING = 'before_processing',
	// After server side checkout processing is completed this status is set
	AFTER_PROCESSING = 'after_processing',
}

const preloadedCheckoutData = getSetting( 'checkoutData', {} ) as Record<
	string,
	unknown
>;

const checkoutData = {
	order_id: 0,
	customer_id: 0,
	billing_address: {} as EnteredAddress,
	shipping_address: {} as EnteredAddress,
	...( preloadedCheckoutData || {} ),
};

export const DEFAULT_CHECKOUT_STATE_DATA: CheckoutStateContextType = {
	onSubmit: () => void null,
	onCheckoutAfterProcessingWithSuccess: () => () => void null,
	onCheckoutAfterProcessingWithError: () => () => void null,
	onCheckoutBeforeProcessing: () => () => void null, // deprecated for onCheckoutValidationBeforeProcessing
	onCheckoutValidationBeforeProcessing: () => () => void null,
};

export const DEFAULT_STATE: CheckoutStateContextState = {
	redirectUrl: '',
	status: STATUS.PRISTINE,
	hasError: false,
	calculatingCount: 0,
	orderId: checkoutData.order_id,
	orderNotes: '',
	customerId: checkoutData.customer_id,
	useShippingAsBilling: isSameAddress(
		checkoutData.billing_address,
		checkoutData.shipping_address
	),
	shouldCreateAccount: false,
	processingResponse: null,
	extensionData: {},
};
