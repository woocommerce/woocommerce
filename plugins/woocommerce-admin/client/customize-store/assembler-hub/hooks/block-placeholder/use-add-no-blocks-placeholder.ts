/**
 * External dependencies
 */
import { BlockInstance } from '@wordpress/blocks';
import { useEffect } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
/**
 * Internal dependencies
 */
import NoBlocks from '../../../assets/images/no-blocks.png';
import { DISABLE_CLICK_CLASS } from '../auto-block-preview-event-listener';

/**
 * The scope of this variable is limited to the block-placeholder folder.
 *
 */
export let noBlocksPlaceholderClientId: string | null;

export const useAddNoBlocksPlaceholder = ( {
	blocks,
	createBlock,
	insertBlock,
	removeBlock,
}: {
	blocks: BlockInstance[];
	createBlock: (
		name: string,
		attributes: Record< string, unknown >,
		innerBlocks?: BlockInstance[]
	) => BlockInstance;
	insertBlock: ( block: BlockInstance, index: number ) => void;
	removeBlock: ( clientId: string ) => void;
} ) => {
	useEffect( () => {
		if (
			blocks.length === 2 &&
			blocks.every( ( block ) => block.name === 'core/template-part' )
		) {
			const noBlocksBlock = createBlock(
				'core/cover',
				{
					url: '',
					customOverlayColor: '#FAFAFA',
					minHeight: '60vh',
					__noBlocksPlaceholder: true,
					className: DISABLE_CLICK_CLASS,
				},
				[
					createBlock( 'core/image', {
						url: NoBlocks,
						align: 'center',
					} ),
					createBlock(
						'core/group',
						{
							layout: {
								type: 'constrained',
								contentSize: '350px',
							},
						},
						[
							createBlock( 'core/paragraph', {
								align: 'center',
								fontFamily: 'inter',
								style: {
									color: {
										text: '#2F2F2F',
									},
								},
								content: __(
									'Add one or more of our homepage patterns to create a page that welcomes shoppers.',
									'woocommerce'
								),
							} ),
						]
					),
				]
			);
			insertBlock( noBlocksBlock, 1 );
			noBlocksPlaceholderClientId = noBlocksBlock.clientId;
		}

		if ( blocks.length > 3 && noBlocksPlaceholderClientId ) {
			removeBlock( noBlocksPlaceholderClientId );
		}
	}, [ blocks, createBlock, insertBlock, removeBlock ] );
};
