/**
 * External dependencies
 */
import { BlockAttributes } from '@wordpress/blocks';
import { createElement } from '@wordpress/element';
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

export function AttributesBlockEdit( {
	attributes,
}: ProductEditorBlockEditProps< BlockAttributes > ) {
	const [ entityAttributes, setEntityAttributes ] = useEntityProp<
		ProductProductAttribute[]
	>( 'postType', 'product', 'attributes' );

	const productId = useEntityId( 'postType', 'product' );

	const blockProps = useWooBlockProps( attributes );

	return (
		<div { ...blockProps }>
			<AttributesContainer
				productId={ productId }
				value={ entityAttributes }
				onChange={ setEntityAttributes }
			/>
		</div>
	);
}
