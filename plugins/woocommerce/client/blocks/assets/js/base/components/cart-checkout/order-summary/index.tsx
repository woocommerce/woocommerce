/**
 * External dependencies
 */
import {
	useContainerWidthContext,
	useSettledOrderSummaryValue,
	useStoreCart,
} from '@woocommerce/base-context';
import type { CartItem } from '@woocommerce/types';
import clsx from 'clsx';
import { CartLineItemsCheckoutSkeleton } from '@woocommerce/base-components/skeleton/patterns/cart-line-items';
import { DelayedContentWithSkeleton } from '@woocommerce/base-components/delayed-content-with-skeleton';
/**
 * Internal dependencies
 */
import OrderSummaryItem from './order-summary-item';
import './style.scss';

interface OrderSummaryProps {
	cartItems: CartItem[];
	disableProductDescriptions: boolean;
}

const OrderSummary = ( {
	cartItems = [],
	disableProductDescriptions = false,
}: OrderSummaryProps ): null | JSX.Element => {
	const { isLarge } = useContainerWidthContext();
	const { cartIsLoading, hasPendingItemsOperations } = useStoreCart();
	const showSkeleton = cartIsLoading || hasPendingItemsOperations;
	// The item prices change when the totals skeletons end, not before.
	const items = useSettledOrderSummaryValue( cartItems );

	return (
		<DelayedContentWithSkeleton
			isLoading={ showSkeleton }
			skeleton={
				<CartLineItemsCheckoutSkeleton rows={ items?.length || 2 } />
			}
		>
			<div
				className={ clsx( 'wc-block-components-order-summary', {
					'is-large': isLarge,
				} ) }
			>
				<div className="wc-block-components-order-summary__content">
					{ items.map( ( cartItem ) => {
						return (
							<OrderSummaryItem
								disableProductDescriptions={
									disableProductDescriptions
								}
								key={ cartItem.key }
								cartItem={ cartItem }
							/>
						);
					} ) }
				</div>
			</div>
		</DelayedContentWithSkeleton>
	);
};

export default OrderSummary;
