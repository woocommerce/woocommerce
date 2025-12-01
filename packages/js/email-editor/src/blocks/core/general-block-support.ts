/**
 * External dependencies
 */
import {
	Block as WPBlock,
	BlockSupports as WPBlockSupports,
} from '@wordpress/blocks/index';
import domReady from '@wordpress/dom-ready';
import { getBlockTypes } from '@wordpress/blocks';
import { select } from '@wordpress/data';

/**
 * Internal dependencies
 */
import { updateBlockSettings } from '../../config-tools/block-config';
import { unregisterBlockStyleForEmail } from '../../config-tools/block-style';

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
function alterSupportConfiguration(): void {
	getBlockTypes().forEach( ( blockType: Block ) => {
		if ( blockType.supports?.shadow ) {
			updateBlockSettings( blockType.name, ( current ) => ( {
				...current,
				supports: {
					...current.supports,
					shadow: false,
				},
			} ) );
		}
	} );
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
		const blockStyles = select( 'core/blocks' ).getBlockStyles( blockName );
		if ( ! Array.isArray( blockStyles ) || blockStyles?.length === 0 ) {
			return;
		}

		blockStyles.forEach( ( blockStyle ) => {
			unregisterBlockStyleForEmail( blockName, blockStyle.name );
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
