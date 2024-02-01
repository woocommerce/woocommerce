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
};

type Props = {
	value: string;
	onChange: ( value: string ) => void;
	onFocus?: ( event: React.FocusEvent< HTMLInputElement > ) => void;
	onKeyDown?: ( event: React.KeyboardEvent< HTMLInputElement > ) => void;
};

const NOT_NUMBERS_OR_SEPARATORS_REGEX = /[^0-9,.]/g;

export const useNumberInputProps = ( {
	value,
	onChange,
	onFocus,
	onKeyDown,
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
		onKeyDown( event: React.KeyboardEvent< HTMLInputElement > ) {
			const amount = Number.parseFloat( value || '0' );
			const step = Number( event.currentTarget.step || '1' );
			if ( event.code === 'ArrowUp' ) {
				event.preventDefault();
				onChange( String( amount + step ) );
			}
			if ( event.code === 'ArrowDown' ) {
				event.preventDefault();
				onChange( String( amount - step ) );
			}
			if ( onKeyDown ) {
				onKeyDown( event );
			}
		},
		onChange( newValue: string ) {
			const sanitizeValue = parseNumber(
				newValue.replace( NOT_NUMBERS_OR_SEPARATORS_REGEX, '' )
			);
			onChange( sanitizeValue );
		},
	};
	return numberInputProps;
};
