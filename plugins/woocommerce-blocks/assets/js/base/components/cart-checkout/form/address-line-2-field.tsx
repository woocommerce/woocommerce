/**
 * External dependencies
 */
import { ValidatedTextInput } from '@woocommerce/blocks-components';
import { AddressFormValues, ContactFormValues } from '@woocommerce/settings';
import { useState, Fragment, useCallback } from '@wordpress/element';
import { __, sprintf } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { AddressLineFieldProps } from './types';
import './style.scss';

const AddressLine2Field = < T extends AddressFormValues | ContactFormValues >( {
	field,
	props,
	onChange,
	value,
}: AddressLineFieldProps< T > ): JSX.Element => {
	const isFieldRequired = field?.required ?? false;

	// Display the input field if it has a value or if it is required.
	const [ isFieldVisible, setIsFieldVisible ] = useState(
		() => Boolean( value ) || isFieldRequired
	);

	const handleHiddenInputChange = useCallback(
		( newValue: string ) => {
			onChange( field.key as keyof T, newValue );
			setIsFieldVisible( true );
		},
		[ field.key, onChange ]
	);

	return (
		<Fragment>
			{ isFieldVisible ? (
				<ValidatedTextInput
					{ ...props }
					type={ field.type }
					label={
						isFieldRequired ? field.label : field.optionalLabel
					}
					className={ `wc-block-components-address-form__${ field.key }` }
					value={ value }
					onChange={ ( newValue: string ) =>
						onChange( field.key as keyof T, newValue )
					}
				/>
			) : (
				<>
					<button
						className={
							'wc-block-components-address-form__address_2-toggle'
						}
						onClick={ () => setIsFieldVisible( true ) }
					>
						{ sprintf(
							// translators: %s: address 2 field label.
							__( '+ Add %s', 'woocommerce' ),
							field.label.toLowerCase()
						) }
					</button>
					<input
						type="text"
						tabIndex={ -1 }
						className="wc-block-components-address-form__address_2-hidden-input"
						aria-hidden="true"
						aria-label={ field.label }
						autoComplete={ field.autocomplete }
						id={ props?.id }
						value={ value }
						onChange={ ( event ) =>
							handleHiddenInputChange( event.target.value )
						}
					/>
				</>
			) }
		</Fragment>
	);
};

export default AddressLine2Field;
