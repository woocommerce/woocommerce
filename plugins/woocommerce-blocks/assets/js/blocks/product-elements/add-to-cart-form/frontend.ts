/**
 * External dependencies
 */
import { store } from '@woocommerce/interactivity';
import { HTMLElementEvent } from '@woocommerce/types';

const getInputElement = ( event: HTMLElementEvent< HTMLButtonElement > ) => {
	const target = event.target as HTMLButtonElement;

	const inputForm = target.parentElement?.querySelector(
		'.input-text.qty.text'
	) as HTMLInputElement | null | undefined;

	return inputForm;
};

store( 'woocommerce/add-to-cart-form', {
	state: {},
	actions: {
		addQuantity: ( event: HTMLElementEvent< HTMLButtonElement > ) => {
			const inputElement = getInputElement( event );

			if ( inputElement ) {
				const parsedValue = parseInt( inputElement.value, 10 );
				const currentValue = isNaN( parsedValue ) ? 0 : parsedValue;
				inputElement.value = ( currentValue + 1 ).toString();
			}
		},
		removeQuantity: ( event: HTMLElementEvent< HTMLButtonElement > ) => {
			const inputElement = getInputElement( event );

			if ( inputElement && inputElement.value >= '1' ) {
				const parsedValue = parseInt( inputElement.value, 10 );
				inputElement.value = ( parsedValue - 1 ).toString();
			}
		},
	},
} );
