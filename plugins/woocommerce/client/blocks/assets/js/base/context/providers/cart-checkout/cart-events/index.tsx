/**
 * External dependencies
 */
import { createContext, useContext } from '@wordpress/element';
import type { ObserverResponse } from '@woocommerce/types';

/**
 * Internal dependencies
 */
import { cartEventsEmitter, CART_EVENTS } from '../../../../../events/cart-events';
import type { EventListener } from '../../../../../events/event-emitter';

type CartEventsContextType = {
	onProceedToCheckout: (
		callback: EventListener,
		priority?: number
	) => () => void;
	dispatchOnProceedToCheckout: () => Promise< ObserverResponse[] >;
};

const CartEventsContext = createContext< CartEventsContextType >( {
	onProceedToCheckout: () => () => void null,
	dispatchOnProceedToCheckout: () => Promise.resolve( [] ),
} );

export const useCartEventsContext = () => {
	return useContext( CartEventsContext );
};

/**
 * Cart Events provider
 * Delegates to the shared cartEventsEmitter so that both React hooks
 * and Interactivity API stores share the same observer registry.
 */
export const CartEventsProvider = ( {
	children,
}: {
	children: React.ReactNode;
} ): JSX.Element => {
	const cartEventsValue: CartEventsContextType = {
		onProceedToCheckout: ( callback: EventListener, priority = 10 ) =>
			cartEventsEmitter.subscribe(
				callback,
				priority,
				CART_EVENTS.PROCEED_TO_CHECKOUT
			),
		dispatchOnProceedToCheckout: () =>
			cartEventsEmitter.emitWithAbort(
				CART_EVENTS.PROCEED_TO_CHECKOUT,
				null
			),
	};

	return (
		<CartEventsContext.Provider value={ cartEventsValue }>
			{ children }
		</CartEventsContext.Provider>
	);
};
