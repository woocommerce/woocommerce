/**
 * External dependencies
 */
import { useLayoutEffect, useRef } from '@wordpress/element';
import { useSelect } from '@wordpress/data';
import { checkoutStore } from '@woocommerce/block-data';
import { useDelayedSkeleton } from '@woocommerce/base-components/delayed-content-with-skeleton';
import { usePresence } from '@woocommerce/base-hooks';
import type { StoreCart } from '@woocommerce/types';

/**
 * Internal dependencies
 */
import { useStoreCart } from './cart/use-store-cart';
import { useStoreCartCoupons } from './cart/use-store-cart-coupons';

/**
 * Custom hook that provides comprehensive loading states for Order Summary components.
 * This hook combines various loading states that can affect order totals and pricing.
 */
export const useOrderSummaryLoadingState = () => {
	const { cartIsLoading, isLoadingRates, hasPendingItemsOperations } =
		useStoreCart();
	const { isApplyingCoupon, isRemovingCoupon } = useStoreCartCoupons();

	const isCalculating = useSelect(
		( select ) => select( checkoutStore ).isCalculating(),
		[]
	);

	// Determine if any loading state is active
	const isLoading =
		cartIsLoading ||
		isLoadingRates ||
		isApplyingCoupon ||
		isRemovingCoupon ||
		isCalculating ||
		hasPendingItemsOperations;

	return {
		// Combined loading state - true if any operation is in progress
		isLoading,
	};
};

/**
 * Returns `value` as it was before the order summary's skeletons appeared, until they end. Values that use it change
 * at the same moment as the values behind the skeletons, not when the new data arrives.
 */
export const useSettledOrderSummaryValue = < T >( value: T ): T => {
	const { isLoading } = useOrderSummaryLoadingState();
	const showSkeleton = useDelayedSkeleton( isLoading );
	const settled = useRef( value );
	if ( ! showSkeleton ) {
		settled.current = value;
	}
	return settled.current;
};

/**
 * For an order summary row that only shows sometimes, such as Fees. The row appears and goes when the skeletons end,
 * with the other values. It grows in and collapses out, so the rows below and the total move with it. Rows of the
 * cart that loads with the page show immediately.
 *
 * @param storeCart The cart from `useStoreCart`.
 * @param isShown   Whether the row shows for the settled cart.
 * @return The settled cart to render the row from, or `null` when the row is gone. While the row collapses, it is the
 *         last cart that the row showed. Spread `wrapperProps` on the row's `TotalsWrapper`.
 */
export const useOrderSummaryRow = (
	storeCart: StoreCart,
	isShown: ( cart: StoreCart ) => boolean
) => {
	const cart = useSettledOrderSummaryValue( storeCart );
	const shown = isShown( cart );
	const lastShown = useRef( cart );
	if ( shown ) {
		lastShown.current = cart;
	}
	// Updated after the commit, so the render that settles the first load does not animate.
	const wasLoaded = useRef( ! cart.cartIsLoading );
	useLayoutEffect( () => {
		wasLoaded.current = ! cart.cartIsLoading;
	}, [ cart.cartIsLoading ] );
	// A boolean, so a new cart object never counts as a change of presence.
	const presence = usePresence( shown || null, {
		animate: wasLoaded.current,
		collapse: true,
	} );
	return {
		cart: presence.value ? lastShown.current : null,
		wrapperProps: {
			elementRef: presence.ref,
			isExiting: presence.isExiting,
		},
	};
};
