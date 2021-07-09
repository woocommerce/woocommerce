/**
 * External dependencies
 */
import classnames from 'classnames';
import { __ } from '@wordpress/i18n';
import { useState } from '@wordpress/element';
import { useStoreCart } from '@woocommerce/base-context/hooks';
import { TotalsItem } from '@woocommerce/blocks-checkout';
import type { Currency } from '@woocommerce/price-format';
import type { ReactElement } from 'react';
import { getSetting, EnteredAddress } from '@woocommerce/settings';
import { ShippingVia } from '@woocommerce/base-components/cart-checkout/totals/shipping/shipping-via';

/**
 * Internal dependencies
 */
import ShippingRateSelector from './shipping-rate-selector';
import hasShippingRate from './has-shipping-rate';
import ShippingCalculator from '../../shipping-calculator';
import ShippingLocation from '../../shipping-location';
import './style.scss';

interface CalculatorButtonProps {
	label?: string;
	isShippingCalculatorOpen: boolean;
	setIsShippingCalculatorOpen: ( isShippingCalculatorOpen: boolean ) => void;
}

const CalculatorButton = ( {
	label = __( 'Calculate', 'woo-gutenberg-products-block' ),
	isShippingCalculatorOpen,
	setIsShippingCalculatorOpen,
}: CalculatorButtonProps ): ReactElement => {
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

interface ShippingAddressProps {
	showCalculator: boolean;
	isShippingCalculatorOpen: boolean;
	setIsShippingCalculatorOpen: CalculatorButtonProps[ 'setIsShippingCalculatorOpen' ];
	shippingAddress: EnteredAddress;
}

const ShippingAddress = ( {
	showCalculator,
	isShippingCalculatorOpen,
	setIsShippingCalculatorOpen,
	shippingAddress,
}: ShippingAddressProps ): ReactElement | null => {
	return (
		<>
			<ShippingLocation address={ shippingAddress } />
			{ showCalculator && (
				<CalculatorButton
					label={ __(
						'(change address)',
						'woo-gutenberg-products-block'
					) }
					isShippingCalculatorOpen={ isShippingCalculatorOpen }
					setIsShippingCalculatorOpen={ setIsShippingCalculatorOpen }
				/>
			) }
		</>
	);
};

interface NoShippingPlaceholderProps {
	showCalculator: boolean;
	isShippingCalculatorOpen: boolean;
	setIsShippingCalculatorOpen: CalculatorButtonProps[ 'setIsShippingCalculatorOpen' ];
}

const NoShippingPlaceholder = ( {
	showCalculator,
	isShippingCalculatorOpen,
	setIsShippingCalculatorOpen,
}: NoShippingPlaceholderProps ): ReactElement => {
	if ( ! showCalculator ) {
		return (
			<em>
				{ __(
					'Calculated during checkout',
					'woo-gutenberg-products-block'
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

interface TotalShippingProps {
	currency: Currency;
	values: {
		total_shipping: string;
		total_shipping_tax: string;
	}; // Values in use
	showCalculator?: boolean; //Whether to display the rate selector below the shipping total.
	showRateSelector?: boolean; // Whether to show shipping calculator or not.
	className?: string;
}

const TotalsShipping = ( {
	currency,
	values,
	showCalculator = true,
	showRateSelector = true,
	className,
}: TotalShippingProps ): ReactElement => {
	const [ isShippingCalculatorOpen, setIsShippingCalculatorOpen ] = useState(
		false
	);
	const {
		shippingAddress,
		cartHasCalculatedShipping,
		shippingRates,
		shippingRatesLoading,
	} = useStoreCart();

	const totalShippingValue = getSetting(
		'displayCartPricesIncludingTax',
		false
	)
		? parseInt( values.total_shipping, 10 ) +
		  parseInt( values.total_shipping_tax, 10 )
		: parseInt( values.total_shipping, 10 );
	const hasRates = hasShippingRate( shippingRates ) || totalShippingValue;
	const calculatorButtonProps = {
		isShippingCalculatorOpen,
		setIsShippingCalculatorOpen,
	};

	const selectedShippingRates = shippingRates.flatMap(
		( shippingPackage ) => {
			return shippingPackage.shipping_rates
				.filter( ( rate ) => rate.selected )
				.flatMap( ( rate ) => rate.name );
		}
	);

	return (
		<div
			className={ classnames(
				'wc-block-components-totals-shipping',
				className
			) }
		>
			<TotalsItem
				label={ __( 'Shipping', 'woo-gutenberg-products-block' ) }
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
							<>
								<ShippingVia
									selectedShippingRates={
										selectedShippingRates
									}
								/>
								<ShippingAddress
									shippingAddress={ shippingAddress }
									showCalculator={ showCalculator }
									{ ...calculatorButtonProps }
								/>
							</>
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

export default TotalsShipping;
