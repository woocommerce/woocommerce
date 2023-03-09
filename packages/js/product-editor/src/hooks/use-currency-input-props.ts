/**
 * External dependencies
 */
import { CurrencyContext } from '@woocommerce/currency';
import { useContext } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { useProductHelper } from './use-product-helper';

export type CurrencyInputProps = {
	prefix: string;
	className: string;
	sanitize: ( value: string | number ) => string;
	onFocus: ( event: React.FocusEvent< HTMLInputElement > ) => void;
	onKeyUp: ( event: React.KeyboardEvent< HTMLInputElement > ) => void;
};

export const useCurrencyInputProps = ( {
	value,
	setValue,
}: {
	value: string;
	setValue: ( value: string ) => void;
} ) => {
	const { sanitizePrice } = useProductHelper();

	const context = useContext( CurrencyContext );
	const { getCurrencyConfig } = context;
	const currencyConfig = getCurrencyConfig();

	const currencyInputProps: CurrencyInputProps = {
		prefix: currencyConfig.symbol,
		className: 'half-width-field components-currency-control',
		sanitize: ( val: string | number ) => {
			return sanitizePrice( String( val ) );
		},
		onFocus( event: React.FocusEvent< HTMLInputElement > ) {
			// In some browsers like safari .select() function inside
			// the onFocus event doesn't work as expected because it
			// conflicts with onClick the first time user click the
			// input. Using setTimeout defers the text selection and
			// avoid the unexpected behaviour.
			setTimeout(
				function deferSelection( element: HTMLInputElement ) {
					element.select();
				},
				0,
				event.currentTarget
			);
		},
		onKeyUp( event: React.KeyboardEvent< HTMLInputElement > ) {
			const amount = Number.parseFloat( sanitizePrice( value || '0' ) );
			const step = Number( event.currentTarget.step || '1' );
			if ( event.code === 'ArrowUp' ) {
				setValue( String( amount + step ) );
			}
			if ( event.code === 'ArrowDown' ) {
				setValue( String( amount - step ) );
			}
		},
	};
	return currencyInputProps;
};
