/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { DISPLAY_CART_PRICES_INCLUDING_TAX } from '@woocommerce/block-settings';
import {
	ShippingCalculator,
	ShippingLocation,
} from '@woocommerce/base-components/cart-checkout';
import PropTypes from 'prop-types';
import { useState } from '@wordpress/element';
import { useStoreCart } from '@woocommerce/base-hooks';

/**
 * Internal dependencies
 */
import TotalsItem from '../totals-item';
import ShippingRateSelector from './shipping-rate-selector';
import hasShippingRate from './has-shipping-rate';
import './style.scss';

/**
 * Renders the shipping totals row, rates, and calculator if enabled.
 */
const TotalsShippingItem = ( {
	currency,
	values,
	isCheckout = false,
	showCalculator = true,
	showRatesWithoutAddress = false,
} ) => {
	const [ isShippingCalculatorOpen, setIsShippingCalculatorOpen ] = useState(
		false
	);
	const {
		shippingRates,
		shippingRatesLoading,
		hasShippingAddress,
		shippingAddress,
	} = useStoreCart();
	const totalShippingValue = DISPLAY_CART_PRICES_INCLUDING_TAX
		? parseInt( values.total_shipping, 10 ) +
		  parseInt( values.total_shipping_tax, 10 )
		: parseInt( values.total_shipping, 10 );
	const hasRates = hasShippingRate( shippingRates ) || totalShippingValue;
	const showingRates = showRatesWithoutAddress || hasShippingAddress;

	// If we have no rates, and an address is needed.
	if ( ! hasRates && ! hasShippingAddress && ! isCheckout ) {
		return (
			<>
				<TotalsItem
					className="wc-block-components-totals-shipping"
					label={ __( 'Shipping', 'woocommerce' ) }
					value={
						showCalculator ? (
							<button
								className="wc-block-components-totals-shipping__change-address-button"
								onClick={ () => {
									setIsShippingCalculatorOpen(
										! isShippingCalculatorOpen
									);
								} }
							>
								{ __(
									'Calculate',
									'woocommerce'
								) }
							</button>
						) : (
							<em>
								{ __(
									'Calculated during checkout',
									'woocommerce'
								) }
							</em>
						)
					}
				/>
				{ showCalculator && isShippingCalculatorOpen && (
					<ShippingCalculator
						onUpdate={ () => {
							setIsShippingCalculatorOpen( false );
						} }
					/>
				) }
			</>
		);
	}

	return (
		<div className="wc-block-components-totals-shipping">
			<TotalsItem
				label={ __( 'Shipping', 'woocommerce' ) }
				value={ totalShippingValue ? totalShippingValue : '' }
				description={
					<>
						<ShippingLocation address={ shippingAddress } />{ ' ' }
						{ showCalculator && (
							<button
								className="wc-block-components-totals-shipping__change-address-button"
								onClick={ () => {
									setIsShippingCalculatorOpen(
										! isShippingCalculatorOpen
									);
								} }
								aria-expanded={ isShippingCalculatorOpen }
							>
								{ __(
									'(change address)',
									'woocommerce'
								) }
							</button>
						) }
					</>
				}
				currency={ currency }
			/>
			{ showCalculator && isShippingCalculatorOpen && (
				<ShippingCalculator
					onUpdate={ () => {
						setIsShippingCalculatorOpen( false );
					} }
				/>
			) }
			{ ! isCheckout && showingRates && (
				<ShippingRateSelector
					hasRates={ hasRates }
					shippingRates={ shippingRates }
					shippingRatesLoading={ shippingRatesLoading }
				/>
			) }
		</div>
	);
};

TotalsShippingItem.propTypes = {
	currency: PropTypes.object.isRequired,
	values: PropTypes.shape( {
		total_shipping: PropTypes.string,
		total_shipping_tax: PropTypes.string,
	} ).isRequired,
	isCheckout: PropTypes.bool,
	showCalculator: PropTypes.bool,
	showRatesWithoutAddress: PropTypes.bool,
};

export default TotalsShippingItem;
