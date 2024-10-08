/**
 * External dependencies
 */
import EditProductLink from '@woocommerce/editor-components/edit-product-link';
import { useBlockProps } from '@wordpress/block-editor';
import type { BlockEditProps } from '@wordpress/blocks';
import { ProductQueryContext as Context } from '@woocommerce/blocks/product-query/types';
import { useEffect } from '@wordpress/element';

/**
 * Internal dependencies
 */
import Block from './block';
import withProductSelector from '../shared/with-product-selector';
import {
	BLOCK_TITLE as label,
	BLOCK_ICON as icon,
	BLOCK_DESCRIPTION as description,
} from './constants';
import type { BlockAttributes } from './types';

const Edit = ( {
	attributes,
	setAttributes,
	context,
}: BlockEditProps< BlockAttributes > & { context: Context } ): JSX.Element => {
	const { style, ...blockProps } = useBlockProps( {
		className: 'wc-block-components-product-stock-indicator',
	} );

	const blockAttrs = {
		...attributes,
		...context,
	};
	const isDescendentOfQueryLoop = Number.isFinite( context.queryId );

	useEffect(
		() => setAttributes( { isDescendentOfQueryLoop } ),
		[ setAttributes, isDescendentOfQueryLoop ]
	);

	return (
		<div
			{ ...blockProps }
			/**
			 * If block is a descendant of the All Products block, we don't
			 * want to apply style here because it will be applied inside
			 * Block using useColors, useTypography, and useSpacing hooks.
			 */
			style={ attributes.isDescendantOfAllProducts ? undefined : style }
		>
			<EditProductLink />
			<Block { ...blockAttrs } />
		</div>
	);
};

export default withProductSelector( { icon, label, description } )( Edit );
