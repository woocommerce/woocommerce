/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { useShippingData } from '@woocommerce/base-context/hooks';
import {
	__experimentalRadio as Radio,
	__experimentalRadioGroup as RadioGroup,
} from 'wordpress-components';
import classnames from 'classnames';
import { Icon, store, shipping } from '@wordpress/icons';

/**
 * Internal dependencies
 */
import './style.scss';
import { RatePrice, getLocalPickupPrices, getShippingPrices } from './shared';
import type { minMaxPrices } from './shared';
import { defaultLocalPickupText, defaultShippingText } from './constants';

const LocalPickupSelector = ( {
	checked,
	rate,
	showPrice,
	showIcon,
	toggleText,
	multiple,
}: {
	checked: string;
	rate: minMaxPrices;
	showPrice: boolean;
	showIcon: boolean;
	toggleText: string;
	multiple: boolean;
} ) => {
	return (
		<Radio
			value="pickup"
			className={ classnames(
				'wc-block-checkout__shipping-method-option',
				{
					'wc-block-checkout__shipping-method-option--selected':
						checked === 'pickup',
				}
			) }
		>
			{ showIcon === true && (
				<Icon
					icon={ store }
					size={ 28 }
					className="wc-block-checkout__shipping-method-option-icon"
				/>
			) }
			<span className="wc-block-checkout__shipping-method-option-title">
				{ toggleText }
			</span>
			{ showPrice === true && (
				<RatePrice
					multiple={ multiple }
					minRate={ rate.min }
					maxRate={ rate.max }
				/>
			) }
		</Radio>
	);
};

const ShippingSelector = ( {
	checked,
	rate,
	showPrice,
	showIcon,
	toggleText,
}: {
	checked: string;
	rate: minMaxPrices;
	showPrice: boolean;
	showIcon: boolean;
	toggleText: string;
} ) => {
	const Price =
		rate.min === undefined ? (
			<span className="wc-block-checkout__shipping-method-option-price">
				{ __(
					'calculated with an address',
					'woo-gutenberg-products-block'
				) }
			</span>
		) : (
			<RatePrice minRate={ rate.min } maxRate={ rate.max } />
		);

	return (
		<Radio
			value="shipping"
			className={ classnames(
				'wc-block-checkout__shipping-method-option',
				{
					'wc-block-checkout__shipping-method-option--selected':
						checked === 'shipping',
				}
			) }
		>
			{ showIcon === true && (
				<Icon
					icon={ shipping }
					size={ 28 }
					className="wc-block-checkout__shipping-method-option-icon"
				/>
			) }
			<span className="wc-block-checkout__shipping-method-option-title">
				{ toggleText }
			</span>
			{ showPrice === true && Price }
		</Radio>
	);
};
const Block = ( {
	checked,
	onChange,
	showPrice,
	showIcon,
	localPickupText,
	shippingText,
}: {
	checked: string;
	onChange: ( value: string ) => void;
	showPrice: boolean;
	showIcon: boolean;
	localPickupText: string;
	shippingText: string;
} ): JSX.Element | null => {
	const { shippingRates } = useShippingData();

	return (
		<RadioGroup
			id="shipping-method"
			className="wc-block-checkout__shipping-method-container"
			label="options"
			onChange={ onChange }
			checked={ checked }
		>
			<ShippingSelector
				checked={ checked }
				rate={ getShippingPrices( shippingRates[ 0 ]?.shipping_rates ) }
				showPrice={ showPrice }
				showIcon={ showIcon }
				toggleText={ shippingText || defaultShippingText }
			/>
			<LocalPickupSelector
				checked={ checked }
				rate={ getLocalPickupPrices(
					shippingRates[ 0 ]?.shipping_rates
				) }
				multiple={ shippingRates.length > 1 }
				showPrice={ showPrice }
				showIcon={ showIcon }
				toggleText={ localPickupText || defaultLocalPickupText }
			/>
		</RadioGroup>
	);
};

export default Block;
