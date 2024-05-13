/**
 * External dependencies
 */
import { BlockAttributes } from '@wordpress/blocks';
import { createElement, useEffect } from '@wordpress/element';
import { useWooBlockProps } from '@woocommerce/block-templates';
import { ProductProductAttribute } from '@woocommerce/data';
// eslint-disable-next-line @typescript-eslint/ban-ts-comment
// @ts-ignore No types for this exist yet.
// eslint-disable-next-line @woocommerce/dependency-group
import { useEntityProp, useEntityId } from '@wordpress/core-data';

/**
 * Internal dependencies
 */
import { Attributes as AttributesContainer } from '../../../components/attributes/attributes';
import { ProductEditorBlockEditProps } from '../../../types';
import { useProductAttributes } from '../../../hooks/use-product-attributes';

export function AttributesBlockEdit( {
	attributes,
	context: { isInSelectedTab },
}: ProductEditorBlockEditProps< BlockAttributes > ) {
	const [ entityAttributes, setEntityAttributes ] = useEntityProp<
		ProductProductAttribute[]
	>( 'postType', 'product', 'attributes' );

	const productId = useEntityId( 'postType', 'product' );

	const blockProps = useWooBlockProps( attributes );

	const {
		attributes: attributeList,
		fetchAttributes,
		handleChange,
	} = useProductAttributes( {
		allAttributes: entityAttributes,
		onChange: setEntityAttributes,
		productId,
	} );

	useEffect( () => {
		if ( isInSelectedTab ) {
			fetchAttributes();
		}
	}, [ entityAttributes, isInSelectedTab ] );

	return (
		<div { ...blockProps }>
			<AttributesContainer
				attributeList={ attributeList }
				onChange={ handleChange }
				value={ entityAttributes }
			/>
		</div>
	);
}
