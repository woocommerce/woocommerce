/**
 * Internal dependencies
 */
import { useProductHelper } from './use-product-helper';
import { deferSelectInFocus } from '../utils';

export type NumberInputProps = {
	value: string;
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

export const useNumberInputProps = ( {
	value,
	onChange,
	onFocus,
	onKeyUp,
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
			const amount = Number.parseFloat( value || '0' );
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
			const sanitizeValue = parseNumber( newValue );
			onChange( sanitizeValue );
		},
	};
	return numberInputProps;
};
