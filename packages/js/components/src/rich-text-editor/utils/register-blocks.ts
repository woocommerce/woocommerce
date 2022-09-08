/**
 * External dependencies
 */
import { addFilter } from '@wordpress/hooks';
import { BlockInstance, getBlockTypes } from '@wordpress/blocks';
import {
	// eslint-disable-next-line @typescript-eslint/ban-ts-comment
	// @ts-ignore We need this to import the block modules for registration.
	__experimentalGetCoreBlocks,
	registerCoreBlocks as wpRegisterCoreBlocks,
} from '@wordpress/block-library';

export const PARAGRAPH_BLOCK_ID = 'core/paragraph';
export const HEADING_BLOCK_ID = 'core/heading';
export const LIST_BLOCK_ID = 'core/list';
export const QUOTE_BLOCK_ID = 'core/quote';

const registrationFilter = ( settings: BlockInstance, name: string ) => {
	switch ( name ) {
		case PARAGRAPH_BLOCK_ID:
		case HEADING_BLOCK_ID:
		case LIST_BLOCK_ID:
			return {
				...settings,
				attributes: {
					...settings.attributes,
					placeholder: {
						// Don't display a placeholder for new blocks.
						default: ' ',
					},
				},
			};

		default:
			return settings;
	}
};

// Confirming if blocks are registered is mostly useful for HMR in development,
// but also if there is an unexpected re-run of the registration code in a single
// page load, no warnings will fire.
export const blockIsRegistered = ( blockName: string ) => {
	const registeredBlocks = getBlockTypes();
	return !! registeredBlocks.find( ( block ) => block.name === blockName );
};

const ALLOWED_CORE_BLOCKS = [
	PARAGRAPH_BLOCK_ID,
	HEADING_BLOCK_ID,
	LIST_BLOCK_ID,
	QUOTE_BLOCK_ID,
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
	addFilter(
		'blocks.registerBlockType',
		'rich-text-editor/block-registration',
		registrationFilter
	);

	registerCoreBlocks();
};
