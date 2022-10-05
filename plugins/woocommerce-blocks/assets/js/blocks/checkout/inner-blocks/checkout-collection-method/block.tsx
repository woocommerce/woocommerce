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
import type { CartShippingPackageShippingRate } from '@woocommerce/type-defs/cart';

/**
 * Internal dependencies
 */
import './style.scss';
import {
	RatePrice,
	getLocalPickupStartingPrice,
	getShippingStartingPrice,
} from './shared';

const LocalPickupSelector = ( {
	checked,
	rate,
	showPrice,
	showIcon,
	toggleText,
}: {
	checked: string;
	rate: CartShippingPackageShippingRate;
	showPrice: boolean;
	showIcon: boolean;
	toggleText: string;
} ) => {
	return (
		<Radio
			value="pickup"
			className={ classnames( 'wc-block-checkout__collection-item', {
				'wc-block-checkout__collection-item--selected':
					checked === 'pickup',
			} ) }
		>
			{ showIcon === true && (
				<Icon
					icon={ store }
					size={ 28 }
					className="wc-block-checkout__collection-item-icon"
				/>
			) }
			<span className="wc-block-checkout__collection-item-title">
				{ toggleText }
			</span>
			{ showPrice === true && <RatePrice rate={ rate } /> }
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
	rate: CartShippingPackageShippingRate;
	showPrice: boolean;
	showIcon: boolean;
	toggleText: string;
} ) => {
	const Price =
		rate === undefined ? (
			<span className="wc-block-checkout__collection-item-price">
				{ __(
					'calculated with an address',
					'woo-gutenberg-products-block'
				) }
			</span>
		) : (
			<RatePrice rate={ rate } />
		);

	return (
		<Radio
			value="shipping"
			className={ classnames( 'wc-block-checkout__collection-item', {
				'wc-block-checkout__collection-item--selected':
					checked === 'shipping',
			} ) }
		>
			{ showIcon === true && (
				<Icon
					icon={ shipping }
					size={ 28 }
					className="wc-block-checkout__collection-item-icon"
				/>
			) }
			<span className="wc-block-checkout__collection-item-title">
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
	const { shippingRates, needsShipping, hasCalculatedShipping } =
		useShippingData();

	if ( ! needsShipping || ! hasCalculatedShipping ) {
		return null;
	}

	const localPickupStartingPrice = getLocalPickupStartingPrice(
		shippingRates[ 0 ]?.shipping_rates
	);
	const shippingStartingPrice = getShippingStartingPrice(
		shippingRates[ 0 ]?.shipping_rates
	);

	if ( ! localPickupStartingPrice && ! shippingStartingPrice ) {
		return null;
	}
	return (
		<RadioGroup
			id="collection-method"
			className="wc-block-checkout__collection-method-container"
			label="options"
			onChange={ onChange }
			checked={ checked }
		>
			<ShippingSelector
				checked={ checked }
				rate={ shippingStartingPrice }
				showPrice={ showPrice }
				showIcon={ showIcon }
				toggleText={ shippingText }
			/>
			<LocalPickupSelector
				checked={ checked }
				rate={ localPickupStartingPrice }
				showPrice={ showPrice }
				showIcon={ showIcon }
				toggleText={ localPickupText }
			/>
		</RadioGroup>
	);
};

export default Block;
