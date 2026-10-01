/**
 * External dependencies
 */
import clsx from 'clsx';
import { forwardRef, isValidElement, useState } from '@wordpress/element';
import { useMergeRefs } from '@wordpress/compose';
import { decodeEntities } from '@wordpress/html-entities';
import { __, sprintf } from '@wordpress/i18n';
import { unescapeMask } from '@woocommerce/input-mask';
import type { InputHTMLAttributes, ReactNode } from 'react';

/**
 * Internal dependencies
 */
import Label from '../label';
import { useInputMask } from './use-input-mask';
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
	// Input mask, see @woocommerce/input-mask. `value` and `onChange` carry the unmasked value.
	mask?: string | undefined;
}

const TextInput = forwardRef< HTMLInputElement, TextInputProps >(
	(
		{
			className,
			id,
			type = 'text',
			ariaLabel,
			ariaDescribedBy,
			'aria-describedby': ariaDescribedByAttribute,
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
			mask,
			...rest
		},
		ref
	) => {
		const [ isActive, setIsActive ] = useState( false );
		const decodedValue = decodeEntities( String( value ) );
		const inputMask = useInputMask( mask, decodedValue, onChange );
		const mergedRef = useMergeRefs( [ ref, inputMask.ref ] );
		const hintId = mask ? id + '__mask-hint' : undefined;
		const helpId = help ? id + '__help' : undefined;
		const description =
			ariaDescribedByAttribute ?? ( ariaDescribedBy || helpId );
		const describedBy = [ description, hintId ]
			.filter( Boolean )
			.join( ' ' );

		// Date-like inputs report a value the browser can't parse (e.g. the 31st of a 30-day month) as an
		// empty `value`, so the input is asked directly. Focus and blur both re-render, which is when this
		// can have changed while the field is not active.
		const input = typeof ref === 'object' ? ref?.current : null;
		const isFieldActive =
			isActive || !! value || !! input?.validity?.badInput;

		const inputWithLabel = (
			<>
				<input
					type={ type }
					id={ id }
					{ ...( mask
						? { defaultValue: decodedValue }
						: { value: decodedValue } ) }
					ref={ mergedRef }
					autoCapitalize={ autoCapitalize }
					autoComplete={ autoComplete }
					onChange={
						inputMask.isBound
							? undefined
							: ( event ) =>
									inputMask.onChange( event.target.value )
					}
					onFocus={ () => setIsActive( true ) }
					onBlur={ ( event ) => {
						onBlur( event.target.value );
						setIsActive( false );
					} }
					aria-label={ ariaLabel || label }
					disabled={ disabled }
					aria-describedby={ describedBy || undefined }
					required={ required }
					{ ...rest }
				/>
				{ !! mask && (
					<span id={ hintId } className="screen-reader-text">
						{ sprintf(
							/* translators: %s: expected input format, e.g. 000-000 where 0 is a digit */
							__( 'Expected format: %s', 'woocommerce' ),
							unescapeMask( mask )
						) }
					</span>
				) }
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
					'is-active': isFieldActive || inputMask.hasText,
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
						id={ helpId }
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
