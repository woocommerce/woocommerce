/**
 * External dependencies
 */
import { useBlockProps } from '@wordpress/block-editor';
import { useEffect } from '@wordpress/element';
import type { BlockEditProps } from '@wordpress/blocks';
import type { ProductQueryContext as Context } from '@woocommerce/blocks/product-query/types';

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
import './editor.scss';
import { useIsDescendentOfSingleProductBlock } from '../shared/use-is-descendent-of-single-product-block';
import { useIsDescendentOfSingleProductTemplate } from '../shared/use-is-descendent-of-single-product-template';
import type { Attributes } from './types';

const Edit = ( {
	attributes,
	setAttributes,
	context,
}: BlockEditProps< Attributes > & { context: Context } ): JSX.Element => {
	const blockProps = useBlockProps();

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
		<div { ...blockProps }>
			<Block { ...attributes } />
		</div>
	);
};

// @todo: Refactor this to remove the HOC 'withProductSelector()' component as users will not see this block in the inserter. Therefore, we can export the Edit component by default. The HOC 'withProductSelector()' component should also be removed from other `product-elements` components. See also https://github.com/woocommerce/woocommerce-blocks/pull/7566#pullrequestreview-1168635469.
export default withProductSelector( { icon, label, description } )( Edit );
