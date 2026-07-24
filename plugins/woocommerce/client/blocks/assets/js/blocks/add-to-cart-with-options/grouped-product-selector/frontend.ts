/**
 * External dependencies
 */
import { store, getContext, getConfig } from '@wordpress/interactivity';
import '@woocommerce/stores/woocommerce';
import type { WooCommerce } from '@woocommerce/stores/woocommerce';

/**
 * Internal dependencies
 */
import type {
	AddToCartWithOptionsStore,
	Context as AddToCartWithOptionsStoreContext,
} from '../frontend';

// Stores are locked to prevent 3PD usage until the API is stable.
const universalLock =
	'I acknowledge that using a private store means my plugin will inevitably break on the next store release.';

// A static value-import of the unified `woocommerce` root module (above),
// rather than a type-only one, so this block self-registers
// `state.findItem` regardless of which other blocks share the page.
const { state: wooState } = store< WooCommerce >(
	'woocommerce',
	{},
	{ lock: universalLock }
);

export type GroupedProductAddToCartWithOptionsStore =
	AddToCartWithOptionsStore & {
		actions: {
			validateGroupedProductQuantity: () => void;
		};
		callbacks: {
			validateQuantities: () => void;
		};
	};

const { actions } = store< GroupedProductAddToCartWithOptionsStore >(
	'woocommerce/add-to-cart-with-options',
	{
		actions: {
			validateGroupedProductQuantity() {
				actions.clearErrors( 'invalid-quantities' );

				const { errorMessages } = getConfig();
				const context =
					getContext< AddToCartWithOptionsStoreContext >();

				// Validate that at least one product quantity is above 0.
				const hasNonZeroQuantity = Object.values(
					context.quantity
				).some( ( qty ) => qty > 0 );

				if ( ! hasNonZeroQuantity ) {
					actions.addError( {
						code: 'groupedProductAddToCartMissingItems',
						message:
							errorMessages?.groupedProductAddToCartMissingItems ||
							'',
						group: 'invalid-quantities',
					} );

					return;
				}

				// Validate that all product quantities are within the min and max (or 0).
				const hasInvalidQuantity = Object.entries(
					context.quantity
				).some( ( [ id, qty ] ) => {
					const product = wooState.findItem( {
						id: Number( id ),
						selectedAttributes: context.selectedAttributes,
					} ).product;
					if ( ! product ) {
						return false;
					}
					const { minimum, maximum } = product.add_to_cart;
					return qty !== 0 && ( qty < minimum || qty > maximum );
				} );

				if ( hasInvalidQuantity ) {
					actions.addError( {
						code: 'invalidQuantities',
						message: errorMessages?.invalidQuantities || '',
						group: 'invalid-quantities',
					} );
				}
			},
		},
		callbacks: {
			validateQuantities() {
				actions.validateGroupedProductQuantity();
			},
		},
	},
	{ lock: universalLock }
);
