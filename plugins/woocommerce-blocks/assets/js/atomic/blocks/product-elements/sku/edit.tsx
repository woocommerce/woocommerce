/**
 * External dependencies
 */
import type { BlockEditProps } from '@wordpress/blocks';
import EditProductLink from '@woocommerce/editor-components/edit-product-link';
import { ProductQueryContext as Context } from '@woocommerce/blocks/product-query/types';
import { useEffect } from '@wordpress/element';

/**
 * Internal dependencies
 */
import Block from './block';
import type { Attributes } from './types';

const Edit = ( {
	attributes,
	setAttributes,
	context,
}: BlockEditProps< Attributes > & { context: Context } ): JSX.Element => {
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
		<>
			<EditProductLink />
			<Block { ...blockAttrs } />
		</>
	);
};

export default Edit;
