/**
 * External dependencies
 */
import { useBlockProps, InnerBlocks } from '@wordpress/block-editor';
import { useEditorContext } from '@woocommerce/base-context';
import { getBlockTypes } from '@wordpress/blocks';

/**
 * Internal dependencies
 */

const EXCLUDED_BLOCKS: readonly string[] = [
	'woocommerce/mini-cart',
	'woocommerce/single-product',
	'core/post-template',
	'core/comment-template',
	'core/query-pagination',
	'core/comments-query-loop',
	'core/post-comments-form',
	'core/post-comments-link',
	'core/post-comments-count',
	'core/comments-pagination',
	'core/post-navigation-link',
];

export const Edit = (): JSX.Element => {
	const blockProps = useBlockProps();
	const { currentView } = useEditorContext();
	const allowedBlocks = getBlockTypes()
		.filter( ( block ) => {
			if ( EXCLUDED_BLOCKS.includes( block.name ) ) {
				return false;
			}

			// Exclude child blocks of EXCLUDED_BLOCKS.
			if (
				block.parent &&
				block.parent.filter( ( value ) =>
					EXCLUDED_BLOCKS.includes( value )
				).length > 0
			) {
				return false;
			}

			return true;
		} )
		.map( ( { name } ) => name );

	return (
		<div
			{ ...blockProps }
			hidden={
				currentView !== 'woocommerce/empty-mini-cart-contents-block'
			}
		>
			<InnerBlocks
				allowedBlocks={ allowedBlocks }
				renderAppender={ InnerBlocks.ButtonBlockAppender }
			/>
		</div>
	);
};

export const Save = (): JSX.Element => {
	return (
		<div { ...useBlockProps.save() }>
			<InnerBlocks.Content />
		</div>
	);
};
