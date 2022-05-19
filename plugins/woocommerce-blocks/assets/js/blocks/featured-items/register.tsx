/**
 * External dependencies
 */
import { FunctionComponent } from 'react';
import { InnerBlocks } from '@wordpress/block-editor';
import { BlockConfiguration, registerBlockType } from '@wordpress/blocks';
import { getSetting } from '@woocommerce/settings';
import { isFeaturePluginBuild } from '@woocommerce/block-settings';

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
				default: getSetting( 'default_height', 500 ),
			},
		},
		supports: {
			...metadata.supports,
			color: {
				background: metadata.supports?.color?.background,
				text: metadata.supports?.color?.text,
				...( isFeaturePluginBuild() && {
					__experimentalDuotone:
						metadata.supports?.color?.__experimentalDuotone,
				} ),
			},
			spacing: {
				padding: metadata.supports?.spacing?.padding,
				...( isFeaturePluginBuild() && {
					__experimentalDefaultControls: {
						padding:
							metadata.supports?.spacing
								?.__experimentalDefaultControls,
					},
					__experimentalSkipSerialization:
						metadata.supports?.spacing
							?.__experimentalSkipSerialization,
				} ),
			},
			...( isFeaturePluginBuild() && {
				__experimentalBorder: metadata?.supports?.__experimentalBorder,
			} ),
		},
	};

	const DEFAULT_EXAMPLE = {
		attributes: {
			alt: '',
			contentAlign: 'center',
			dimRatio: 50,
			editMode: false,
			hasParallax: false,
			isRepeated: false,
			height: getSetting( 'default_height', 500 ),
			mediaSrc: '',
			overlayColor: '#000000',
			showDesc: true,
		},
	};

	registerBlockType( metadata, {
		...DEFAULT_SETTINGS,
		example: {
			...DEFAULT_EXAMPLE,
			example,
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
