/**
 * Internal dependencies
 */
import { useProductHelper } from './use-product-helper';
import { deferSelectInFocus } from '../utils';

export type NumberInputProps = {
	value: string;
	onChange: ( value: string ) => void;
	onFocus: ( event: React.FocusEvent< HTMLInputElement > ) => void;
	onBlur: ( event: React.FocusEvent ) => void;
	onKeyDown: ( event: React.KeyboardEvent< HTMLInputElement > ) => void;
};

type Props = {
	value: string;
	onChange: ( value: string ) => void;
	onFocus?: ( event: React.FocusEvent< HTMLInputElement > ) => void;
	onKeyUp?: ( event: React.KeyboardEvent< HTMLInputElement > ) => void;
	onKeyDown?: ( event: React.KeyboardEvent< HTMLInputElement > ) => void;
	onBlur?: ( event: React.FocusEvent ) => void;
};

export const useNumberInputProps = ( {
	value,
	onChange,
	onFocus,
	onKeyDown,
	onBlur,
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
		onBlur( event ) {
			if ( onBlur ) {
				onBlur( event );
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
			const sanitizeValue = parseNumber( newValue );
			onChange( sanitizeValue );
		},
	};
	return numberInputProps;
};
