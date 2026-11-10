/**
 * External dependencies
 */
import clsx from 'clsx';
import { useRef } from '@wordpress/element';
import { useSelect } from '@wordpress/data';
import { checkoutStore } from '@woocommerce/block-data';
import { useDelayedSkeleton } from '@woocommerce/base-components/delayed-content-with-skeleton';
import { usePresence } from '@woocommerce/base-hooks/use-presence';
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
 * Returns `value` as it was before the order summary's skeletons appeared, until they end. Prices and totals rows
 * that use it change at the same moment the skeletons end, instead of when the new data arrives.
 */
export const useSettledOrderSummaryValue = < T >( value: T ): T => {
	const { isLoading } = useOrderSummaryLoadingState();
	const showSkeleton = useDelayedSkeleton( isLoading );
	const settled = useRef( value );
	if ( ! isLoading && ! showSkeleton ) {
		settled.current = value;
	}
	return settled.current;
};

/**
 * For a totals row that only shows sometimes, such as Fees: the settled `cart` (the last one shown while the row
 * collapses), and props for its `TotalsWrapper` that grow it in and collapse it as `isShown` changes.
 */
export const useTotalsRow = (
	cart: StoreCart,
	className: string | undefined,
	isShown: ( settledCart: StoreCart ) => boolean
) => {
	const settledCart = useSettledOrderSummaryValue( cart );
	const row = usePresence( isShown( settledCart ), {
		data: settledCart,
		layout: true,
	} );
	return {
		...row.data,
		wrapperProps: {
			className: clsx( className, row.className ),
			ref: row.ref,
		},
	};
};
