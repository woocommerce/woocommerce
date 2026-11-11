/**
 * External dependencies
 */
import {
	Fragment,
	useLayoutEffect,
	useRef,
	useState,
} from '@wordpress/element';
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
	'renderAmount' | 'renderText' | 'displayType'
> {
	/**
	 * Set to `false` while the value is not final, such as while the cart loads for the first time. A change that
	 * happens while this is `false` does not roll.
	 */
	canRoll?: boolean;
}

const COLUMN_SELECTOR = '.wc-block-components-rolling-price__column';

/**
 * The amount of a price, without its sign and currency symbol. At rest it is plain text. When it changes and
 * `rolls` is set, each digit rolls from the old digit to the new one (Price roll rule). Assistive technology reads
 * the plain amount while the digits roll, and the plain text returns when the roll ends.
 */
const RollingAmount = ( {
	amount,
	rolls,
}: {
	amount: string;
	rolls: boolean;
} ): JSX.Element => {
	const ref = useRef< HTMLSpanElement | null >( null );
	const [ previousAmount, setPreviousAmount ] = useState( amount );
	// The amount that the current roll started from, or `null` at rest.
	const [ rollFrom, setRollFrom ] = useState< string | null >( null );

	// Adjust state while rendering, so the old amount never shows for a frame after the change.
	if ( amount !== previousAmount ) {
		setPreviousAmount( amount );
		setRollFrom( rolls ? previousAmount : null );
	}

	// A layout effect runs after the new digits are in the DOM, so their transitions have started, and before paint,
	// so a roll that does not animate (reduced motion, Jest) never shows its columns.
	useLayoutEffect( () => {
		if ( rollFrom === null ) {
			return;
		}
		const columns = Array.from(
			ref.current?.querySelectorAll( COLUMN_SELECTOR ) ?? []
		);
		let pendingColumns = columns.length;
		if ( pendingColumns === 0 ) {
			setRollFrom( null );
			return;
		}
		// Each column only animates if its digit changed, so wait for all of them.
		const cancels = columns.map( ( column ) =>
			whenAnimationsEnd( column, () => {
				pendingColumns--;
				if ( pendingColumns === 0 ) {
					setRollFrom( null );
				}
			} )
		);
		return () => cancels.forEach( ( cancel ) => cancel() );
	}, [ rollFrom, amount ] );

	if ( rollFrom === null ) {
		return <>{ amount }</>;
	}

	return (
		<>
			<span className="screen-reader-text">{ amount }</span>
			{ /* Digits always run left to right, also inside a right-to-left price, so the roll is isolated as LTR. */ }
			<span
				ref={ ref }
				className="wc-block-components-rolling-price"
				aria-hidden="true"
				dir="ltr"
			>
				{ getRollingCharacters( rollFrom, amount ).map(
					( { key, character, digit, fromDigit } ) =>
						digit === null ? (
							<Fragment key={ key }>{ character }</Fragment>
						) : (
							<span
								key={ key }
								className="wc-block-components-rolling-price__digit"
							>
								{ character }
								<span
									className="wc-block-components-rolling-price__column"
									style={
										{
											'--wc-roll-from': fromDigit,
											'--wc-roll-to': digit,
										} as React.CSSProperties
									}
								>
									{ Array.from( DIGITS, ( columnDigit ) => (
										<span key={ columnDigit }>
											{ columnDigit }
										</span>
									) ) }
								</span>
							</span>
						)
				) }
			</span>
		</>
	);
};

/**
 * A `FormattedMonetaryAmount` whose digits roll when the price changes in place (Price roll rule). At rest its
 * markup is the same as `FormattedMonetaryAmount`.
 */
export const RollingPrice = ( {
	canRoll = true,
	...props
}: RollingPriceProps ): JSX.Element => {
	const value = String( props.value );
	const [ shown, setShown ] = useState( { value, rolls: false } );

	// Price roll rule: prices do not roll while the cart loads for the first time, so a change rolls only if
	// `canRoll` is set when it happens.
	if ( value !== shown.value ) {
		setShown( { value, rolls: canRoll } );
	}

	return (
		<FormattedMonetaryAmount
			{ ...props }
			renderAmount={ ( amount ) => (
				<RollingAmount amount={ amount } rolls={ shown.rolls } />
			) }
		/>
	);
};

export default RollingPrice;
