/**
 * External dependencies
 */
import EditProductLink from '@woocommerce/editor-components/edit-product-link';
import { useBlockProps } from '@wordpress/block-editor';
import type { BlockEditProps } from '@wordpress/blocks';
import { ProductQueryContext as Context } from '@woocommerce/blocks/product-query/types';

/**
 * Internal dependencies
 */
import Block from './block';
import type { BlockAttributes } from './types';

const Edit = ( {
	attributes,
	context,
}: BlockEditProps< BlockAttributes > & { context: Context } ): JSX.Element => {
	const { style, ...blockProps } = useBlockProps( {
		className: 'wc-block-components-product-stock-indicator',
	} );

	const blockAttrs = {
		...attributes,
		...context,
	};

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

const StockIndicatorEdit: React.FC<
	BlockEditProps< BlockAttributes > & { context: Context }
> = ( props ) => {
	return <Edit { ...props } />;
};

export default StockIndicatorEdit;
