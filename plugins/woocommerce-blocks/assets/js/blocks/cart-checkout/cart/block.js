/**
 * External dependencies
 */
import { useStoreCart } from '@woocommerce/base-hooks';
import { RawHTML } from '@wordpress/element';
import LoadingMask from '@woocommerce/base-components/loading-mask';
import {
	ValidationContextProvider,
	CartProvider,
} from '@woocommerce/base-context';

/**
 * Internal dependencies
 */
import FullCart from './full-cart';

const Block = ( { emptyCart, attributes } ) => {
	const { cartItems, cartIsLoading } = useStoreCart();

	return (
		<>
			{ ! cartIsLoading && cartItems.length === 0 ? (
				<RawHTML>{ emptyCart }</RawHTML>
			) : (
				<LoadingMask showSpinner={ true } isLoading={ cartIsLoading }>
					<ValidationContextProvider>
						<CartProvider>
							<FullCart attributes={ attributes } />
						</CartProvider>
					</ValidationContextProvider>
				</LoadingMask>
			) }
		</>
	);
};

export default Block;
