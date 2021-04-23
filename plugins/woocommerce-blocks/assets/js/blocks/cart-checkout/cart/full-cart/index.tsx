/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import {
	TotalsCoupon,
	TotalsDiscount,
	TotalsFooterItem,
	TotalsShipping,
} from '@woocommerce/base-components/cart-checkout';
import {
	Subtotal,
	TotalsFees,
	TotalsTaxes,
	ExperimentalOrderMeta,
} from '@woocommerce/blocks-checkout';

import { getCurrencyFromPriceResponse } from '@woocommerce/price-format';
import {
	useStoreCartCoupons,
	useStoreCart,
	useStoreNotices,
} from '@woocommerce/base-context/hooks';
import classnames from 'classnames';
import {
	Sidebar,
	SidebarLayout,
	Main,
} from '@woocommerce/base-components/sidebar-layout';
import Title from '@woocommerce/base-components/title';
import { getSetting } from '@woocommerce/settings';
import { useEffect } from '@wordpress/element';
import { decodeEntities } from '@wordpress/html-entities';
import { CartProvider } from '@woocommerce/base-context';

/**
 * Internal dependencies
 */
import CheckoutButton from '../checkout-button';
import CartLineItemsTitle from './cart-line-items-title';
import CartLineItemsTable from './cart-line-items-table';
import { CartExpressPayment } from '../../payment-methods';

import './style.scss';

interface CartAttributes {
	hasDarkControls: boolean;
	isShippingCalculatorEnabled: boolean;
	checkoutPageId: number;
	isPreview: boolean;
}

interface CartProps {
	attributes: CartAttributes;
}
/**
 * Component that renders the Cart block when user has something in cart aka "full".
 *
 * @param {Object} props Incoming props for the component.
 * @param {Object} props.attributes Incoming attributes for block.
 */
const Cart = ( { attributes }: CartProps ) => {
	const { isShippingCalculatorEnabled, hasDarkControls } = attributes;

	const {
		cartItems,
		cartFees,
		cartTotals,
		cartIsLoading,
		cartItemsCount,
		cartItemErrors,
		cartNeedsPayment,
		cartNeedsShipping,
	} = useStoreCart();

	const {
		applyCoupon,
		removeCoupon,
		isApplyingCoupon,
		isRemovingCoupon,
		appliedCoupons,
	} = useStoreCartCoupons();

	const { addErrorNotice } = useStoreNotices();

	// Ensures any cart errors listed in the API response get shown.
	useEffect( () => {
		cartItemErrors.forEach( ( error ) => {
			addErrorNotice( decodeEntities( error.message ), {
				isDismissible: true,
				id: error.code,
			} );
		} );
	}, [ addErrorNotice, cartItemErrors ] );

	const totalsCurrency = getCurrencyFromPriceResponse( cartTotals );

	const cartClassName = classnames( 'wc-block-cart', {
		'wc-block-cart--is-loading': cartIsLoading,
		'has-dark-controls': hasDarkControls,
	} );

	// Prepare props to pass to the ExperimentalOrderMeta slot fill.
	// We need to pluck out receiveCart.
	// eslint-disable-next-line no-unused-vars
	const { extensions, ...cart } = useStoreCart();
	const slotFillProps = {
		extensions,
		cart,
	};

	return (
		<>
			<CartLineItemsTitle itemCount={ cartItemsCount } />
			<SidebarLayout className={ cartClassName }>
				<Main className="wc-block-cart__main">
					<CartLineItemsTable
						lineItems={ cartItems }
						isLoading={ cartIsLoading }
					/>
				</Main>
				<Sidebar className="wc-block-cart__sidebar">
					<Title
						headingLevel="2"
						className="wc-block-cart__totals-title"
					>
						{ __( 'Cart totals', 'woo-gutenberg-products-block' ) }
					</Title>
					<Subtotal
						currency={ totalsCurrency }
						values={ cartTotals }
					/>
					<TotalsFees
						currency={ totalsCurrency }
						cartFees={ cartFees }
					/>
					<TotalsDiscount
						cartCoupons={ appliedCoupons }
						currency={ totalsCurrency }
						isRemovingCoupon={ isRemovingCoupon }
						removeCoupon={ removeCoupon }
						values={ cartTotals }
					/>
					{ cartNeedsShipping && (
						<TotalsShipping
							showCalculator={ isShippingCalculatorEnabled }
							showRateSelector={ true }
							values={ cartTotals }
							currency={ totalsCurrency }
						/>
					) }
					{ ! getSetting(
						'displayCartPricesIncludingTax',
						false
					) && (
						<TotalsTaxes
							currency={ totalsCurrency }
							values={ cartTotals }
						/>
					) }
					{ getSetting( 'couponsEnabled', true ) && (
						<TotalsCoupon
							onSubmit={ applyCoupon }
							isLoading={ isApplyingCoupon }
						/>
					) }
					<TotalsFooterItem
						currency={ totalsCurrency }
						values={ cartTotals }
					/>
					<ExperimentalOrderMeta.Slot { ...slotFillProps } />
					<div className="wc-block-cart__payment-options">
						{ cartNeedsPayment && <CartExpressPayment /> }
						<CheckoutButton
							link={ getSetting(
								'page-' + attributes?.checkoutPageId,
								false
							) }
						/>
					</div>
				</Sidebar>
			</SidebarLayout>
		</>
	);
};

const Block = ( props: CartProps ): JSX.Element => {
	return (
		<CartProvider>
			<Cart { ...props } />
		</CartProvider>
	);
};

export default Block;
