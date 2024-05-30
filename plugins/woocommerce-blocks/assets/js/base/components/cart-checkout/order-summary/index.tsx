/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { useContainerWidthContext } from '@woocommerce/base-context';
import { Panel } from '@woocommerce/blocks-components';
import type { CartItem } from '@woocommerce/types';

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
	const { isMobile, isSmall, hasContainerWidth } = useContainerWidthContext();

	if ( ! hasContainerWidth ) {
		return null;
	}

	const panelContent = (
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
	);

	if ( isMobile || isSmall ) {
		return panelContent;
	}

	return (
		<Panel
			className="wc-block-components-order-summary"
			initialOpen={ true }
			hasBorder={ false }
			title={
				<span className="wc-block-components-order-summary__button-text">
					{ __( 'Order summary', 'woocommerce' ) }
				</span>
			}
		>
			{ panelContent }
		</Panel>
	);
};

export default OrderSummary;
