/**
 * External dependencies
 */
import { useRef } from '@wordpress/element';
import { useSelect } from '@wordpress/data';
import { checkoutStore } from '@woocommerce/block-data';
import { useDelayedSkeleton } from '@woocommerce/base-components/delayed-content-with-skeleton';

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
