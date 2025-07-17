/**
 * External dependencies
 */
import { productsStore } from '@woocommerce/data';
import { useMemo } from '@wordpress/element';
import { useSelect } from '@wordpress/data';
import { __ } from '@wordpress/i18n';
import { Disabled } from '@wordpress/components';

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
import { LegacyProductDetailsPreview } from './legacy-preview';
import './editor.scss';

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

	const { hasInnerBlocks, wasBlockJustInserted } = useSelect(
		( select ) => {
			const blocks = select( blockEditorStore ).getBlocks( clientId );
			return {
				hasInnerBlocks: blocks.length > 0,
				wasBlockJustInserted:
					// eslint-disable-next-line @typescript-eslint/ban-ts-comment
					// @ts-ignore method exists but not typed
					select( blockEditorStore ).wasBlockJustInserted( clientId ),
			};
		},
		[ clientId ]
	);

	const innerBlocksProps = useInnerBlocksProps( blockProps, {
		template: wasBlockJustInserted ? template : undefined,
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

	if ( hasInnerBlocks || wasBlockJustInserted ) {
		return <div { ...innerBlocksProps } />;
	}

	return (
		<div { ...blockProps }>
			<Disabled>
				<LegacyProductDetailsPreview hideTabTitle={ true } />
			</Disabled>
		</div>
	);
};

export default Edit;
