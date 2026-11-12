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
import type { CSSProperties, MouseEvent } from 'react';
import { cartStore, validationStore } from '@woocommerce/block-data';
import { useDispatch, useSelect } from '@wordpress/data';
import {
	isPackageRateCollectable,
	whenAnimationsEnd,
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
	onClick: ( event: MouseEvent ) => void;
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
	onClick: ( event: MouseEvent ) => void;
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
	/**
	 * Called when the shopper chooses an option. `direction` is the way that the selection slides on the screen: 1 to
	 * the right, -1 to the left. It is 0 when the change applies immediately, such as after a key press.
	 */
	onChange: ( value: string, direction: number ) => void;
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
	const [ indicator, setIndicator ] = useState< {
		key: number;
		isVertical: boolean;
		style: CSSProperties;
	} | null >( null );

	// The indicator only exists while it slides. Without motion, it goes before paint.
	useLayoutEffect(
		() =>
			indicator
				? whenAnimationsEnd( indicatorRef.current, () =>
						setIndicator( null )
					)
				: undefined,
		[ indicator ]
	);

	// The selected style slides from the old option to the new one, or from where it is if it still slides. A click
	// that a key press starts has no click count, and applies immediately.
	const select = ( value: string, event: MouseEvent ) => {
		if ( value === checked ) {
			onChange( value, 0 );
			return;
		}
		const container = containerRef.current;
		// Ship renders first, then Pickup.
		const options = container?.querySelectorAll(
			'.wc-block-checkout__shipping-method-option'
		);
		const to = options?.[ value === 'pickup' ? 1 : 0 ];
		const from =
			indicatorRef.current ?? options?.[ value === 'pickup' ? 0 : 1 ];
		if ( event.detail === 0 || ! container || ! to || ! from ) {
			setIndicator( null );
			onChange( value, 0 );
			return;
		}
		const box = container.getBoundingClientRect();
		const toRect = to.getBoundingClientRect();
		const fromRect = from.getBoundingClientRect();
		const x =
			fromRect.left + fromRect.width / 2 - toRect.left - toRect.width / 2;
		const y =
			fromRect.top + fromRect.height / 2 - toRect.top - toRect.height / 2;
		setIndicator( ( current ) => ( {
			key: ( current?.key ?? 0 ) + 1,
			isVertical: Math.abs( y ) > Math.abs( x ),
			style: {
				left: toRect.left - box.left - container.clientLeft,
				top: toRect.top - box.top - container.clientTop,
				width: toRect.width,
				height: toRect.height,
				'--wc-shipping-method-indicator-offset': `${ x }px ${ y }px`,
			} as CSSProperties,
		} ) );
		// The way the selection moves. When the options stack, Pickup counts as to the right of Ship.
		const stackedDirection = value === 'pickup' ? 1 : -1;
		onChange(
			value,
			Math.abs( x ) >= 1 ? Math.sign( -x ) : stackedDirection
		);
	};

	return (
		<div
			ref={ containerRef }
			id="shipping-method"
			// components-button-group is here for backwards compatibility, in case themes or plugins rely on it.
			className="components-button-group wc-block-checkout__shipping-method-container"
			role="radiogroup"
			aria-label={ __( 'Shipping method', 'woocommerce' ) }
		>
			<ShippingSelector
				checked={ checked }
				onClick={ ( event ) => select( 'shipping', event ) }
				rate={ getShippingPrices( shippingRates[ 0 ]?.shipping_rates ) }
				showPrice={ showPrice }
				showIcon={ showIcon }
				shippingCostRequiresAddress={ shippingCostRequiresAddress }
				toggleText={ shippingText || defaultShippingText }
			/>
			<LocalPickupSelector
				checked={ checked }
				onClick={ ( event ) => select( 'pickup', event ) }
				rate={ getLocalPickupPrices(
					shippingRates[ 0 ]?.shipping_rates
				) }
				multiple={ shippingRates.length > 1 }
				showPrice={ showPrice }
				showIcon={ showIcon }
				toggleText={ localPickupTextFromSettings }
			/>
			{ indicator && (
				<span
					key={ indicator.key }
					ref={ indicatorRef }
					aria-hidden="true"
					className={ clsx(
						'wc-block-checkout__shipping-method-indicator',
						{
							'wc-block-checkout__shipping-method-indicator--vertical':
								indicator.isVertical,
						}
					) }
					style={ indicator.style }
				/>
			) }
		</div>
	);
};

export default Block;
