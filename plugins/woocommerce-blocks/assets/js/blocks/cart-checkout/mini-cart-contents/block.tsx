/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { useEffect, useRef } from '@wordpress/element';
import {
	usePaymentMethods,
	useStoreCart,
} from '@woocommerce/base-context/hooks';
import { TotalsItem } from '@woocommerce/blocks-checkout';
import { CART_URL, CHECKOUT_URL } from '@woocommerce/block-settings';
import Button from '@woocommerce/base-components/button';
import { PaymentMethodDataProvider } from '@woocommerce/base-context';
import { getIconsFromPaymentMethods } from '@woocommerce/base-utils';
import PaymentMethodIcons from '@woocommerce/base-components/cart-checkout/payment-method-icons';
import { getCurrencyFromPriceResponse } from '@woocommerce/price-format';
import { getSetting } from '@woocommerce/settings';

/**
 * Internal dependencies
 */
import CartLineItemsTable from '../cart/cart-line-items-table';

const PaymentMethodIconsElement = (): JSX.Element => {
	const { paymentMethods } = usePaymentMethods();
	return (
		<PaymentMethodIcons
			icons={ getIconsFromPaymentMethods( paymentMethods ) }
		/>
	);
};

const MiniCartContentsBlock = (): JSX.Element => {
	const { cartItems, cartIsLoading, cartTotals } = useStoreCart();
	const emptyCartRef = useRef< HTMLDivElement | null >( null );

	const subTotal = getSetting( 'displayCartPricesIncludingTax', false )
		? parseInt( cartTotals.total_items, 10 ) +
		  parseInt( cartTotals.total_items_tax, 10 )
		: parseInt( cartTotals.total_items, 10 );

	useEffect( () => {
		// If the cart has been completely emptied, move focus to empty cart
		// element.
		if ( ! cartIsLoading && cartItems.length === 0 ) {
			if ( emptyCartRef.current instanceof HTMLElement ) {
				emptyCartRef.current.focus();
			}
		}
	}, [ cartIsLoading, cartItems.length, emptyCartRef ] );

	return ! cartIsLoading && cartItems.length === 0 ? (
		<div
			className="wc-block-mini-cart__empty-cart"
			tabIndex={ -1 }
			ref={ emptyCartRef }
		>
			{ __( 'Cart is empty', 'woo-gutenberg-products-block' ) }
		</div>
	) : (
		<>
			<div className="wc-block-mini-cart__items">
				<CartLineItemsTable
					lineItems={ cartItems }
					isLoading={ cartIsLoading }
				/>
			</div>
			<div className="wc-block-mini-cart__footer">
				<TotalsItem
					className="wc-block-mini-cart__footer-subtotal"
					currency={ getCurrencyFromPriceResponse( cartTotals ) }
					label={ __( 'Subtotal', 'woo-gutenberg-products-block' ) }
					value={ subTotal }
					description={ __(
						'Shipping, taxes, and discounts calculated at checkout.',
						'woo-gutenberg-products-block'
					) }
				/>
				<div className="wc-block-mini-cart__footer-actions">
					<Button
						className="wc-block-mini-cart__footer-cart"
						href={ CART_URL }
					>
						{ __( 'View my cart', 'woo-gutenberg-products-block' ) }
					</Button>
					<Button
						className="wc-block-mini-cart__footer-checkout"
						href={ CHECKOUT_URL }
					>
						{ __(
							'Go to checkout',
							'woo-gutenberg-products-block'
						) }
					</Button>
				</div>
				<PaymentMethodDataProvider>
					<PaymentMethodIconsElement />
				</PaymentMethodDataProvider>
			</div>
		</>
	);
};

export default MiniCartContentsBlock;
