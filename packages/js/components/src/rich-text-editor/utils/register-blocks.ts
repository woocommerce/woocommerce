/**
 * External dependencies
 */
import { BlockInstance } from '@wordpress/blocks';
import {
	// eslint-disable-next-line @typescript-eslint/ban-ts-comment
	// @ts-ignore We need this to import the block modules for registration.
	__experimentalGetCoreBlocks,
	registerCoreBlocks as wpRegisterCoreBlocks,
} from '@wordpress/block-library';

export const PARAGRAPH_BLOCK_ID = 'core/paragraph';
export const HEADING_BLOCK_ID = 'core/heading';
export const LIST_BLOCK_ID = 'core/list';
export const LIST_ITEM_BLOCK_ID = 'core/list-item';
export const QUOTE_BLOCK_ID = 'core/quote';
export const IMAGE_BLOCK_ID = 'core/image';
export const VIDEO_BLOCK_ID = 'core/video';

const ALLOWED_CORE_BLOCKS = [
	PARAGRAPH_BLOCK_ID,
	HEADING_BLOCK_ID,
	LIST_BLOCK_ID,
	LIST_ITEM_BLOCK_ID,
	QUOTE_BLOCK_ID,
	IMAGE_BLOCK_ID,
	VIDEO_BLOCK_ID,
];

const registerCoreBlocks = () => {
	const coreBlocks = __experimentalGetCoreBlocks();
	const blocks = coreBlocks.filter( ( block: BlockInstance ) =>
		ALLOWED_CORE_BLOCKS.includes( block.name )
	);

	// eslint-disable-next-line @typescript-eslint/ban-ts-comment
	// @ts-ignore An argument is allowed to specify which blocks to register.
	wpRegisterCoreBlocks( blocks );
};

export const registerBlocks = () => {
	registerCoreBlocks();
};
