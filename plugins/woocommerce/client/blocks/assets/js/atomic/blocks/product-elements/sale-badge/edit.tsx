/**
 * External dependencies
 */
import { useBlockProps } from '@wordpress/block-editor';
import type { BlockEditProps } from '@wordpress/blocks';
import { ProductQueryContext as Context } from '@woocommerce/blocks/product-query/types';

/**
 * Internal dependencies
 */
import Block from './block';
import type { BlockAttributes } from './types';
import { useIsDescendentOfSingleProductTemplate } from '../shared/use-is-descendent-of-single-product-template';

const Edit = ( {
	attributes,
	context,
}: BlockEditProps< BlockAttributes > & { context: Context } ): JSX.Element => {
	const blockProps = useBlockProps();

	const { isDescendentOfSingleProductTemplate } =
		useIsDescendentOfSingleProductTemplate();

	const blockAttrs = {
		...attributes,
		...context,
	};

	return (
		<div { ...blockProps }>
			<Block
				{ ...blockAttrs }
				isDescendentOfSingleProductTemplate={
					isDescendentOfSingleProductTemplate
				}
			/>
		</div>
	);
};

export default Edit;
