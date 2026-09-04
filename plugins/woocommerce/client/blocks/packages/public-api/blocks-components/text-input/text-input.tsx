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
		// A masked input can show literals only, like `+1 `, while `value` is empty.
		const [ hasMaskedText, setHasMaskedText ] = useState( false );
		const mergedRef = useMergeRefs( [
			ref,
			useInputMask( mask, onChange ),
		] );
		const decodedValue = decodeEntities( String( value ) );
		const hintId = mask ? id + '__mask-hint' : undefined;
		const describedBy = [
			!! help && ! ariaDescribedBy ? id + '__help' : ariaDescribedBy,
			hintId,
		]
			.filter( Boolean )
			.join( ' ' );

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
						mask
							? undefined
							: ( event ) => onChange( event.target.value )
					}
					onFocus={ () => setIsActive( true ) }
					onBlur={ ( event ) => {
						onBlur( event.target.value );
						setIsActive( false );
						setHasMaskedText(
							!! mask && event.target.value !== ''
						);
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
					'is-active': isActive || value || hasMaskedText,
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
