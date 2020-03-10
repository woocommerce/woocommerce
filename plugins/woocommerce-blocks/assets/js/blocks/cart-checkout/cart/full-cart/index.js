// @ts-nocheck
/**
 * External dependencies
 */
import PropTypes from 'prop-types';
import { __ } from '@wordpress/i18n';
import {
	SubtotalsItem,
	TotalsFeesItem,
	TotalsCouponCodeInput,
	TotalsDiscountItem,
	TotalsFooterItem,
	TotalsShippingItem,
	TotalsTaxesItem,
} from '@woocommerce/base-components/totals';
import ShippingRatesControl from '@woocommerce/base-components/shipping-rates-control';
import {
	COUPONS_ENABLED,
	SHIPPING_ENABLED,
	DISPLAY_CART_PRICES_INCLUDING_TAX,
} from '@woocommerce/block-settings';
import { getCurrencyFromPriceResponse } from '@woocommerce/base-utils';
import { Card, CardBody } from 'wordpress-components';
import FormattedMonetaryAmount from '@woocommerce/base-components/formatted-monetary-amount';
import { decodeEntities } from '@wordpress/html-entities';
import { useStoreCartCoupons, useShippingRates } from '@woocommerce/base-hooks';
import classnames from 'classnames';

/**
 * Internal dependencies
 */
import CheckoutButton from './checkout-button';
import CartLineItemsTitle from './cart-line-items-title';
import CartLineItemsTable from './cart-line-items-table';

import './style.scss';
import './editor.scss';

const renderShippingRatesControlOption = ( option ) => ( {
	label: decodeEntities( option.name ),
	value: option.rate_id,
	description: (
		<>
			{ option.price && (
				<FormattedMonetaryAmount
					currency={ getCurrencyFromPriceResponse( option ) }
					value={ option.price }
				/>
			) }
			{ option.price && option.delivery_time ? ' — ' : null }
			{ decodeEntities( option.delivery_time ) }
		</>
	),
} );

const ShippingCalculatorOptions = ( {
	shippingRates,
	shippingRatesLoading,
	shippingAddress,
} ) => {
	return (
		<fieldset className="wc-block-cart__shipping-options-fieldset">
			<legend className="screen-reader-text">
				{ __(
					'Choose the shipping method.',
					'woo-gutenberg-products-block'
				) }
			</legend>
			<ShippingRatesControl
				className="wc-block-cart__shipping-options"
				address={
					shippingAddress
						? {
								city: shippingAddress.city,
								state: shippingAddress.state,
								postcode: shippingAddress.postcode,
								country: shippingAddress.country,
						  }
						: null
				}
				collapsibleWhenMultiple={ true }
				noResultsMessage={ __(
					'No shipping options were found.',
					'woo-gutenberg-products-block'
				) }
				renderOption={ renderShippingRatesControlOption }
				shippingRates={ shippingRates }
				shippingRatesLoading={ shippingRatesLoading }
			/>
		</fieldset>
	);
};

/**
 * Component that renders the Cart block when user has something in cart aka "full".
 */
const Cart = ( {
	cartItems = [],
	cartTotals = {},
	cartCoupons = [],
	isShippingCalculatorEnabled,
	isShippingCostHidden,
	shippingRates,
	isLoading = false,
} ) => {
	const { updateShippingAddress, shippingRatesLoading } = useShippingRates();
	const shippingAddress = shippingRates[ 0 ]?.destination;
	const {
		applyCoupon,
		removeCoupon,
		isApplyingCoupon,
		isRemovingCoupon,
	} = useStoreCartCoupons();

	const showShippingCosts = Boolean(
		SHIPPING_ENABLED &&
			isShippingCalculatorEnabled &&
			( ! isShippingCostHidden || shippingAddress?.country )
	);

	const totalsCurrency = getCurrencyFromPriceResponse( cartTotals );

	const cartClassName = classnames( 'wc-block-cart', {
		'wc-block-cart--is-loading': isLoading,
	} );

	return (
		<div className={ cartClassName }>
			<div className="wc-block-cart__main">
				<CartLineItemsTitle itemCount={ cartItems.length } />
				<CartLineItemsTable
					lineItems={ cartItems }
					isLoading={ isLoading }
				/>
			</div>
			<div className="wc-block-cart__sidebar">
				<Card isElevated={ true }>
					<CardBody>
						<h2 className="wc-block-cart__totals-title">
							{ __(
								'Cart totals',
								'woo-gutenberg-products-block'
							) }
						</h2>
						<SubtotalsItem
							currency={ totalsCurrency }
							values={ cartTotals }
						/>
						<TotalsFeesItem
							currency={ totalsCurrency }
							values={ cartTotals }
						/>
						<TotalsDiscountItem
							cartCoupons={ cartCoupons }
							currency={ totalsCurrency }
							isRemovingCoupon={ isRemovingCoupon }
							removeCoupon={ removeCoupon }
							values={ cartTotals }
						/>
						{ isShippingCalculatorEnabled && (
							<TotalsShippingItem
								currency={ totalsCurrency }
								shippingAddress={ shippingAddress }
								updateShippingAddress={ updateShippingAddress }
								values={ cartTotals }
							/>
						) }
						{ showShippingCosts && (
							<fieldset className="wc-block-cart__shipping-options-fieldset">
								<legend className="screen-reader-text">
									{ __(
										'Choose the shipping method.',
										'woo-gutenberg-products-block'
									) }
								</legend>
								<ShippingCalculatorOptions
									shippingRates={ shippingRates }
									shippingRatesLoading={
										shippingRatesLoading
									}
									shippingAddress={ shippingAddress }
								/>
							</fieldset>
						) }
						{ ! DISPLAY_CART_PRICES_INCLUDING_TAX && (
							<TotalsTaxesItem
								currency={ totalsCurrency }
								values={ cartTotals }
							/>
						) }
						{ COUPONS_ENABLED && (
							<TotalsCouponCodeInput
								onSubmit={ applyCoupon }
								isLoading={ isApplyingCoupon }
							/>
						) }
						<TotalsFooterItem
							currency={ totalsCurrency }
							values={ cartTotals }
						/>
						<CheckoutButton />
					</CardBody>
				</Card>
			</div>
		</div>
	);
};

Cart.propTypes = {
	cartItems: PropTypes.array,
	cartTotals: PropTypes.shape( {
		total_items: PropTypes.string,
		total_items_tax: PropTypes.string,
		total_fees: PropTypes.string,
		total_fees_tax: PropTypes.string,
		total_discount: PropTypes.string,
		total_discount_tax: PropTypes.string,
		total_shipping: PropTypes.string,
		total_shipping_tax: PropTypes.string,
		total_tax: PropTypes.string,
		total_price: PropTypes.string,
	} ),
	isShippingCalculatorEnabled: PropTypes.bool,
	isShippingCostHidden: PropTypes.bool,
	isLoading: PropTypes.bool,
	/**
	 * List of shipping rates to display. If defined, shipping rates will not be fetched from the API (used for the block preview).
	 */
	shippingRates: PropTypes.array,
};

export default Cart;
