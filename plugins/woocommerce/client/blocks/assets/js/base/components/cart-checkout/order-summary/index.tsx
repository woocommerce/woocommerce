/**
 * External dependencies
 */
import { useContainerWidthContext } from '@woocommerce/base-context';
import type { CartItem } from '@woocommerce/types';
import clsx from 'clsx';

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
	const { isLarge, hasContainerWidth } = useContainerWidthContext();

	if ( ! hasContainerWidth ) {
		return null;
	}

	return (
		<div
			className={ clsx( 'wc-block-components-order-summary', {
				'is-large': isLarge,
			} ) }
		>
			<div className="wc-block-components-order-summary__content">
				{ cartItems.map( ( cartItem ) => {
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
	);
};

export default OrderSummary;
