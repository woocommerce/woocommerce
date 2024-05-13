/**
 * External dependencies
 */
import { ValidatedTextInput } from '@woocommerce/blocks-components';

const AddressField = ( {
	address1,
	address2,
	values,
	onChange,
	fieldsRef,
	fieldProps,
} ) => {
	console.log( { address1, address2, values, onChange } );

	return (
		<>
			{ address1 && (
				<ValidatedTextInput
					key={ address1.key }
					ref={ ( el ) => ( fieldsRef.current[ address1.key ] = el ) }
					{ ...fieldProps }
					type={ address1.type }
					label={ address1.label }
					className={ `wc-block-components-address-form__${ address1.key }` }
					value={ values[ address1.key ] }
					onChange={ ( newValue: string ) =>
						onChange( {
							...values,
							[ address1.key ]: newValue,
						} )
					}
				/>
			) }
			{ address2 && ! address2.hidden && (
				<ValidatedTextInput
					key={ address2.key }
					ref={ ( el ) => ( fieldsRef.current[ address2.key ] = el ) }
					{ ...fieldProps }
					type={ address2.type }
					label={ address2.label }
					className={ `wc-block-components-address-form__${ address2.key }` }
					value={ values[ address2.key ] }
					onChange={ ( newValue: string ) =>
						onChange( {
							...values,
							[ address2.key ]: newValue,
						} )
					}
				/>
			) }
		</>
	);

	// return (
	// 	<ValidatedTextInput
	// 		key={ field.key }
	// 		ref={ ( el ) => ( fieldsRef.current[ field.key ] = el ) }
	// 		{ ...fieldProps }
	// 		type={ field.type }
	// 		value={ values[ field.key ] }
	// 		onChange={ ( newValue: string ) =>
	// 			onChange( {
	// 				...values,
	// 				[ field.key ]: newValue,
	// 			} )
	// 		}
	// 	/>
	// );
};

export default AddressField;
