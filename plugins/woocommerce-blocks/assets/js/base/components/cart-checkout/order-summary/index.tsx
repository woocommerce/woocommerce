/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { useContainerWidthContext } from '@woocommerce/base-context';
import { Panel } from '@woocommerce/blocks-components';
import type { CartItem } from '@woocommerce/types';
import clsx from 'clsx';

/**
 * Internal dependencies
 */
import OrderSummaryItem from './order-summary-item';
import './style.scss';

interface OrderSummaryProps {
	cartItems: CartItem[];
}

const OrderSummary = ( {
	cartItems = [],
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
