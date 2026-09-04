/**
 * External dependencies
 */
import clsx from 'clsx';
import { forwardRef, isValidElement, useState } from '@wordpress/element';
import { decodeEntities } from '@wordpress/html-entities';
import type { InputHTMLAttributes, ReactNode } from 'react';

/**
 * Internal dependencies
 */
import Label from '../label';
import './style.scss';

export interface TextInputProps
	extends Omit<
		InputHTMLAttributes< HTMLInputElement >,
		'onChange' | 'onBlur'
	> {
	id: string;
	ariaLabel?: string;
	label?: string | undefined;
	ariaDescribedBy?: string | undefined;
	screenReaderLabel?: string | undefined;
	help?: string;
	feedback?: ReactNode | null;
	autoComplete?: string | undefined;
	onChange: ( newValue: string ) => void;
	onBlur?: ( newValue: string ) => void;
	icon?: ReactNode;
}

const TextInput = forwardRef< HTMLInputElement, TextInputProps >(
	(
		{
			className,
			id,
			type = 'text',
			ariaLabel,
			ariaDescribedBy,
			label,
			screenReaderLabel,
			disabled,
			help,
			autoCapitalize = 'off',
			autoComplete = 'off',
			value = '',
			onChange,
			required = false,
			onBlur = () => {
				/* Do nothing */
			},
			feedback,
			icon = null,
			...rest
		},
		ref
	) => {
		const [ isActive, setIsActive ] = useState( false );

		const inputWithLabel = (
			<>
				<input
					type={ type }
					id={ id }
					value={ decodeEntities( value ) }
					ref={ ref }
					autoCapitalize={ autoCapitalize }
					autoComplete={ autoComplete }
					onChange={ ( event ) => {
						onChange( event.target.value );
					} }
					onFocus={ () => setIsActive( true ) }
					onBlur={ ( event ) => {
						onBlur( event.target.value );
						setIsActive( false );
					} }
					aria-label={ ariaLabel || label }
					disabled={ disabled }
					aria-describedby={
						!! help && ! ariaDescribedBy
							? id + '__help'
							: ariaDescribedBy
					}
					required={ required }
					{ ...rest }
				/>
				<Label
					label={ label }
					screenReaderLabel={ screenReaderLabel || label }
					wrapperElement="label"
					wrapperProps={ {
						htmlFor: id,
					} }
					htmlFor={ id }
				/>
			</>
		);

		return (
			<div
				className={ clsx( 'wc-block-components-text-input', className, {
					'is-active': isActive || value,
				} ) }
			>
				{ isValidElement( icon ) ? (
					<div className="wc-block-components-text-input__wrapper">
						{ inputWithLabel }
						{ icon }
					</div>
				) : (
					inputWithLabel
				) }
				{ !! help && (
					<p
						id={ id + '__help' }
						className="wc-block-components-text-input__help"
					>
						{ help }
					</p>
				) }
				{ feedback }
			</div>
		);
	}
);

export default TextInput;
