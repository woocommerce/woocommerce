/**
 * External dependencies
 */
import { useEvent } from '@wordpress/compose';
import { useLayoutEffect, useRef, useState } from '@wordpress/element';
import type { bind, Bound, FormatResult } from '@woocommerce/input-mask';

type InputMaskWindow = Window & {
	wc?: { inputMask?: { bind: typeof bind } };
};

/**
 * Keeps the input display in sync with raw values and reports edits to the parent.
 */
export const useInputMask = (
	mask: string | undefined,
	value: string,
	onChange: ( value: string ) => void
) => {
	const inputRef = useRef< HTMLInputElement >( null );
	const boundRef = useRef< Bound >();
	const lastValue = useRef< string >();
	const [ hasText, setHasText ] = useState( false );
	const bindMask = ( window as InputMaskWindow ).wc?.inputMask?.bind;

	const handleChange = useEvent(
		( newValue: string, result?: FormatResult ) => {
			lastValue.current = newValue;
			if ( mask ) {
				setHasText( ( result?.display ?? newValue ) !== '' );
			}
			onChange( newValue );
		}
	);

	useLayoutEffect( () => {
		if ( ! mask || ! bindMask || ! inputRef.current ) {
			return;
		}
		const bound = bindMask( inputRef.current, {
			mask,
			onChange: handleChange,
		} );
		boundRef.current = bound;
		lastValue.current = undefined;
		return () => {
			bound.destroy();
			boundRef.current = undefined;
			lastValue.current = undefined;
		};
	}, [ mask, bindMask, handleChange ] );

	useLayoutEffect( () => {
		const input = inputRef.current;
		if ( ! mask || ! input || value === lastValue.current ) {
			return;
		}
		if ( boundRef.current ) {
			boundRef.current.setValue( value );
		} else if ( input.value !== value ) {
			input.value = value;
		}
		lastValue.current = value;
		setHasText( input.value !== '' );
	}, [ value, mask, bindMask ] );

	return {
		ref: inputRef,
		onChange: handleChange,
		hasText: !! mask && hasText,
		isBound: !! mask && !! bindMask,
	};
};
