/**
 * External dependencies
 */
import { addFilter } from '@wordpress/hooks';
import { Block } from '@wordpress/blocks';

/**
 * Set supports.email = true for the blocks that are supported in the block email editor.
 */
export function setEmailBlockSupport() {
	addFilter(
		'blocks.registerBlockType',
		'woocommerce-email-editor/supports-email',
		( settings: Block, name ) => {
			const ALLOWED_BLOCK_TYPES = new Set( [
				'core/button',
				'core/buttons',
				'core/column',
				'core/columns',
				'core/group',
				'core/heading',
				'core/image',
				'core/list',
				'core/list-item',
				'core/paragraph',
				'core/quote',
				'core/spacer',
				'core/social-link',
				'core/social-links',
			] );

			if ( ALLOWED_BLOCK_TYPES.has( name ) ) {
				return {
					...settings,
					supports: {
						...settings.supports,
						email: true,
					},
				};
			}

			return settings;
		}
	);
}
