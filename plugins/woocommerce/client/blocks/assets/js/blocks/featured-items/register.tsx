/**
 * External dependencies
 */
import { InnerBlocks } from '@wordpress/block-editor';
import { registerBlockType } from '@wordpress/blocks';
import { getSetting } from '@woocommerce/settings';
import type { FunctionComponent } from 'react';
import type { BlockConfiguration } from '@wordpress/blocks';

/**
 * Internal dependencies
 */
import { Edit } from './edit';

export function register(
	Block: FunctionComponent,
	example: { attributes: Record< string, unknown > },
	metadata: BlockConfiguration,
	settings: Partial< BlockConfiguration >
): void {
	const DEFAULT_SETTINGS = {
		attributes: {
			...metadata.attributes,
			/**
			 * A minimum height for the block.
			 *
			 * Note: if padding is increased, this way the inner content will never
			 * overflow, but instead will resize the container.
			 *
			 * It was decided to change this to make this block more in line with
			 * the “Cover” block.
			 */
			minHeight: {
				type: 'number',
				default: getSetting( 'defaultHeight', 500 ),
			},
		},
		supports: metadata.supports,
	};

	const DEFAULT_EXAMPLE = {
		attributes: {
			alt: '',
			contentAlign: 'center',
			dimRatio: 50,
			hasParallax: false,
			isRepeated: false,
			height: getSetting( 'defaultHeight', 500 ),
			mediaSrc: '',
			overlayColor: '#000000',
			showDesc: true,
		},
	};

	registerBlockType( metadata, {
		...DEFAULT_SETTINGS,
		example: {
			...DEFAULT_EXAMPLE,
			...example,
		},
		/**
		 * Renders and manages the block.
		 *
		 * @param {Object} props Props to pass to block.
		 */
		edit: Edit( Block ),
		/**
		 * Block content is rendered in PHP, not via save function.
		 */
		save: () => <InnerBlocks.Content />,
		...settings,
	} );
}
