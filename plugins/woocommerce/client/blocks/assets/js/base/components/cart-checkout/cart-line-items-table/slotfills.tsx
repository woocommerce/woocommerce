/**
 * External dependencies
 */
import { getCartItemActions } from '@woocommerce/blocks-checkout';
import type { CartItem } from '@woocommerce/types';

interface CartItemActionsProps {
	lineItem: CartItem;
	cart: Record< string, unknown >;
}

export const CartItemActions = ( { lineItem, cart }: CartItemActionsProps ): JSX.Element => {
	const actions = getCartItemActions();
	
	return (
		<>
			{ actions.map( ( Component, index ) => (
				<Component key={ index } lineItem={ lineItem } cart={ cart } />
			) ) }
		</>
	);
};
