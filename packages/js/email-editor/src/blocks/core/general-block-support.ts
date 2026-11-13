/**
 * External dependencies
 */
import {
	Block as WPBlock,
	BlockSupports as WPBlockSupports,
	getBlockTypes,
} from '@wordpress/blocks';
import domReady from '@wordpress/dom-ready';
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

// Block styles the email renderer supports: a list of style names, or `true` for all of them.
const BLOCK_STYLES_TO_PRESERVE: Record< string, string[] | true > = {
	'core/separator': [ 'default', 'wide', 'dots' ],
	'core/social-links': true,
};

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
 * Remove block styles the email renderer doesn’t support (see BLOCK_STYLES_TO_PRESERVE)
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
		const preserved = BLOCK_STYLES_TO_PRESERVE[ blockName ];

		if ( preserved === true ) {
			return;
		}
		const blockStyles = select( 'core/blocks' ).getBlockStyles( blockName );
		if ( ! Array.isArray( blockStyles ) || blockStyles?.length === 0 ) {
			return;
		}

		blockStyles
			.filter(
				( blockStyle ) => ! preserved?.includes( blockStyle.name )
			)
			.forEach( ( blockStyle ) => {
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
