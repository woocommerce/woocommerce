/**
 * External dependencies
 */
import { useSelect } from '@wordpress/data';
import { store as blockEditorStore } from '@wordpress/block-editor';

const supporterBlocksPrefix = [ 'core/', 'woocommerce/' ];

export const useGetUnsupportedBlocks = ( clientId: string ) => {
	return useSelect(
		( select ) => {
			const { getClientIdsOfDescendants, getBlockName } =
				select( blockEditorStore );
			const blocks = {};
			getClientIdsOfDescendants( clientId ).forEach(
				( descendantClientId: string ) => {
					const blockName = getBlockName( descendantClientId );

					if ()
					if ( ! blockName.startsWith( 'core/' ) ) {
						blocks.hasBlocksFromPlugins = true;
					} else if ( blockName === 'core/post-content' ) {
						blocks.hasPostContentBlock = true;
					}
				}
			);
			blocks.hasUnsupportedBlocks =
				blocks.hasBlocksFromPlugins || blocks.hasPostContentBlock;
			return blocks;
		},
		[ clientId ]
	);
};
