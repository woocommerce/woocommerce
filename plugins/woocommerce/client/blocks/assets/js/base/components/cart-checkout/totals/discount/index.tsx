/**
 * External dependencies
 */
import { __, sprintf } from '@wordpress/i18n';
import clsx from 'clsx';
import { useEffect, useRef } from '@wordpress/element';
import { select, subscribe } from '@wordpress/data';
import { cartStore } from '@woocommerce/block-data';
import LoadingMask from '@woocommerce/base-components/loading-mask';
import { RemovableChip, TotalsItem } from '@woocommerce/blocks-components';
import { applyCheckoutFilter } from '@woocommerce/blocks-checkout';
import { getSetting } from '@woocommerce/settings';
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

// Codes the shopper has applied since the page loaded; their chips pulse once when they appear. This is tracked
// from the store rather than on mount because the cart unmounts this component while it has no coupons, so a
// first coupon would otherwise look like one that was applied before the page loaded.
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
	// Once the request settles, forget a code that didn't make it into the cart, so a rejected code can't
	// pulse later.
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

	// A chip pulses when it first appears for a code the shopper just applied, and keeps the class while mounted so
	// re-renders don't cut the animation short. Existing chips never pulse, even if the same code is re-submitted.
	const renderedCouponCodes = useRef< string[] >( [] );
	const pulsingCouponCodes = useRef< string[] >( [] );
	const couponCodes = filteredCartCoupons.map( ( { code } ) => code );
	const newlyAppliedCouponCodes = couponCodes.filter(
		( code ) =>
			pulsingCouponCodes.current.includes( code ) ||
			( ! renderedCouponCodes.current.includes( code ) &&
				appliedCouponKeys.has( toCouponKey( code ) ) )
	);

	useEffect( () => {
		couponCodes.forEach( ( code ) =>
			appliedCouponKeys.delete( toCouponKey( code ) )
		);
		pulsingCouponCodes.current = newlyAppliedCouponCodes;
		renderedCouponCodes.current = couponCodes;
	} );

	if ( ! discountValue && filteredCartCoupons.length === 0 ) {
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
				filteredCartCoupons.length !== 0 && (
					<LoadingMask
						screenReaderLabel={ __(
							'Removing coupon…',
							'woocommerce'
						) }
						isLoading={ isRemovingCoupon }
						showSpinner={ false }
					>
						<ul className="wc-block-components-totals-discount__coupon-list">
							{ filteredCartCoupons.map( ( cartCoupon ) => {
								return (
									<RemovableChip
										key={ 'coupon-' + cartCoupon.code }
										className={ clsx(
											'wc-block-components-totals-discount__coupon-list-item',
											{
												'is-newly-applied':
													newlyAppliedCouponCodes.includes(
														cartCoupon.code
													),
											}
										) }
										text={ cartCoupon.label }
										screenReaderText={ sprintf(
											/* translators: %s Coupon code. */
											__( 'Coupon: %s', 'woocommerce' ),
											cartCoupon.label
										) }
										disabled={ isRemovingCoupon }
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
