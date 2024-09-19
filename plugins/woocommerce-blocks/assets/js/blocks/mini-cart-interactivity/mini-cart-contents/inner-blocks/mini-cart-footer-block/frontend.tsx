/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { TotalsItem } from '@woocommerce/blocks-components';
import { getCurrencyFromPriceResponse } from '@woocommerce/price-format';
import PaymentMethodIcons from '@woocommerce/base-components/cart-checkout/payment-method-icons';
import { getIconsFromPaymentMethods } from '@woocommerce/base-utils';
import { PaymentEventsProvider } from '@woocommerce/base-context';
import clsx from 'clsx';
// import {
// 	usePaymentMethods,
// 	useStoreCart,
// } from '@woocommerce/base-context/hooks';

/**
 * Internal dependencies
 */
import CartButton from '../mini-cart-cart-button-block/frontend';
import CheckoutButton from '../mini-cart-checkout-button-block/frontend';
import { hasChildren } from '../utils';

const PaymentMethodIconsElement = (): JSX.Element => {
	// const { paymentMethods } = usePaymentMethods();

	return <PaymentMethodIcons icons={ getIconsFromPaymentMethods( {} ) } />;
};

interface Props {
	children: JSX.Element | JSX.Element[];
	className?: string;
	cartButtonLabel: string;
	checkoutButtonLabel: string;
}

const Block = ( {
	children,
	className,
	cartButtonLabel,
	checkoutButtonLabel,
}: Props ): JSX.Element => {
	// const { cartTotals } = useStoreCart();
	// const subTotal = getSetting( 'displayCartPricesIncludingTax', false )
	// 	? parseInt( cartTotals.total_items, 10 ) +
	// 	  parseInt( cartTotals.total_items_tax, 10 )
	// 	: parseInt( cartTotals.total_items, 10 );

	const subTotal = 0;
	const cartTotals = {};

	// The `Cart` and `Checkout` buttons were converted to inner blocks, but we still need to render the buttons
	// for themes that have the old `mini-cart.html` template. So we check if there are any inner blocks (buttons) and
	// if not, render the buttons.
	const hasButtons = hasChildren( children );

	return (
		<div className={ clsx( className, 'wc-block-mini-cart__footer' ) }>
			<TotalsItem
				className="wc-block-mini-cart__footer-subtotal"
				currency={ getCurrencyFromPriceResponse( cartTotals ) }
				label={ __( 'Subtotal', 'woocommerce' ) }
				value={ subTotal }
				description={ __(
					'Shipping, taxes, and discounts calculated at checkout.',
					'woocommerce'
				) }
			/>
			<div className="wc-block-mini-cart__footer-actions">
				{ hasButtons ? (
					children
				) : (
					<>
						<CartButton cartButtonLabel={ cartButtonLabel } />
						<CheckoutButton
							checkoutButtonLabel={ checkoutButtonLabel }
						/>
					</>
				) }
			</div>
			<PaymentEventsProvider>
				<PaymentMethodIconsElement />
			</PaymentEventsProvider>
		</div>
	);
};

export default Block;
