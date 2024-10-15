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
import { ENABLE_CLICK_CLASS } from '../auto-block-preview-event-listener';

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
				'core/group',
				{
					__noBlocksPlaceholder: true,
					className: ENABLE_CLICK_CLASS,
					style: {
						dimensions: {
							minHeight: '60vh',
						},
						color: {
							background: '#FAFAFA',
						},
						spacing: {
							padding: {
								top: '40px',
								bottom: '40px',
							},
						},
					},
					layout: {
						type: 'flex',
						orientation: 'vertical',
						justifyContent: 'center',
						verticalAlignment: 'center',
					},
				},
				[
					createBlock( 'core/image', {
						url: NoBlocks,
						align: 'center',
						className: ENABLE_CLICK_CLASS,
					} ),
					createBlock(
						'core/group',
						{
							layout: {
								type: 'constrained',
								contentSize: '350px',
							},
							className: ENABLE_CLICK_CLASS,
						},
						[
							createBlock( 'core/paragraph', {
								className: ENABLE_CLICK_CLASS,
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
							createBlock( 'core/button', {
								align: 'center',
								fontFamily: 'inter',
								className: `is-style-outline ${ ENABLE_CLICK_CLASS } no-blocks-insert-pattern-button`,
								style: {
									border: {
										radius: '2px',
										color: '#007cba',
										width: '1px',
									},
									color: {
										text: '#007cba',
									},
								},
								text: __( 'Add patterns', 'woocommerce' ),
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
