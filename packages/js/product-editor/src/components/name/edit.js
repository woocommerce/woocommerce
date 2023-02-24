/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { createElement } from '@wordpress/element';
import interpolateComponents from '@automattic/interpolate-components';
import { TextControl } from '@woocommerce/components';
import { useBlockProps } from '@wordpress/block-editor';
import { useEntityProp } from '@wordpress/core-data';

export function Edit() {
	const blockProps = useBlockProps();
	const [ title, setTitle ] = useEntityProp( 'postType', 'product', 'title' );

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
				onChange={ setTitle }
				value={ title }
				// { ...getInputProps( 'name', {
				// 	onBlur: setSkuIfEmpty,
				// } ) }
			/>
		</div>
	);
}
