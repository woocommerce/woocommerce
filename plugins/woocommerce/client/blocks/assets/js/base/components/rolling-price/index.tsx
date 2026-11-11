/**
 * External dependencies
 */
import { useLayoutEffect, useRef, useState } from '@wordpress/element';
import { FormattedMonetaryAmount } from '@woocommerce/blocks-components';
import type { FormattedMonetaryAmountProps } from '@woocommerce/blocks-components/formatted-monetary-amount';
import { whenAnimationsEnd } from '@woocommerce/base-utils';

/**
 * Internal dependencies
 */
import { DIGITS, getRollingCharacters } from './utils';
import './style.scss';

export interface RollingPriceProps extends Omit<
	FormattedMonetaryAmountProps,
	'renderText' | 'displayType' | 'getInputRef'
> {
	/**
	 * Set to `false` while the value is not final, such as while the cart loads for the first time. A change that
	 * happens while this is `false` does not roll.
	 */
	canRoll?: boolean;
}

/**
 * A `FormattedMonetaryAmount` whose digits roll when the price changes in place (Price roll rule). At rest it is a
 * plain `FormattedMonetaryAmount`. Only while the digits roll does it use `renderText` to draw them, so the price is
 * formatted in one place.
 */
export const RollingPrice = ( {
	canRoll = true,
	...props
}: RollingPriceProps ): JSX.Element => {
	const value = String( props.value );
	const priceRef = useRef< HTMLSpanElement | null >( null );
	// The value and the formatted text that showed at rest, to roll from.
	const previous = useRef( { value, text: '' } );
	const [ rollFrom, setRollFrom ] = useState< string | null >( null );

	// Before paint: start a roll when the value changes at rest, and end it when the digits stop moving. A roll that
	// does not animate, as with reduced motion or in Jest, ends here and never shows.
	useLayoutEffect( () => {
		const last = previous.current;
		if ( rollFrom === null ) {
			previous.current = {
				value,
				text: priceRef.current?.textContent ?? '',
			};
			if ( canRoll && value !== last.value && last.text ) {
				setRollFrom( last.text );
			}
			return;
		}
		previous.current = { value, text: '' };
		const columns = Array.from(
			priceRef.current?.querySelectorAll( '[data-roll-to]' ) ?? []
		);
		let pending = columns.length;
		if ( pending === 0 ) {
			setRollFrom( null );
			return;
		}
		const cancels = columns.map( ( column ) =>
			whenAnimationsEnd( column, () => {
				pending--;
				if ( pending === 0 ) {
					setRollFrom( null );
				}
			} )
		);
		return () => cancels.forEach( ( cancel ) => cancel() );
	} );

	if ( rollFrom === null ) {
		return (
			<FormattedMonetaryAmount { ...props } getInputRef={ priceRef } />
		);
	}

	return (
		<FormattedMonetaryAmount
			{ ...props }
			renderText={ ( formattedValue, spanProps ) => (
				<span
					{ ...( spanProps as React.HTMLAttributes< HTMLSpanElement > ) }
					ref={ priceRef }
				>
					<span className="screen-reader-text">
						{ formattedValue }
					</span>
					{ /* Digits run left to right, as in a plain price, also on a right-to-left page. */ }
					<span
						className="wc-block-components-rolling-price"
						aria-hidden="true"
						dir="ltr"
					>
						{ getRollingCharacters( rollFrom, formattedValue ).map(
							( { key, character, digit, fromDigit } ) =>
								digit === null ? (
									character
								) : (
									<span
										key={ key }
										className="wc-block-components-rolling-price__digit"
									>
										{ character }
										<span
											className="wc-block-components-rolling-price__column"
											data-roll-to={ digit }
											style={
												{
													'--wc-roll-from': fromDigit,
													'--wc-roll-to': digit,
												} as React.CSSProperties
											}
										>
											{ Array.from(
												DIGITS,
												( column ) => (
													<span key={ column }>
														{ column }
													</span>
												)
											) }
										</span>
									</span>
								)
						) }
					</span>
				</span>
			) }
		/>
	);
};

export default RollingPrice;
