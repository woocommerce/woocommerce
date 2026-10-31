/**
 * External dependencies
 */
import type { AddToCartEventProduct } from '@woocommerce/types';

type CartLine = {
	id: number;
	quantity: number;
};

const getQuantitiesById = ( items: CartLine[] ) => {
	const quantities = new Map< number, number >();
	for ( const { id, quantity } of items ) {
		quantities.set( id, ( quantities.get( id ) ?? 0 ) + quantity );
	}
	return quantities;
};

/**
 * Lists the products whose quantity went up between two versions of the cart,
 * with the quantity added to each one, so the added-to-cart event reports what
 * the server committed rather than what was requested.
 *
 * @param previousItems Cart lines before the change.
 * @param items         Cart lines after the change.
 * @return The added quantity per product or variation ID.
 */
export const getAddedProducts = (
	previousItems: CartLine[],
	items: CartLine[]
): AddToCartEventProduct[] => {
	const previousQuantities = getQuantitiesById( previousItems );
	return Array.from( getQuantitiesById( items ) ).flatMap(
		( [ id, quantity ] ) => {
			const added = quantity - ( previousQuantities.get( id ) ?? 0 );
			return added > 0 ? [ { id, quantity: added } ] : [];
		}
	);
};
