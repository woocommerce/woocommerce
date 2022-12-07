/**
 * External dependencies
 */
import { createElement, MouseEvent, useRef } from 'react';
import classNames from 'classnames';

/**
 * Internal dependencies
 */
import { Props } from './types';

type ComboBoxProps = {
	children?: JSX.Element | JSX.Element[] | null;
	comboBoxProps: Props;
	inputProps: Props;
	suffix?: JSX.Element | null;
};

export const ComboBox = ( {
	children,
	comboBoxProps,
	inputProps,
	suffix,
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
			className={ classNames(
				'woocommerce-experimental-select-control__combo-box-wrapper',
				{
					'woocommerce-experimental-select-control__combo-box-wrapper--disabled':
						inputProps.disabled,
				}
			) }
			onMouseDown={ maybeFocusInput }
		>
			<div className="woocommerce-experimental-select-control__items-wrapper">
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
			</div>
			{ suffix && (
				<div className="woocommerce-experimental-select-control__suffix">
					{ suffix }
				</div>
			) }
		</div>
	);
};
