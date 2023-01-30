/**
 * External dependencies
 */
import { useState, useCallback } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { DEFAULT_STRIPE_EVENT_HANDLERS } from './constants';

/**
 * A utility hook for maintaining an event handler cache.
 */
export const useEventHandlers = () => {
	const [ paymentRequestEventHandlers, setEventHandlers ] = useState(
		DEFAULT_STRIPE_EVENT_HANDLERS
	);

	const setPaymentRequestEventHandler = useCallback(
		( eventName, handler ) => {
			setEventHandlers( ( prevEventHandlers ) => {
				return {
					...prevEventHandlers,
					[ eventName ]: handler,
				};
			} );
		},
		[ setEventHandlers ]
	);

	const clearPaymentRequestEventHandler = useCallback(
		( eventName ) => {
			// @ts-ignore
			setEventHandlers( ( prevEventHandlers ) => {
				// @ts-ignore
				// eslint-disable-next-line no-unused-vars
				const { [ eventName ]: __, ...newHandlers } = prevEventHandlers;
				return newHandlers;
			} );
		},
		[ setEventHandlers ]
	);
	return {
		paymentRequestEventHandlers,
		setPaymentRequestEventHandler,
		clearPaymentRequestEventHandler,
	};
};
