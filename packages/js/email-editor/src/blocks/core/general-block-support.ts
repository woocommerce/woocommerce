/**
 * External dependencies
 */
import { addFilter } from '@wordpress/hooks';
import {
	Block as WPBlock,
	BlockSupports as WPBlockSupports,
} from '@wordpress/blocks/index';
import domReady from '@wordpress/dom-ready';
import {
	store as blocksStore,
	unregisterBlockStyle,
	getBlockTypes,
} from '@wordpress/blocks';
import { select } from '@wordpress/data';

// Extend the BlockSupports type to include shadow
// The shadow is not included in WP6.4 but it is in WP6.5
// We can remove it once we upgrade packages to WP6.5
type BlockSupports = WPBlockSupports & { shadow: boolean };
type Block = WPBlock & { supports?: BlockSupports };

// List of blocks that we want to support.
const BLOCK_STYLES_TO_PRESERVE = [ 'core/social-links' ];

/**
 * Disables Shadow Support for all blocks
 * Currently we are not able to read these styles in renderer
 */
function alterSupportConfiguration() {
	addFilter(
		'blocks.registerBlockType',
		'woocommerce-email-editor/block-support',
		( settings: Block ) => {
			if ( settings.supports?.shadow ) {
				return {
					...settings,
					supports: { ...settings.supports, shadow: false },
				};
			}
			return settings;
		}
	);
}

/**
 * Remove block styles for all blocks that are not in the BLOCK_STYLES_TO_PRESERVE array
 * We are removing these block styles because they might contain CSS not supported in the email editor renderer (or email clients).
 *
 * Block styles removed:
 * - Core block styles
 * - Block styles from plugins
 * - Block styles from themes
 * - Block styles from the user's custom styles
 */
function removeBlockStyles() {
	getBlockTypes().forEach( ( blockType ) => {
		const blockName = blockType.name;

		if ( BLOCK_STYLES_TO_PRESERVE.includes( blockName ) ) {
			// Skip block styles that are in the BLOCK_STYLES_TO_PRESERVE array
			return;
		}
		// @ts-expect-error Type not complete.
		const blockStyles = select( blocksStore ).getBlockStyles( blockName );
		if ( ! Array.isArray( blockStyles ) || blockStyles?.length === 0 ) {
			return;
		}
		blockStyles.forEach( ( blockStyle ) => {
			unregisterBlockStyle( blockName, blockStyle.name );
		} );
	} );
}

/**
 * Remove block styles for all blocks
 * See removeBlockStyles() for more details
 */
function removeBlockStylesFromAllBlocks() {
	domReady( removeBlockStyles );
}

export { alterSupportConfiguration, removeBlockStylesFromAllBlocks };
