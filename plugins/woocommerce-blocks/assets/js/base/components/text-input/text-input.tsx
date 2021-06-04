/**
 * External dependencies
 */
import { forwardRef, InputHTMLAttributes } from 'react';
import classnames from 'classnames';
import { useState } from '@wordpress/element';
import { Label } from '@woocommerce/blocks-checkout';

/**
 * Internal dependencies
 */
import './style.scss';

interface TextInputPropsWithNumberType {
	type: 'number';
	step?: number;
	min?: number;
	max?: number;
}

interface TextInputProps
	extends Omit<
		InputHTMLAttributes< HTMLInputElement >,
		'onChange' | 'onBlur'
	> {
	id: string;
	ariaLabel?: string;
	label?: string;
	ariaDescribedBy?: string;
	screenReaderLabel?: string;
	help?: string;
	feedback?: boolean | JSX.Element;
	autoComplete?: string;
	onChange: ( newValue: string ) => void;
	onBlur?: ( newValue: string ) => void;
}

const TextInput = forwardRef<
	HTMLInputElement,
	TextInputProps & ( Record< string, never > | TextInputPropsWithNumberType )
>(
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
			min,
			max,
			step,
			required = false,
			onBlur = () => {
				/* Do nothing */
			},
			feedback,
		},
		ref
	) => {
		const [ isActive, setIsActive ] = useState( false );

		const numberAttributesFromProps: {
			[ prop: string ]: string | number | undefined;
		} =
			type === 'number'
				? {
						step,
						min,
						max,
				  }
				: {};

		const numberProps: {
			[ prop: string ]: string | number | undefined;
		} = {};

		Object.keys( numberAttributesFromProps ).forEach( ( key ) => {
			if ( typeof numberAttributesFromProps[ key ] === 'undefined' ) {
				return;
			}
			numberProps[ key ] = numberAttributesFromProps[ key ];
		} );

		return (
			<div
				className={ classnames(
					'wc-block-components-text-input',
					className,
					{
						'is-active': isActive || value,
					}
				) }
			>
				<input
					type={ type }
					id={ id }
					value={ value }
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
					{ ...numberProps }
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
