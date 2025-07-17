/**
 * External dependencies
 */
import { productsStore } from '@woocommerce/data';
import { useMemo } from '@wordpress/element';
import { useSelect } from '@wordpress/data';
import { __ } from '@wordpress/i18n';
import {
	store as blockEditorStore,
	useBlockProps,
	// @ts-expect-error - useInnerBlocksProps is not exported from @wordpress/block-editor
	useInnerBlocksProps,
	Warning,
} from '@wordpress/block-editor';

/**
 * Internal dependencies
 */
import { ProductDetailsEditProps } from './types';
import { getTemplate } from './utils';

/**
 * Check if block is inside a Query Loop with non-product post type
 *
 * @param {string} clientId The block's client ID
 * @param {string} postType The current post type
 * @return {boolean} Whether the block is in an invalid Query Loop context
 */
const useIsInvalidQueryLoopContext = ( clientId: string, postType: string ) => {
	return useSelect(
		( select ) => {
			const blockParents = select(
				blockEditorStore
			).getBlockParentsByBlockName( clientId, 'core/post-template' );
			return blockParents.length > 0 && postType !== 'product';
		},
		[ clientId, postType ]
	);
};

const Edit = ( { clientId, context }: ProductDetailsEditProps ) => {
	const blockProps = useBlockProps();

	const product = useSelect(
		( select ) => {
			if ( ! context.postId ) {
				return null;
			}
			const { getProduct } = select( productsStore );
			return getProduct( Number( context.postId ) );
		},
		[ context.postId ]
	);

	const template = useMemo( () => {
		if ( ! product ) {
			return [];
		}

		return getTemplate( product );
	}, [ product ] );

	const innerBlocksProps = useInnerBlocksProps( blockProps, {
		template,
	} );

	const isInvalidQueryLoopContext = useIsInvalidQueryLoopContext(
		clientId,
		context.postType
	);
	if ( isInvalidQueryLoopContext ) {
		return (
			<div { ...blockProps }>
				<Warning>
					{ __(
						'The Product Details block requires a product context. When used in a Query Loop, the Query Loop must be configured to display products.',
						'woocommerce'
					) }
				</Warning>
			</div>
		);
	}
	return <div { ...innerBlocksProps } />;
};

export default Edit;
