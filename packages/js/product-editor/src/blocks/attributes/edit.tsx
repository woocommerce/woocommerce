/**
 * External dependencies
 */
import { BlockInstance, createBlock } from '@wordpress/blocks';
import { createElement, useEffect, useState } from '@wordpress/element';
import { ProductAttribute } from '@woocommerce/data';
// eslint-disable-next-line @typescript-eslint/ban-ts-comment
// @ts-ignore No types for this exist yet
// eslint-disable-next-line @woocommerce/dependency-group
import { useBlockProps, useInnerBlocksProps } from '@wordpress/block-editor';
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

	const [ attributeBlocks, setAttributeBlocks ] =
		useState< BlockInstance[] >();

	useEffect( () => {
		setAttributeBlocks(
			entityAttributes.map( ( entityAttribute ) => {
				console.log( 'entityAttribute', entityAttribute );

				return createBlock( 'woocommerce/product-attribute', {
					entityAttribute,
				} );
			} )
		);
	}, [ entityAttributes ] );

	const blockProps = useBlockProps();

	const { children, ...innerBlocksProps } = useInnerBlocksProps(
		{},
		{
			allowedBlocks: [ 'woocommerce/product-attribute' ],
			value: attributeBlocks,
			onInput: () => {
				console.log( 'onInput' );
			},
			onChange: () => {
				console.log( 'onChange' );
			},
			templateInsertUpdatesSelection: false,
			templateLock: 'insert',
		}
	);

	return (
		<div { ...blockProps }>
			<AttributesContainer
				productId={ productId }
				value={ entityAttributes }
				onChange={ setEntityAttributes }
			/>
			<div { ...innerBlocksProps }>{ children }</div>
		</div>
	);
}
