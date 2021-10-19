/**
 * External dependencies
 */
import { useState, useEffect } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { useStoreEvents } from '../../../hooks/use-store-events';

export const useActivePaymentMethod = (): {
	activePaymentMethod: string;
	activeSavedToken: string;
	setActivePaymentMethod: React.Dispatch< React.SetStateAction< string > >;
	setActiveSavedToken: ( token: string ) => void;
} => {
	const { dispatchCheckoutEvent } = useStoreEvents();

	// The active payment method - e.g. Stripe CC or BACS.
	const [ activePaymentMethod, setActivePaymentMethod ] = useState( '' );

	// If a previously saved payment method is active, the token for that method. For example, a for a Stripe CC card saved to user account.
	const [ activeSavedToken, setActiveSavedToken ] = useState( '' );

	// Trigger event on change.
	useEffect( () => {
		dispatchCheckoutEvent( 'set-active-payment-method', {
			activePaymentMethod,
		} );
	}, [ dispatchCheckoutEvent, activePaymentMethod ] );

	return {
		activePaymentMethod,
		activeSavedToken,
		setActivePaymentMethod,
		setActiveSavedToken,
	};
};
