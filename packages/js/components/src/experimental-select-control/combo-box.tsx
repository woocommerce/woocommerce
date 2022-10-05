/**
 * External dependencies
 */
import { createElement, MouseEvent, useRef } from 'react';
import { Icon, search } from '@wordpress/icons';

/**
 * Internal dependencies
 */
import { Props } from './types';

type ComboBoxProps = {
	children?: JSX.Element | JSX.Element[] | null;
	comboBoxProps: Props;
	inputProps: Props;
};

export const ComboBox = ( {
	children,
	comboBoxProps,
	inputProps,
}: ComboBoxProps ) => {
	const inputRef = useRef< HTMLInputElement | null >( null );

	const maybeFocusInput = ( event: MouseEvent< HTMLDivElement > ) => {
		if ( ! inputRef || ! inputRef.current ) {
			return;
		}

		event.preventDefault();

		if ( document.activeElement !== inputRef.current ) {
			inputRef.current.focus();
			event.stopPropagation();
		}
	};

	return (
		// Disable reason: The click event is purely for accidental clicks around the input.
		// Keyboard users are still able to tab to and interact with elements in the combobox.
		/* eslint-disable jsx-a11y/no-static-element-interactions, jsx-a11y/click-events-have-key-events */
		<div
			className="woocommerce-experimental-select-control__combo-box-wrapper"
			onMouseDown={ maybeFocusInput }
		>
			{ children }
			<div
				{ ...comboBoxProps }
				className="woocommerce-experimental-select-control__combox-box"
			>
				<input
					{ ...inputProps }
					ref={ ( node ) => {
						inputRef.current = node;
						(
							inputProps.ref as unknown as (
								node: HTMLInputElement | null
							) => void
						 )( node );
					} }
				/>
			</div>
			<Icon
				className="woocommerce-experimental-select-control__combox-box-icon"
				icon={ search }
			/>
		</div>
	);
};
