/**
 * External dependencies
 */
import { BlockInstance } from '@wordpress/blocks';
import { useEffect } from '@wordpress/element';
/**
 * Internal dependencies
 */
import NoBlocks from '../../../assets/images/no-blocks.png';

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
					customOverlayColor: '#F6F7F7',
					minHeight: 800,
					__noBlocksPlaceholder: true,
				},
				[
					createBlock( 'core/image', {
						url: NoBlocks,
						align: 'center',
					} ),
					createBlock( 'core/paragraph', {
						align: 'center',
						fontFamily: 'inter',
						style: {
							color: {
								text: '#000000',
							},
						},
						content:
							'Unlock your creativity and populate your homepage by adding as many patterns as you like.',
					} ),
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
