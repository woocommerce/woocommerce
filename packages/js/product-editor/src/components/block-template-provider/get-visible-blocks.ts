/**
 * External dependencies
 */
import { BlockInstance } from '@wordpress/blocks';

export function getVisibleBlocks(
	blocks: BlockInstance[],
	hiddenBlockIds: string[]
) {
	const visibleBlocks: BlockInstance[] = [];

	blocks.forEach( ( block ) => {
		if ( hiddenBlockIds.includes( block.attributes._templateId ) ) {
			return;
		}
		const visibleInnerBlocks = getVisibleBlocks(
			block.innerBlocks,
			hiddenBlockIds
		);
		const visibleBlock = { ...block, innerBlocks: visibleInnerBlocks };
		visibleBlocks.push( visibleBlock );
	} );

	return visibleBlocks;
}
