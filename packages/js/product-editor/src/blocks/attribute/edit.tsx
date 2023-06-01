/**
 * External dependencies
 */
import { BlockEditProps } from '@wordpress/blocks';
import { createElement, Fragment } from '@wordpress/element';
import { ProductAttribute } from '@woocommerce/data';
// eslint-disable-next-line @typescript-eslint/ban-ts-comment
// @ts-ignore No types for this exist yet
// eslint-disable-next-line @woocommerce/dependency-group
import { useBlockProps } from '@wordpress/block-editor';
// eslint-disable-next-line @typescript-eslint/ban-ts-comment
// @ts-ignore No types for this exist yet.
// eslint-disable-next-line @woocommerce/dependency-group
import { useEntityProp, useEntityId } from '@wordpress/core-data';

/**
 * Internal dependencies
 */
import { Attributes as AttributesContainer } from '../../components/attributes/attributes';
import { AttributeListItem } from '../../components/attribute-list-item';
import { AttributeBlockAttributes } from './types';

export function Edit( {
	attributes,
}: BlockEditProps< AttributeBlockAttributes > ) {
	/*
	const [ entityAttributes, setEntityAttributes ] = useEntityProp<
		ProductAttribute[]
	>( 'postType', 'product', 'attributes' );

	const productId = useEntityId( 'postType', 'product' );
	*/

	const blockProps = useBlockProps();

	const { entityAttribute } = attributes;

	function onEditClick( editedAttribute: ProductAttribute ) {
		console.log( 'onEditClick', editedAttribute );
	}

	function onRemoveClick( removedAttribute: ProductAttribute ) {
		console.log( 'onRemoveClick', removedAttribute );
	}

	return (
		<div { ...blockProps }>
			<AttributeListItem
				attribute={ entityAttribute }
				onEditClick={ onEditClick }
				onRemoveClick={ onRemoveClick }
			/>
		</div>
	);
}
