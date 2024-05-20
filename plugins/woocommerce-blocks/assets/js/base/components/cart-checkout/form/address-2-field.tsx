/**
 * External dependencies
 */
import { ValidatedTextInput } from '@woocommerce/blocks-components';
import { AddressFormValues, ContactFormValues } from '@woocommerce/settings';
import { useEffect, useState, Fragment } from '@wordpress/element';
import { __, sprintf } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { AddressFieldProps } from './types';

const Address2Field = < T extends AddressFormValues | ContactFormValues >( {
	fields,
	props,
	onChange,
	value,
}: AddressFieldProps< T > ): JSX.Element => {
	const isFieldRequired = fields?.required ?? false;

	// Display the input field if it has a value or if it is required.
	const [ isFieldVisible, setFieldVisible ] = useState(
		() => Boolean( value ) || isFieldRequired
	);

	// Toggle the visibility of the field, in teh page editor, based on whether it is required.
	useEffect( () => {
		setFieldVisible( isFieldRequired );
	}, [ isFieldRequired ] );

	return (
		<Fragment key={ fields.key }>
			{ isFieldVisible ? (
				<ValidatedTextInput
					{ ...props }
					type={ fields.type }
					label={
						isFieldRequired ? fields.label : fields.optionalLabel
					}
					className={ `wc-block-components-address-form__${ fields.key }` }
					value={ value }
					onChange={ ( newValue: string ) =>
						onChange( fields.key as keyof T, newValue )
					}
				/>
			) : (
				<button
					className={
						'wc-block-components-address-form__address_2-toggle'
					}
					onClick={ () => setFieldVisible( true ) }
				>
					{ sprintf(
						// translators: %s: address 2 field label.
						__( '+ Add %s', 'woocommerce' ),
						fields.label.toLowerCase()
					) }
				</button>
			) }
		</Fragment>
	);
};

export default Address2Field;
