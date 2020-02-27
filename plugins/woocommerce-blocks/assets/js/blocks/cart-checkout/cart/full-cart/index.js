/**
 * External dependencies
 */
import PropTypes from 'prop-types';
import { sprintf, __ } from '@wordpress/i18n';
import { useState, useEffect } from '@wordpress/element';
import {
	TotalsCouponCodeInput,
	TotalsItem,
} from '@woocommerce/base-components/totals';
import ShippingRatesControl, {
	Packages,
} from '@woocommerce/base-components/shipping-rates-control';
import ShippingCalculator from '@woocommerce/base-components/shipping-calculator';
import ShippingLocation from '@woocommerce/base-components/shipping-location';
import LoadingMask from '@woocommerce/base-components/loading-mask';
import Chip from '@woocommerce/base-components/chip';
import {
	COUPONS_ENABLED,
	SHIPPING_ENABLED,
	DISPLAY_PRICES_INCLUDING_TAXES,
} from '@woocommerce/block-settings';
import { getCurrencyFromPriceResponse } from '@woocommerce/base-utils';
import { Card, CardBody } from 'wordpress-components';
import FormattedMonetaryAmount from '@woocommerce/base-components/formatted-monetary-amount';
import { decodeEntities } from '@wordpress/html-entities';
import { useStoreCartCoupons } from '@woocommerce/base-hooks';
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
	const [ selectedShippingRate, setSelectedShippingRate ] = useState();
	const [
		shippingCalculatorAddress,
		setShippingCalculatorAddress,
	] = useState( {
		city: '',
		state: '',
		postcode: '',
		country: '',
	} );
	const [ showShippingCosts, setShowShippingCosts ] = useState(
		! isShippingCostHidden
	);
	const {
		applyCoupon,
		removeCoupon,
		isApplyingCoupon,
		isRemovingCoupon,
	} = useStoreCartCoupons();

	useEffect( () => {
		if ( ! SHIPPING_ENABLED ) {
			return setShowShippingCosts( false );
		}
		if ( isShippingCalculatorEnabled ) {
			if ( isShippingCostHidden ) {
				if ( shippingCalculatorAddress.country ) {
					return setShowShippingCosts( true );
				}
			} else {
				return setShowShippingCosts( true );
			}
		}
		return setShowShippingCosts( false );
	}, [
		isShippingCalculatorEnabled,
		isShippingCostHidden,
		shippingCalculatorAddress,
	] );

	/**
	 * Given an API response with cart totals, generates an array of rows to display in the Cart block.
	 *
	 * @return {Object[]} Values to display in the cart block.
	 */
	const getTotalRowsConfig = () => {
		const totalItems = parseInt( cartTotals.total_items, 10 );
		const totalItemsTax = parseInt( cartTotals.total_items_tax, 10 );
		const totalRowsConfig = [
			{
				label: __( 'Subtotal:', 'woo-gutenberg-products-block' ),
				value: DISPLAY_PRICES_INCLUDING_TAXES
					? totalItems + totalItemsTax
					: totalItems,
			},
		];
		const totalFees = parseInt( cartTotals.total_fees, 10 );
		if ( totalFees > 0 ) {
			const totalFeesTax = parseInt( cartTotals.total_fees_tax, 10 );
			totalRowsConfig.push( {
				label: __( 'Fees:', 'woo-gutenberg-products-block' ),
				value: DISPLAY_PRICES_INCLUDING_TAXES
					? totalFees + totalFeesTax
					: totalFees,
			} );
		}
		const totalDiscount = parseInt( cartTotals.total_discount, 10 );
		if ( totalDiscount > 0 || cartCoupons.length !== 0 ) {
			const totalDiscountTax = parseInt(
				cartTotals.total_discount_tax,
				10
			);
			// @todo The remove coupon button is a placeholder - replace with new
			// chip component.
			totalRowsConfig.push( {
				label: __( 'Discount:', 'woo-gutenberg-products-block' ),
				value:
					( DISPLAY_PRICES_INCLUDING_TAXES
						? totalDiscount + totalDiscountTax
						: totalDiscount ) * -1,
				description: cartCoupons.length !== 0 && (
					<LoadingMask
						screenReaderLabel={ __(
							'Removing coupon…',
							'woo-gutenberg-products-block'
						) }
						isLoading={ isRemovingCoupon }
						showSpinner={ false }
					>
						<ul className="wc-block-cart-coupon-list">
							{ cartCoupons.map( ( cartCoupon ) => (
								<Chip
									key={ 'coupon-' + cartCoupon.code }
									className="wc-block-cart-coupon-list__item"
									text={ cartCoupon.code }
									screenReaderText={ sprintf(
										/* Translators: %s Coupon code. */
										__(
											'Coupon: %s',
											'woo-gutenberg-products-block'
										),
										cartCoupon.code
									) }
									disabled={ isRemovingCoupon }
									onRemove={ () => {
										removeCoupon( cartCoupon.code );
									} }
									radius="large"
								/>
							) ) }
						</ul>
					</LoadingMask>
				),
			} );
		}
		if ( ! DISPLAY_PRICES_INCLUDING_TAXES ) {
			const totalTax = parseInt( cartTotals.total_tax, 10 );
			totalRowsConfig.push( {
				label: __( 'Taxes:', 'woo-gutenberg-products-block' ),
				value: totalTax,
			} );
		}
		if ( SHIPPING_ENABLED && isShippingCalculatorEnabled ) {
			const totalShipping = parseInt( cartTotals.total_shipping, 10 );
			const totalShippingTax = parseInt(
				cartTotals.total_shipping_tax,
				10
			);
			totalRowsConfig.push( {
				label: __( 'Shipping:', 'woo-gutenberg-products-block' ),
				value: DISPLAY_PRICES_INCLUDING_TAXES
					? totalShipping + totalShippingTax
					: totalShipping,
				description: (
					<>
						<ShippingLocation
							address={ shippingCalculatorAddress }
						/>
						<ShippingCalculator
							address={ shippingCalculatorAddress }
							setAddress={ setShippingCalculatorAddress }
						/>
					</>
				),
			} );
		}
		return totalRowsConfig;
	};

	const totalsCurrency = getCurrencyFromPriceResponse( cartTotals );
	const totalRowsConfig = getTotalRowsConfig();

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
						{ totalRowsConfig.map(
							( { label, value, description } ) => (
								<TotalsItem
									key={ label }
									currency={ totalsCurrency }
									label={ label }
									value={ value }
									description={ description }
								/>
							)
						) }
						{ showShippingCosts && (
							<fieldset className="wc-block-cart__shipping-options-fieldset">
								<legend className="screen-reader-text">
									{ __(
										'Choose the shipping method.',
										'woo-gutenberg-products-block'
									) }
								</legend>
								{ shippingRates ? (
									<Packages
										className="wc-block-cart__shipping-options"
										renderOption={
											renderShippingRatesControlOption
										}
										shippingRates={ shippingRates }
										selected={ [
											// This is only rendered in the editor, with placeholder
											// shippingRates, so we can safely fallback to set the
											// first shipping rate as selected and ignore setting
											// an onChange prop.
											shippingRates[ 0 ]
												.shipping_rates[ 0 ].rate_id,
										] }
									/>
								) : (
									<ShippingRatesControl
										className="wc-block-cart__shipping-options"
										address={
											shippingCalculatorAddress.country
												? {
														city:
															shippingCalculatorAddress.city,
														state:
															shippingCalculatorAddress.state,
														postcode:
															shippingCalculatorAddress.postcode,
														country:
															shippingCalculatorAddress.country,
												  }
												: null
										}
										noResultsMessage={ __(
											'No shipping options were found.',
											'woo-gutenberg-products-block'
										) }
										selected={ selectedShippingRate }
										renderOption={
											renderShippingRatesControlOption
										}
										onChange={ (
											newSelectedShippingOption
										) =>
											setSelectedShippingRate(
												newSelectedShippingOption
											)
										}
									/>
								) }
							</fieldset>
						) }
						{ COUPONS_ENABLED && (
							<TotalsCouponCodeInput
								onSubmit={ applyCoupon }
								isLoading={ isApplyingCoupon }
							/>
						) }
						<TotalsItem
							className="wc-block-cart__totals-footer"
							currency={ totalsCurrency }
							label={ __(
								'Total',
								'woo-gutenberg-products-block'
							) }
							value={ parseInt( cartTotals.total_price, 10 ) }
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
