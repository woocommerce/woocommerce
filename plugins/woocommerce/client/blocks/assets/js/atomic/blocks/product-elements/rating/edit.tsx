/**
 * External dependencies
 */
import {
	AlignmentToolbar,
	BlockControls,
	useBlockProps,
} from '@wordpress/block-editor';
import type { BlockEditProps } from '@wordpress/blocks';
import { ProductQueryContext as Context } from '@woocommerce/blocks/product-query/types';
import { useProduct } from '@woocommerce/entities';

/**
 * Internal dependencies
 */
import Block from './block';
import { BlockAttributes } from './types';
import './editor.scss';

const Edit = (
	props: BlockEditProps< BlockAttributes > & { context: Context }
): JSX.Element => {
	const { attributes, setAttributes, context } = props;
	const blockProps = useBlockProps( {
		className: 'wp-block-woocommerce-product-rating',
	} );
	const blockAttrs = {
		...attributes,
		...context,
		shouldDisplayMockedReviewsWhenProductHasNoReviews: true,
	};

	const { product } = useProduct( context.postId );

	return (
		<>
			<BlockControls>
				<AlignmentToolbar
					value={ attributes.textAlign }
					onChange={ ( newAlign ) => {
						setAttributes( { textAlign: newAlign || '' } );
					} }
				/>
			</BlockControls>
			<div { ...blockProps }>
				<Block isAdmin={ true } { ...blockAttrs } product={ product } />
			</div>
		</>
	);
};

export default Edit;
