/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { createElement } from '@wordpress/element';
import interpolateComponents from '@automattic/interpolate-components';
import { TextControl } from '@woocommerce/components';
import { useBlockProps } from '@wordpress/block-editor';
import { useController, useFormContext } from 'react-hook-form';

export function Edit( { attributes, setAttributes } ) {
	const blockProps = useBlockProps();
	const { control } = useFormContext();
	const {
		field: { ref, ...field },
		fieldState: { error },
	} = useController( {
		control,
		name: 'name',
		rules: { required: 'This is required' },
	} );
	return (
		<div { ...blockProps }>
			<TextControl
				{ ...field }
				label={ interpolateComponents( {
					mixedString: __( 'Name {{required/}}', 'woocommerce' ),
					components: {
						required: (
							<span className="woocommerce-product-form__optional-input">
								{ __( '(required)', 'woocommerce' ) }
							</span>
						),
					},
				} ) }
				placeholder={ __( 'e.g. 12 oz Coffee Mug', 'woocommerce' ) }
				// onChange={ ( value ) => {
				// 	attributes.onChange( value );
				// 	setAttributes( { value } );
				// } }
				// onChange={ ( newName ) => setAttributes( { name: newName } ) }
				// value={ attributes.name ?? '' }
				// { ...getInputProps( 'name', {
				// 	onBlur: setSkuIfEmpty,
				// } ) }
			/>
			<span>{ error?.message }</span>
		</div>
	);
}
