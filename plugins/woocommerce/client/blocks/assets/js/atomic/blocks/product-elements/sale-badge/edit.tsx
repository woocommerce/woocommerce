/**
 * External dependencies
 */
import { useBlockProps } from '@wordpress/block-editor';
import type { BlockEditProps } from '@wordpress/blocks';
import { ProductQueryContext as Context } from '@woocommerce/blocks/product-query/types';
import { store as editorStore } from '@wordpress/editor';
import { useSelect } from '@wordpress/data';

/**
 * Internal dependencies
 */
import Block from './block';
import type { BlockAttributes } from './types';

const Edit = ( {
	attributes,
	context,
}: BlockEditProps< BlockAttributes > & { context: Context } ): JSX.Element => {
	const blockProps = useBlockProps();

	const isTemplate = useSelect( ( select ) => {
		const { getCurrentPostType } = select( editorStore );

		const template = getCurrentPostType() === 'wp_template';

		return template;
	}, [] );

	const blockAttrs = {
		...attributes,
		...context,
	};

	return (
		<div { ...blockProps }>
			<Block { ...blockAttrs } isTemplate={ isTemplate } />
		</div>
	);
};

export default Edit;
