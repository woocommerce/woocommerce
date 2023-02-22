/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { createElement } from '@wordpress/element';
import interpolateComponents from '@automattic/interpolate-components';
import { TextControl } from '@woocommerce/components';
import { useBlockProps } from '@wordpress/block-editor';
import { useDispatch, useSelect } from '@wordpress/data';

export function Edit( { context: { productId } } ) {
	const blockProps = useBlockProps();
	const product = useSelect(
		( select ) =>
			select( 'core' ).getEditedEntityRecord(
				'postType',
				'product',
				productId
			),
		[ productId ]
	);

	const { editEntityRecord } = useDispatch( 'core' );
	const handleChange = ( title ) =>
		editEntityRecord( 'postType', 'product', productId, { title } );

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
				onChange={ handleChange }
				value={ product.title.raw }
				// { ...getInputProps( 'name', {
				// 	onBlur: setSkuIfEmpty,
				// } ) }
			/>
		</div>
	);
}
