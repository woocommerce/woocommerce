/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { createElement } from '@wordpress/element';
import interpolateComponents from '@automattic/interpolate-components';
import { TextControl } from '@woocommerce/components';
import { useBlockProps } from '@wordpress/block-editor';

export function Edit( { attributes, setAttributes } ) {
	const blockProps = useBlockProps();

	return (
		<div { ...blockProps }>
			<TextControl
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
				name={ 'woocommerce-product-name' }
				placeholder={ __( 'e.g. 12 oz Coffee Mug', 'woocommerce' ) }
				onChange={ ( newName ) => setAttributes( { name: newName } ) }
				value={ attributes.name }
				// { ...getInputProps( 'name', {
				// 	onBlur: setSkuIfEmpty,
				// } ) }
			/>
		</div>
	);
}
