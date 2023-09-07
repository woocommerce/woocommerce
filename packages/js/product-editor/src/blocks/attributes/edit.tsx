/**
 * External dependencies
 */
import { createElement } from '@wordpress/element';
import { ProductAttribute } from '@woocommerce/data';
import { useBlockProps } from '@wordpress/block-editor';
// eslint-disable-next-line @typescript-eslint/ban-ts-comment
// @ts-ignore No types for this exist yet.
// eslint-disable-next-line @woocommerce/dependency-group
import { useEntityProp, useEntityId } from '@wordpress/core-data';

/**
 * Internal dependencies
 */
import { Attributes as AttributesContainer } from '../../components/attributes/attributes';

export function Edit() {
	const [ entityAttributes, setEntityAttributes ] = useEntityProp<
		ProductAttribute[]
	>( 'postType', 'product', 'attributes' );

	const productId = useEntityId( 'postType', 'product' );

	const blockProps = useBlockProps();

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
