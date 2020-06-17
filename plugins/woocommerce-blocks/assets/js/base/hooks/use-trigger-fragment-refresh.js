/**
 * External dependencies
 */
import { useRef, useEffect } from '@wordpress/element';
import { triggerFragmentRefresh } from '@woocommerce/base-utils';

/**
 * Helper that implements triggerFragmentRefresh.
 *
 * @param {number} quantityInCart Quantity of the item in the cart.
 */
export const useTriggerFragmentRefresh = ( quantityInCart ) => {
	const firstMount = useRef( true );

	useEffect( () => {
		if ( firstMount.current ) {
			firstMount.current = false;
			return;
		}
		triggerFragmentRefresh();
	}, [ quantityInCart ] );
};
