/**
 * External dependencies
 */
import { useSelect } from '@wordpress/data';
import { cartStore } from '@woocommerce/block-data';
import type { CartRegulatoryField } from '@woocommerce/types';

const NO_FIELDS: CartRegulatoryField[] = [];

/**
 * Returns the regulatory fields the server lists for the current cart.
 *
 * The server decides which fields apply, from the addresses and the shipping method, and the list
 * comes back with every cart update.
 */
export const useRegulatoryFields = (): CartRegulatoryField[] =>
	useSelect(
		( select ) =>
			select( cartStore ).getCartData().regulatoryFields || NO_FIELDS,
		[]
	);
