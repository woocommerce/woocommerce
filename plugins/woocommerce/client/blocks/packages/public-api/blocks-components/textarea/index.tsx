/**
 * External dependencies
 */
import clsx from 'clsx';
import type { JSX, TextareaHTMLAttributes } from 'react';

/**
 * Internal dependencies
 */
import './style.scss';

export interface TextareaProps extends TextareaHTMLAttributes< HTMLTextAreaElement > {
	onTextChange: ( newText: string ) => void;
	value: string;
}

export const Textarea = ( {
	className = '',
	disabled = false,
	onTextChange,
	onChange,
	placeholder,
	rows = 2,
	value = '',
	...props
}: TextareaProps ): JSX.Element => (
	<textarea
		{ ...props }
		className={ clsx( 'wc-block-components-textarea', className ) }
		disabled={ disabled }
		onChange={ ( event ) => {
			onTextChange( event.target.value );
			onChange?.( event );
		} }
		placeholder={ placeholder }
		rows={ rows }
		value={ value }
	/>
);

export default Textarea;
