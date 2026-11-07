/**
 * External dependencies
 */
import { __, sprintf } from '@wordpress/i18n';
import clsx from 'clsx';
import { useEffect, useRef, useState } from '@wordpress/element';
import { select, subscribe } from '@wordpress/data';
import { cartStore } from '@woocommerce/block-data';
import { usePresenceList } from '@woocommerce/base-hooks/use-presence-list';
import LoadingMask from '@woocommerce/base-components/loading-mask';
import { RemovableChip, TotalsItem } from '@woocommerce/blocks-components';
import { applyCheckoutFilter } from '@woocommerce/blocks-checkout';
import { getSetting } from '@woocommerce/settings';
import type { RemovableChipProps } from '@woocommerce/blocks-components/chip/removable-chip';
import {
	CartResponseCouponItemWithLabel,
	CartTotalsItem,
	Currency,
	LooselyMustHave,
} from '@woocommerce/types';

/**
 * Internal dependencies
 */
import './style.scss';

export interface TotalsDiscountProps {
	cartCoupons: LooselyMustHave<
		CartResponseCouponItemWithLabel,
		'code' | 'label' | 'totals'
	>[];
	currency: Currency;
	isRemovingCoupon: boolean;
	removeCoupon: ( couponCode: string ) => void;
	values: LooselyMustHave<
		CartTotalsItem,
		'total_discount' | 'total_discount_tax'
	>;
	isLoading?: boolean;
}

const filteredCartCouponsFilterArg = {
	context: 'summary',
};

const toCouponKey = ( code: string ) => code.trim().toLowerCase();

// Codes the shopper has applied since the page loaded. Tracked from the store, not on mount, because the cart unmounts
// this component while it has no coupons, so a first coupon would otherwise look like one from before the page loaded.
const appliedCouponKeys = new Set< string >();
let pendingCouponKey = '';
subscribe( () => {
	const cart = select( cartStore );
	const couponCode = cart.getCouponBeingApplied();
	if ( couponCode ) {
		pendingCouponKey = toCouponKey( couponCode );
		appliedCouponKeys.add( pendingCouponKey );
		return;
	}
	// Once the request settles, forget a code that didn't make it into the cart, so it can't pulse later.
	if (
		pendingCouponKey &&
		! cart
			.getCartData()
			.coupons.some(
				( { code } ) => toCouponKey( code ) === pendingCouponKey
			)
	) {
		appliedCouponKeys.delete( pendingCouponKey );
	}
	pendingCouponKey = '';
}, cartStore );

// A chip pulses once, keeping the class while mounted, if the shopper just applied its code.
const CouponChip = ( {
	code,
	className,
	...props
}: RemovableChipProps & { code: string } ) => {
	const [ isNewlyApplied ] = useState( () =>
		appliedCouponKeys.has( toCouponKey( code ) )
	);
	useEffect( () => {
		appliedCouponKeys.delete( toCouponKey( code ) );
	} );
	return (
		<RemovableChip
			className={ clsx(
				className,
				isNewlyApplied && 'is-newly-applied'
			) }
			{ ...props }
		/>
	);
};

const TotalsDiscount = ( {
	cartCoupons = [],
	currency,
	isRemovingCoupon,
	removeCoupon,
	values,
	isLoading,
}: TotalsDiscountProps ): JSX.Element | null => {
	const {
		total_discount: totalDiscount,
		total_discount_tax: totalDiscountTax,
	} = values;
	const discountValue = parseInt( totalDiscount, 10 );

	const filteredCartCoupons = applyCheckoutFilter( {
		arg: filteredCartCouponsFilterArg,
		filterName: 'coupons',
		defaultValue: cartCoupons,
	} );

	// A removed chip stays where it was until it has animated out.
	const listRef = useRef< HTMLUListElement >( null );
	const displayedCoupons = usePresenceList(
		listRef,
		filteredCartCoupons,
		( { code } ) => code
	);

	if ( ! discountValue && displayedCoupons.length === 0 ) {
		return null;
	}

	const discountTaxValue = parseInt( totalDiscountTax, 10 );
	const discountTotalValue = getSetting(
		'displayCartPricesIncludingTax',
		false
	)
		? discountValue + discountTaxValue
		: discountValue;

	return (
		<TotalsItem
			className="wc-block-components-totals-discount"
			currency={ currency }
			description={
				displayedCoupons.length !== 0 && (
					<LoadingMask
						screenReaderLabel={ __(
							'Removing coupon…',
							'woocommerce'
						) }
						isLoading={ isRemovingCoupon }
						showSpinner={ false }
					>
						<ul
							className="wc-block-components-totals-discount__coupon-list"
							ref={ listRef }
						>
							{ displayedCoupons.map( ( displayed ) => {
								const cartCoupon = displayed.item;
								const isExiting =
									displayed.className === 'is-exiting';
								return (
									<CouponChip
										key={ 'coupon-' + cartCoupon.code }
										code={ cartCoupon.code }
										className={ clsx(
											'wc-block-components-totals-discount__coupon-list-item',
											{ 'is-exiting': isExiting }
										) }
										text={ cartCoupon.label }
										screenReaderText={ sprintf(
											/* translators: %s Coupon code. */
											__( 'Coupon: %s', 'woocommerce' ),
											cartCoupon.label
										) }
										disabled={
											isRemovingCoupon || isExiting
										}
										onRemove={ () => {
											removeCoupon( cartCoupon.code );
										} }
										radius="large"
										ariaLabel={ sprintf(
											/* translators: %s is a coupon code. */
											__(
												'Remove coupon "%s"',
												'woocommerce'
											),
											cartCoupon.label
										) }
									/>
								);
							} ) }
						</ul>
					</LoadingMask>
				)
			}
			label={
				!! discountTotalValue
					? __( 'Discount', 'woocommerce' )
					: __( 'Coupons', 'woocommerce' )
			}
			value={ discountTotalValue ? discountTotalValue * -1 : '-' }
			showSkeleton={ isLoading }
		/>
	);
};

export default TotalsDiscount;
