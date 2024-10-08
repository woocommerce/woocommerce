/**
 * External dependencies
 */
import { useBlockProps } from '@wordpress/block-editor';
import type { BlockEditProps } from '@wordpress/blocks';
import EditProductLink from '@woocommerce/editor-components/edit-product-link';
import { ProductQueryContext as Context } from '@woocommerce/blocks/product-query/types';
import { useEffect } from '@wordpress/element';

/**
 * Internal dependencies
 */
import './editor.scss';
import Block from './block';
import type { Attributes } from './types';
import { useIsDescendentOfSingleProductBlock } from '../shared/use-is-descendent-of-single-product-block';
import { useIsDescendentOfSingleProductTemplate } from '../shared/use-is-descendent-of-single-product-template';

const Edit = ( {
	attributes,
	setAttributes,
	context,
}: BlockEditProps< Attributes > & { context: Context } ): JSX.Element => {
	const { style, ...blockProps } = useBlockProps( {
		className:
			'wc-block-components-product-sku wp-block-woocommerce-product-sku',
	} );
	const blockAttrs = {
		...attributes,
		...context,
	};
	const isDescendentOfQueryLoop = Number.isFinite( context.queryId );
	const { isDescendentOfSingleProductBlock } =
		useIsDescendentOfSingleProductBlock( { blockClientId: blockProps.id } );

	let { isDescendentOfSingleProductTemplate } =
		useIsDescendentOfSingleProductTemplate();

	if ( isDescendentOfQueryLoop ) {
		isDescendentOfSingleProductTemplate = false;
	}

	useEffect(
		() =>
			setAttributes( {
				isDescendentOfQueryLoop,
				isDescendentOfSingleProductTemplate,
				isDescendentOfSingleProductBlock,
			} ),
		[
			setAttributes,
			isDescendentOfQueryLoop,
			isDescendentOfSingleProductTemplate,
			isDescendentOfSingleProductBlock,
		]
	);

	return (
		<>
			<EditProductLink />
			<div
				{ ...blockProps }
				/**
				 * If block is a descendant of the All Products block, we don't
				 * want to apply style here because it will be applied inside
				 * Block using useColors, useTypography, and useSpacing hooks.
				 */
				style={
					attributes.isDescendantOfAllProducts ? undefined : style
				}
			>
				<Block { ...blockAttrs } setAttributes={ setAttributes } />
			</div>
		</>
	);
};

export default Edit;
