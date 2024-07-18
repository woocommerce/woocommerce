/**
 * External dependencies
 */
import { CurrencyContext } from '@woocommerce/currency';
import { useContext } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { useProductHelper } from './use-product-helper';
import { deferSelectInFocus, formatCurrencyDisplayValue } from '../utils';

export type CurrencyInputProps = {
	prefix: string;
	className: string;
	value: string;
	sanitize: ( value: string | number ) => string;
	onChange: ( value: string ) => void;
	onFocus: ( event: React.FocusEvent< HTMLInputElement > ) => void;
	onKeyUp: ( event: React.KeyboardEvent< HTMLInputElement > ) => void;
};

type Props = {
	value: string;
	onChange: ( value: string ) => void;
	onFocus?: ( event: React.FocusEvent< HTMLInputElement > ) => void;
	onKeyUp?: ( event: React.KeyboardEvent< HTMLInputElement > ) => void;
};

const CURRENCY_INPUT_MAX = 1_000_000_000_000_000_000.0;

export const useCurrencyInputProps = ( {
	value,
	onChange,
	onFocus,
	onKeyUp,
}: Props ) => {
	const { sanitizePrice } = useProductHelper();

	const context = useContext( CurrencyContext );
	const { getCurrencyConfig, formatAmount } = context;
	const currencyConfig = getCurrencyConfig();

	const currencyInputProps: CurrencyInputProps = {
		prefix: currencyConfig.symbol,
		className: 'components-currency-control',
		value: formatCurrencyDisplayValue(
			String( value ),
			currencyConfig,
			formatAmount
		),
		sanitize: ( val: string | number ) => {
			return sanitizePrice( String( val ) );
		},
		onFocus( event: React.FocusEvent< HTMLInputElement > ) {
			deferSelectInFocus( event.currentTarget );
			if ( onFocus ) {
				onFocus( event );
			}
		},
		onKeyUp( event: React.KeyboardEvent< HTMLInputElement > ) {
			const amount = Number.parseFloat( sanitizePrice( value || '0' ) );
			const step = Number( event.currentTarget.step || '1' );
			if ( event.code === 'ArrowUp' ) {
				onChange( String( amount + step ) );
			}
			if ( event.code === 'ArrowDown' ) {
				onChange( String( amount - step ) );
			}
			if ( onKeyUp ) {
				onKeyUp( event );
			}
		},
		onChange( newValue: string ) {
			const sanitizeValue = sanitizePrice( newValue );
			if ( onChange ) {
				onChange(
					Number( sanitizeValue ) <= CURRENCY_INPUT_MAX
						? sanitizeValue
						: String( CURRENCY_INPUT_MAX )
				);
			}
		},
	};
	return currencyInputProps;
};
