/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { useShippingData } from '@woocommerce/base-context/hooks';
import classnames from 'classnames';
import { Icon, store, shipping } from '@wordpress/icons';
import { useEffect } from '@wordpress/element';
import { CART_STORE_KEY, VALIDATION_STORE_KEY } from '@woocommerce/block-data';
import { useDispatch, useSelect } from '@wordpress/data';
import { isPackageRateCollectable } from '@woocommerce/base-utils';
import { getSetting } from '@woocommerce/settings';

/**
 * Internal dependencies
 */
import { RatePrice, getLocalPickupPrices, getShippingPrices } from './shared';
import type { minMaxPrices } from './shared';
import { defaultLocalPickupText, defaultShippingText } from './constants';
import { shippingAddressHasValidationErrors } from '../../../../data/cart/utils';
import Button from '../../../../base/components/button';

const SHIPPING_RATE_ERROR = {
	hidden: true,
	message: __( 'Shipping options are not available', 'woocommerce' ),
};

const LocalPickupSelector = ( {
	checked,
	rate,
	showPrice,
	showIcon,
	toggleText,
	multiple,
	onClick,
}: {
	checked: string;
	rate: minMaxPrices;
	showPrice: boolean;
	showIcon: boolean;
	toggleText: string;
	multiple: boolean;
	onClick: () => void;
} ) => {
	return (
		<Button
			role="radio"
			removeTextWrap
			onClick={ onClick }
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
		</Button>
	);
};

const ShippingSelector = ( {
	checked,
	rate,
	showPrice,
	showIcon,
	toggleText,
	onClick,
	shippingCostRequiresAddress = false,
}: {
	checked: string;
	rate: minMaxPrices;
	showPrice: boolean;
	showIcon: boolean;
	shippingCostRequiresAddress: boolean;
	onClick: () => void;
	toggleText: string;
} ) => {
	const hasShippableRates = useSelect( ( select ) => {
		const rates = select( CART_STORE_KEY ).getShippingRates();
		return rates.some(
			( { shipping_rates: shippingRate } ) =>
				! shippingRate.every( isPackageRateCollectable )
		);
	} );
	const rateShouldBeHidden =
		shippingCostRequiresAddress &&
		shippingAddressHasValidationErrors() &&
		! hasShippableRates;
	const hasShippingPrices = rate.min !== undefined && rate.max !== undefined;
	const { setValidationErrors, clearValidationError } =
		useDispatch( VALIDATION_STORE_KEY );
	useEffect( () => {
		if ( checked === 'shipping' && ! hasShippingPrices ) {
			setValidationErrors( {
				'shipping-rates-error': SHIPPING_RATE_ERROR,
			} );
		} else {
			clearValidationError( 'shipping-rates-error' );
		}
	}, [
		checked,
		clearValidationError,
		hasShippingPrices,
		setValidationErrors,
	] );
	const Price =
		rate.min === undefined || rateShouldBeHidden ? (
			<span className="wc-block-checkout__shipping-method-option-price">
				{ __( 'calculated with an address', 'woocommerce' ) }
			</span>
		) : (
			<RatePrice minRate={ rate.min } maxRate={ rate.max } />
		);

	return (
		<Button
			role="radio"
			onClick={ onClick }
			removeTextWrap
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
		</Button>
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
	const shippingCostRequiresAddress = getSetting< boolean >(
		'shippingCostRequiresAddress',
		false
	);
	const localPickupTextFromSettings = getSetting< string >(
		'localPickupText',
		localPickupText || defaultLocalPickupText
	);

	return (
		<div
			id="shipping-method"
			// components-button-group is here for backwards compatibility, in case themes or plugins rely on it.
			className="components-button-group wc-block-checkout__shipping-method-container"
			role="radiogroup"
		>
			<ShippingSelector
				checked={ checked }
				onClick={ () => {
					onChange( 'shipping' );
				} }
				rate={ getShippingPrices( shippingRates[ 0 ]?.shipping_rates ) }
				showPrice={ showPrice }
				showIcon={ showIcon }
				shippingCostRequiresAddress={ shippingCostRequiresAddress }
				toggleText={ shippingText || defaultShippingText }
			/>
			<LocalPickupSelector
				checked={ checked }
				onClick={ () => {
					onChange( 'pickup' );
				} }
				rate={ getLocalPickupPrices(
					shippingRates[ 0 ]?.shipping_rates
				) }
				multiple={ shippingRates.length > 1 }
				showPrice={ showPrice }
				showIcon={ showIcon }
				toggleText={ localPickupTextFromSettings }
			/>
		</div>
	);
};

export default Block;
