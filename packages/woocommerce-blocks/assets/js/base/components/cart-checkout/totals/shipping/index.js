/**
 * External dependencies
 */
import classnames from 'classnames';
import { __ } from '@wordpress/i18n';
import { DISPLAY_CART_PRICES_INCLUDING_TAX } from '@woocommerce/block-settings';
import PropTypes from 'prop-types';
import { useState } from '@wordpress/element';
import { useStoreCart } from '@woocommerce/base-hooks';
import {
	ShippingCalculator,
	ShippingLocation,
} from '@woocommerce/base-components/cart-checkout';
import { TotalsItem } from '@woocommerce/blocks-checkout';

/**
 * Internal dependencies
 */
import ShippingRateSelector from './shipping-rate-selector';
import hasShippingRate from './has-shipping-rate';
import './style.scss';

/** @typedef {import('react')} React */

/**
 * Renders the shipping totals row, rates, and calculator if enabled.
 *
 * @param {Object} props Incoming props for the component.
 * @param {Object} props.currency Currency information.
 * @param {Object} props.values Values in use.
 * @param {boolean} props.showRateSelector Whether to display the rate selector below the shipping total.
 * @param {boolean} props.showCalculator Whether to show shipping calculator or not.
 * @param {string} props.className CSS Class supplied by consumer.
 */
const TotalsShipping = ( {
	currency,
	values,
	showCalculator = true,
	showRateSelector = true,
	className,
} ) => {
	const [ isShippingCalculatorOpen, setIsShippingCalculatorOpen ] = useState(
		false
	);
	const {
		shippingAddress,
		cartHasCalculatedShipping,
		shippingRates,
		shippingRatesLoading,
	} = useStoreCart();

	const totalShippingValue = DISPLAY_CART_PRICES_INCLUDING_TAX
		? parseInt( values.total_shipping, 10 ) +
		  parseInt( values.total_shipping_tax, 10 )
		: parseInt( values.total_shipping, 10 );
	const hasRates = hasShippingRate( shippingRates ) || totalShippingValue;
	const calculatorButtonProps = {
		isShippingCalculatorOpen,
		setIsShippingCalculatorOpen,
	};

	return (
		<div
			className={ classnames(
				'wc-block-components-totals-shipping',
				className
			) }
		>
			<TotalsItem
				label={ __( 'Shipping', 'woocommerce' ) }
				value={
					cartHasCalculatedShipping ? (
						totalShippingValue
					) : (
						<NoShippingPlaceholder
							showCalculator={ showCalculator }
							{ ...calculatorButtonProps }
						/>
					)
				}
				description={
					<>
						{ cartHasCalculatedShipping && (
							<ShippingAddress
								shippingAddress={ shippingAddress }
								showCalculator={ showCalculator }
								{ ...calculatorButtonProps }
							/>
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
			{ showRateSelector && cartHasCalculatedShipping && (
				<ShippingRateSelector
					hasRates={ hasRates }
					shippingRates={ shippingRates }
					shippingRatesLoading={ shippingRatesLoading }
				/>
			) }
		</div>
	);
};

const ShippingAddress = ( {
	showCalculator,
	isShippingCalculatorOpen,
	setIsShippingCalculatorOpen,
	shippingAddress,
} ) => {
	return (
		<>
			<ShippingLocation address={ shippingAddress } />
			{ showCalculator && (
				<CalculatorButton
					label={ __(
						'(change address)',
						'woocommerce'
					) }
					isShippingCalculatorOpen={ isShippingCalculatorOpen }
					setIsShippingCalculatorOpen={ setIsShippingCalculatorOpen }
				/>
			) }
		</>
	);
};

const NoShippingPlaceholder = ( {
	showCalculator,
	isShippingCalculatorOpen,
	setIsShippingCalculatorOpen,
} ) => {
	if ( ! showCalculator ) {
		return (
			<em>
				{ __(
					'Calculated during checkout',
					'woocommerce'
				) }
			</em>
		);
	}

	return (
		<CalculatorButton
			isShippingCalculatorOpen={ isShippingCalculatorOpen }
			setIsShippingCalculatorOpen={ setIsShippingCalculatorOpen }
		/>
	);
};

const CalculatorButton = ( {
	label = __( 'Calculate', 'woocommerce' ),
	isShippingCalculatorOpen,
	setIsShippingCalculatorOpen,
} ) => {
	return (
		<button
			className="wc-block-components-totals-shipping__change-address-button"
			onClick={ () => {
				setIsShippingCalculatorOpen( ! isShippingCalculatorOpen );
			} }
			aria-expanded={ isShippingCalculatorOpen }
		>
			{ label }
		</button>
	);
};

TotalsShipping.propTypes = {
	currency: PropTypes.object.isRequired,
	values: PropTypes.shape( {
		total_shipping: PropTypes.string,
		total_shipping_tax: PropTypes.string,
	} ).isRequired,
	showRateSelector: PropTypes.bool,
	showCalculator: PropTypes.bool,
	className: PropTypes.string,
};

export default TotalsShipping;
