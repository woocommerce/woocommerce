/**
 * External dependencies
 */
import { useRefEffect } from '@wordpress/compose';
import { useRef } from '@wordpress/element';
import type { bind } from '@woocommerce/input-mask';

type InputMaskWindow = Window & {
	wc?: { inputMask?: { bind: typeof bind } };
};

/**
 * Masks the input when the `wc-input-mask` script is loaded, else reports the value as typed.
 * `onChange` receives the unmasked value.
 */
export const useInputMask = (
	mask: string | undefined,
	onChange: ( value: string ) => void
) => {
	const onChangeRef = useRef( onChange );
	onChangeRef.current = onChange;

	return useRefEffect< HTMLInputElement >(
		( input ) => {
			if ( ! mask ) {
				return;
			}
			const inputMask = ( window as InputMaskWindow ).wc?.inputMask;
			if ( inputMask ) {
				return inputMask.bind( input, {
					mask,
					onChange: ( value ) => onChangeRef.current( value ),
				} ).destroy;
			}
			const onInput = () => onChangeRef.current( input.value );
			input.addEventListener( 'input', onInput );
			return () => input.removeEventListener( 'input', onInput );
		},
		[ mask ]
	);
};
