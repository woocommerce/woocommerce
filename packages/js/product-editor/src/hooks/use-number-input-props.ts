/**
 * External dependencies
 */
import { InputChangeCallback } from '@wordpress/components/build-types/input-control/types';

/**
 * Internal dependencies
 */
import { useProductHelper } from './use-product-helper';
import { deferSelectInFocus } from '../utils';

export type NumberInputProps = {
	value: string;
	onChange: InputChangeCallback;
	onFocus: ( event: React.FocusEvent< HTMLInputElement > ) => void;
	onKeyDown: ( event: React.KeyboardEvent< HTMLInputElement > ) => void;
	onKeyUp: ( event: React.KeyboardEvent< HTMLInputElement > ) => void;
};

type Props = {
	value: string;
	onChange: InputChangeCallback;
	onFocus?: ( event: React.FocusEvent< HTMLInputElement > ) => void;
	onKeyDown?: ( event: React.KeyboardEvent< HTMLInputElement > ) => void;
};

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
				// @ts-expect-error: TODO - fix this its not type safe.
				onChange( String( amount + step ) );
			}
			if ( event.code === 'ArrowDown' ) {
				event.preventDefault();
				// @ts-expect-error: TODO - fix this its not type safe.
				onChange( String( amount - step ) );
			}
			if ( onKeyDown ) {
				onKeyDown( event );
			}
		},
		onChange( newValue: string | undefined ) {
			// @ts-expect-error: TODO - fix this its not type safe.
			const sanitizeValue = parseNumber( newValue );
			// @ts-expect-error: TODO - fix this its not type safe.
			onChange( sanitizeValue );
		},
	};
	return numberInputProps;
};
