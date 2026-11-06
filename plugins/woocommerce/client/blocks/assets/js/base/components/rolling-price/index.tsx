/**
 * External dependencies
 */
import clsx from 'clsx';
import { formatPrice } from '@woocommerce/price-format';
import type { Currency } from '@woocommerce/types';
import type { CSSProperties } from 'react';

/**
 * Internal dependencies
 */
import './style.scss';

const DIGITS = '0123456789'.split( '' );

export interface RollingPriceProps {
	// Price in the currency's smallest unit, as returned by the Store API.
	value: number | string;
	currency: Currency;
	className?: string;
	// Skip the roll, for example while the cart is still loading its first values.
	animate?: boolean;
}

/**
 * A price whose digits roll to their new values, like a counter, when the price changes. Meant for prices that
 * update in place without a loading skeleton, such as the total on the Place order button.
 *
 * CSS can't reach individual digits in text, so each digit renders as a column of 0–9, positioned by
 * `--wc-rolling-price-digit`. The roll itself is a plain CSS transition on that column.
 */
export const RollingPrice = ( {
	value,
	currency,
	className,
	animate = true,
}: RollingPriceProps ) => {
	const formatted = formatPrice( value, currency );
	// Each digit gets a column; the characters between digits stay together, so a symbol such as ريال still joins.
	const parts = formatted
		.split( '' )
		.reduce< string[] >( ( result, character ) => {
			const last = result[ result.length - 1 ];
			if (
				last !== undefined &&
				! DIGITS.includes( character ) &&
				! DIGITS.includes( last )
			) {
				result[ result.length - 1 ] = last + character;
			} else {
				result.push( character );
			}
			return result;
		}, [] );

	return (
		<span
			className={ clsx(
				'wc-block-formatted-money-amount',
				'wc-block-components-formatted-money-amount',
				'wc-block-components-rolling-price',
				{ 'is-static': ! animate },
				className
			) }
		>
			<span className="screen-reader-text">{ formatted }</span>
			{ /* Left to right on every page, as `FormattedMonetaryAmount` lays out a price. Set in markup, because the RTL stylesheet would flip a CSS `direction`. */ }
			<span
				className="wc-block-components-rolling-price__characters"
				aria-hidden="true"
				dir="ltr"
			>
				{ parts.map( ( part, index ) => {
					// Key by position from the right, so cents stay in the same column when the price gains a digit.
					const fromRight = parts.length - index;
					if ( ! DIGITS.includes( part ) ) {
						return (
							<span key={ `${ fromRight }-${ part }` }>
								{ part }
							</span>
						);
					}
					return (
						<span
							key={ `${ fromRight }-digit` }
							className="wc-block-components-rolling-price__digit"
						>
							<span
								className="wc-block-components-rolling-price__strip"
								style={
									{
										'--wc-rolling-price-digit': part,
									} as CSSProperties
								}
							>
								{ DIGITS.map( ( digit ) => (
									<span key={ digit }>{ digit }</span>
								) ) }
							</span>
						</span>
					);
				} ) }
			</span>
		</span>
	);
};

export default RollingPrice;
