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
import { useReducedMotion } from '@wordpress/compose';
import {
	isPackageRateCollectable,
	onAnimationsFinished,
} from '@woocommerce/base-utils';
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
	const isReducedMotion = useReducedMotion();
	const [ indicatorStyle, setIndicatorStyle ] =
		useState< CSSProperties | null >( null );

	// When the selection changes, slide a copy of the selected style from the old option to the new one.
	// It only exists while moving, so the resting markup and styles are unchanged.
	useLayoutEffect( () => {
		const from = previousChecked.current;
		previousChecked.current = checked;
		const container = containerRef.current;
		if ( ! container || from === checked || isReducedMotion ) {
			return;
		}
		// Options render in a fixed order: shipping, then pickup.
		const options = container.querySelectorAll< HTMLElement >(
			':scope > .wc-block-checkout__shipping-method-option'
		);
		const fromOption = options[ from === 'pickup' ? 1 : 0 ];
		const toOption = options[ checked === 'pickup' ? 1 : 0 ];
		if ( ! fromOption || ! toOption ) {
			return;
		}
		const fromRect = fromOption.getBoundingClientRect();
		const toRect = toOption.getBoundingClientRect();
		const containerRect = container.getBoundingClientRect();
		const offsetX = fromRect.left - toRect.left;
		const offsetY = fromRect.top - toRect.top;
		const isHorizontal = Math.abs( offsetX ) >= Math.abs( offsetY );
		setIndicatorStyle( {
			left: toRect.left - containerRect.left,
			top: toRect.top - containerRect.top,
			width: toRect.width,
			height: toRect.height,
			'--wc-shipping-method-indicator-offset': `${ offsetX }px, ${ offsetY }px`,
			'--wc-shipping-method-indicator-stretch': isHorizontal
				? 'var(--wc-stretch-along), var(--wc-stretch-across)'
				: 'var(--wc-stretch-across), var(--wc-stretch-along)',
		} as CSSProperties );
	}, [ checked, isReducedMotion ] );

	// Remove the indicator once its slide has finished.
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
			className={ clsx(
				'components-button-group wc-block-checkout__shipping-method-container',
				{ 'is-moving': indicatorStyle !== null }
			) }
			role="radiogroup"
			aria-label={ __( 'Shipping method', 'woocommerce' ) }
		>
			{ indicatorStyle && (
				<span
					ref={ indicatorRef }
					aria-hidden="true"
					className="wc-block-checkout__shipping-method-indicator"
					style={ indicatorStyle }
				>
					<span className="wc-block-checkout__shipping-method-indicator-pill" />
				</span>
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
