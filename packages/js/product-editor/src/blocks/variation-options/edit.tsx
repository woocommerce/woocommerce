/**
 * External dependencies
 */

import { useBlockProps } from '@wordpress/block-editor';
import { createElement } from '@wordpress/element';
import { ProductAttribute } from '@woocommerce/data';
// eslint-disable-next-line @typescript-eslint/ban-ts-comment
// @ts-ignore No types for this exist yet.
// eslint-disable-next-line @woocommerce/dependency-group
import { useEntityProp, useEntityId } from '@wordpress/core-data';

/**
 * Internal dependencies
 */
import { useProductAttributes } from '../../hooks/use-product-attributes';
import { AttributeControl } from '../../components/attribute-control';

export function Edit() {
	const blockProps = useBlockProps();

	const [ entityAttributes, setEntityAttributes ] = useEntityProp<
		ProductAttribute[]
	>( 'postType', 'product', 'attributes' );

	const { attributes, handleChange } = useProductAttributes( {
		allAttributes: entityAttributes,
		onChange: setEntityAttributes,
		isVariationAttributes: true,
		productId: useEntityId( 'postType', 'product' ),
	} );

	return (
		<div { ...blockProps }>
			<AttributeControl
				value={ attributes }
				onChange={ handleChange }
				uiStrings={ {
					globalAttributeHelperMessage: '',
					customAttributeHelperMessage: '',
					newAttributeModalNotice: '',
				} }
			/>
		</div>
	);
}
