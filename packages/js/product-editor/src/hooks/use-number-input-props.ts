/**
 * Internal dependencies
 */
import { useProductHelper } from './use-product-helper';
import { deferSelectInFocus } from '../utils';

export type NumberInputProps = {
	value: string;
	onChange: ( value: string ) => void;
	onFocus: ( event: React.FocusEvent< HTMLInputElement > ) => void;
	onKeyDown: ( event: React.KeyboardEvent< HTMLInputElement > ) => void;
	onKeyUp: ( event: React.KeyboardEvent< HTMLInputElement > ) => void;
	inputMode: 'decimal';
};

type Props = {
	value: string;
	onChange: ( value: string ) => void;
	onFocus?: ( event: React.FocusEvent< HTMLInputElement > ) => void;
	onKeyDown?: ( event: React.KeyboardEvent< HTMLInputElement > ) => void;
	min: number;
	max: number;
};

const NOT_NUMBERS_OR_SEPARATORS_OR_MINUS_REGEX = /[^0-9,.\ -]/g;

export const useNumberInputProps = ( {
	value,
	onChange,
	onFocus,
	onKeyDown,
	min,
	max,
}: Props ) => {
	const { formatNumber, parseNumber } = useProductHelper();

	const numberInputProps: NumberInputProps = {
		value: formatNumber( value ),
		onFocus( event: React.FocusEvent< HTMLInputElement > ) {
			deferSelectInFocus( event.currentTarget );
			if ( onFocus ) {
				onFocus( event );
			}
		},
		onKeyUp( event: React.KeyboardEvent< HTMLInputElement > ) {
			if ( event.code === 'ArrowUp' || event.code === 'ArrowDown' ) {
				event.preventDefault();
			}
		},
		inputMode: 'decimal',
		onKeyDown( event: React.KeyboardEvent< HTMLInputElement > ) {
			const amount = Number.parseFloat( value || '0' );
			const step = Number( event.currentTarget.step || '1' );

			if ( event.code === 'ArrowUp' ) {
				event.preventDefault();
				if ( amount + step <= max ) onChange( String( amount + step ) );
			}
			if ( event.code === 'ArrowDown' ) {
				event.preventDefault();
				if ( amount - step >= min ) onChange( String( amount - step ) );
			}
			if ( onKeyDown ) {
				onKeyDown( event );
			}
		},
		onChange( newValue: string ) {
			let sanitizeValue = parseNumber(
				newValue.replace( NOT_NUMBERS_OR_SEPARATORS_OR_MINUS_REGEX, '' )
			);
			const numberValue = Number( sanitizeValue );

			if ( sanitizeValue && numberValue >= max ) {
				sanitizeValue = String( max );
			} else if ( sanitizeValue && numberValue <= min ) {
				sanitizeValue = String( min );
			}
			onChange( ! Number.isNaN( numberValue ) ? sanitizeValue : '' );
		},
	};
	return numberInputProps;
};
