/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { useShippingData } from '@woocommerce/base-context/hooks';
import clsx from 'clsx';
import { Icon, store, shipping } from '@wordpress/icons';
import {
	useEffect,
	useLayoutEffect,
	useRef,
	useState,
} from '@wordpress/element';
import type { CSSProperties } from 'react';
import { cartStore, validationStore } from '@woocommerce/block-data';
import { useDispatch, useSelect } from '@wordpress/data';
import { isPackageRateCollectable } from '@woocommerce/base-utils';
import { onAnimationsFinished } from '@woocommerce/base-utils/on-animations-finished';
import { getSetting } from '@woocommerce/settings';
import { Button } from '@ariakit/react';
import { shippingAddressHasValidationErrors } from '@woocommerce/block-data/cart/utils';

/**
 * Internal dependencies
 */
import { RatePrice, getLocalPickupPrices, getShippingPrices } from './shared';
import type { minMaxPrices } from './shared';
import { defaultLocalPickupText, defaultShippingText } from './constants';

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
			render={ <div /> }
			role="radio"
			onClick={ onClick }
			aria-checked={ checked === 'pickup' }
			className={ clsx( 'wc-block-checkout__shipping-method-option', {
				'wc-block-checkout__shipping-method-option--selected':
					checked === 'pickup',
			} ) }
		>
			<span className="wc-block-checkout__shipping-method-option-title-wrapper">
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
		const rates = select( cartStore ).getShippingRates();
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
		useDispatch( validationStore );
	useEffect( () => {
		if ( checked === 'shipping' && ! hasShippingPrices ) {
			void setValidationErrors( {
				'shipping-rates-error': SHIPPING_RATE_ERROR,
			} );
		} else {
			void clearValidationError( 'shipping-rates-error' );
		}
		return () => clearValidationError( 'shipping-rates-error' );
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
			render={ <div /> }
			role="radio"
			onClick={ onClick }
			aria-checked={ checked === 'shipping' }
			className={ clsx( 'wc-block-checkout__shipping-method-option', {
				'wc-block-checkout__shipping-method-option--selected':
					checked === 'shipping',
			} ) }
		>
			<span className="wc-block-checkout__shipping-method-option-title-wrapper">
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
	const containerRef = useRef< HTMLDivElement >( null );
	const indicatorRef = useRef< HTMLSpanElement >( null );
	const previousChecked = useRef( checked );
	const [ indicatorStyle, setIndicatorStyle ] =
		useState< CSSProperties | null >( null );

	// Slide a copy of the selected style from the old option to the new one. It only exists while moving, so the
	// resting markup is unchanged; with no animation (reduced motion) it's removed before it paints.
	useLayoutEffect( () => {
		const from = previousChecked.current;
		previousChecked.current = checked;
		const container = containerRef.current;
		if ( ! container || from === checked ) {
			return;
		}
		// Shipping renders first, then pickup.
		const options = container.querySelectorAll(
			'.wc-block-checkout__shipping-method-option'
		);
		const fromRect =
			options[ from === 'pickup' ? 1 : 0 ]?.getBoundingClientRect();
		const toRect =
			options[ checked === 'pickup' ? 1 : 0 ]?.getBoundingClientRect();
		if ( ! fromRect || ! toRect ) {
			return;
		}
		const containerRect = container.getBoundingClientRect();
		const offsetX = fromRect.left - toRect.left;
		const offsetY = fromRect.top - toRect.top;
		setIndicatorStyle( {
			left: toRect.left - containerRect.left,
			top: toRect.top - containerRect.top,
			width: toRect.width,
			height: toRect.height,
			'--wc-shipping-method-indicator-offset': `${ offsetX }px ${ offsetY }px`,
			// 1.12 along the travel and 1 / 1.12 across it, so the area stays the same (rule 5).
			'--wc-shipping-method-indicator-stretch':
				Math.abs( offsetX ) >= Math.abs( offsetY )
					? '1.12 0.893'
					: '0.893 1.12',
		} as CSSProperties );
	}, [ checked ] );

	useLayoutEffect( () => {
		if ( ! indicatorStyle ) {
			return;
		}
		return onAnimationsFinished( indicatorRef.current, () =>
			setIndicatorStyle( null )
		);
	}, [ indicatorStyle ] );

	return (
		<div
			ref={ containerRef }
			id="shipping-method"
			// components-button-group is here for backwards compatibility, in case themes or plugins rely on it.
			className="components-button-group wc-block-checkout__shipping-method-container"
			role="radiogroup"
			aria-label={ __( 'Shipping method', 'woocommerce' ) }
		>
			{ indicatorStyle && (
				<span
					ref={ indicatorRef }
					aria-hidden="true"
					className="wc-block-checkout__shipping-method-indicator"
					style={ indicatorStyle }
				/>
			) }
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
