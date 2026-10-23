/**
 * External dependencies
 */
import type { AddToCartEventProduct } from '@woocommerce/types';

type CartLine = {
	key?: string | undefined;
	id: number;
	quantity: number;
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
	const previousQuantities = new Map(
		previousItems.map( ( item ) => [ item.key, item.quantity ] )
	);
	const addedQuantities = new Map< number, number >();
	for ( const item of items ) {
		const added =
			item.quantity - ( previousQuantities.get( item.key ) ?? 0 );
		if ( added > 0 ) {
			addedQuantities.set(
				item.id,
				( addedQuantities.get( item.id ) ?? 0 ) + added
			);
		}
	}
	return Array.from( addedQuantities, ( [ id, quantity ] ) => ( {
		id,
		quantity,
	} ) );
};
