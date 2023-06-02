// @ts-nocheck
/**
 * External dependencies
 */
import PropTypes from 'prop-types';
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
	COUPONS_ENABLED,
	DISPLAY_CART_PRICES_INCLUDING_TAX,
} from '@woocommerce/block-settings';
import { CartExpressPayment } from '@woocommerce/base-components/payment-methods';
import {
	useStoreCartCoupons,
	useStoreCart,
	useStoreNotices,
} from '@woocommerce/base-hooks';
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

import './style.scss';

const Block = ( props ) => {
	return (
		<CartProvider>
			<Cart { ...props } />
		</CartProvider>
	);
};

/**
 * Component that renders the Cart block when user has something in cart aka "full".
 *
 * @param {Object} props Incoming props for the component.
 * @param {Object} props.attributes Incoming attributes for block.
 */
const Cart = ( { attributes } ) => {
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

	return (
		<SidebarLayout className={ cartClassName }>
			<Main className="wc-block-cart__main">
				<CartLineItemsTitle itemCount={ cartItemsCount } />
				<CartLineItemsTable
					lineItems={ cartItems }
					isLoading={ cartIsLoading }
				/>
			</Main>
			<Sidebar className="wc-block-cart__sidebar">
				<Title headingLevel="2" className="wc-block-cart__totals-title">
					{ __( 'Cart totals', 'woocommerce' ) }
				</Title>
				<Subtotal currency={ totalsCurrency } values={ cartTotals } />
				<TotalsFees currency={ totalsCurrency } cartFees={ cartFees } />
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
				{ ! DISPLAY_CART_PRICES_INCLUDING_TAX && (
					<TotalsTaxes
						currency={ totalsCurrency }
						values={ cartTotals }
					/>
				) }
				{ COUPONS_ENABLED && (
					<TotalsCoupon
						onSubmit={ applyCoupon }
						isLoading={ isApplyingCoupon }
					/>
				) }
				<TotalsFooterItem
					currency={ totalsCurrency }
					values={ cartTotals }
				/>
				<ExperimentalOrderMeta.Slot />
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
	);
};

Cart.propTypes = {
	attributes: PropTypes.object.isRequired,
};

export default Block;
