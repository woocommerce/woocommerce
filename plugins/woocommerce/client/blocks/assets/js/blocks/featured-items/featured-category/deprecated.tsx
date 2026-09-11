/**
 * External dependencies
 */
import { BlockInstance, createBlock } from '@wordpress/blocks';
import { InnerBlocks } from '@wordpress/block-editor';

/**
 * Internal dependencies
 */
import metadata from './block.json';
import { migrateToCover } from './migrate';

interface BlockAttributes {
	showDesc?: boolean;
	[ key: string ]: unknown;
}

// Version 1: Migration from legacy showDesc attribute to inner blocks
const v1 = {
	attributes: {
		...metadata.attributes,
		showDesc: {
			type: 'boolean',
			default: true,
		},
	},
	save: () => <InnerBlocks.Content />,
	isEligible: ( attributes: BlockAttributes ) => {
		// If the block has editMode attribute as boolean value, it's a legacy block
		// and it should be migrated to use inner blocks instead.
		return typeof attributes.editMode === 'boolean';
	},
	migrate: ( attributes: BlockAttributes, innerBlocks: BlockInstance[] ) => {
		const { editMode, showDesc, ...otherAttributes } = attributes;

		// This padding was applied via the styles in inner sections of the block.
		// Now that they are inner blocks, we are porting this padding to their attributes.
		const V1_PADDING_BOTTOM = '16px';

		// Conditionally add category description if showDesc was true
		if ( showDesc ) {
			innerBlocks.unshift(
				createBlock( 'woocommerce/category-description', {
					textAlign: 'center',
					style: {
						padding: {
							bottom: V1_PADDING_BOTTOM,
						},
					},
				} )
			);
		}

		// Always add category title as first inner block
		innerBlocks.unshift(
			createBlock( 'woocommerce/category-title', {
				level: 2,
				isLink: false,
				textAlign: 'center',
				style: {
					padding: {
						bottom: V1_PADDING_BOTTOM,
					},
				},
			} )
		);

		return [ otherAttributes, innerBlocks ];
	},
};

// Both pre-Cover formats share the same saved markup. Migrate them directly;
// Gutenberg does not chain v1's migration into this one.
const v2 = {
	attributes: {
		...v1.attributes,
		editMode: { type: 'boolean' },
		height: { type: 'number' },
		style: { type: 'object' },
		textColor: { type: 'string' },
		fontSize: { type: 'string' },
		lineHeight: { type: 'string' },
	},
	supports: {
		interactivity: {
			clientNavigation: true,
		},
		align: [ 'wide', 'full' ],
		ariaLabel: true,
		color: {
			background: true,
			text: true,
		},
		html: false,
		filter: {
			duotone: true,
		},
		spacing: {
			padding: true,
			__experimentalDefaultControls: {
				padding: true,
			},
			__experimentalSkipSerialization: true,
		},
		__experimentalBorder: {
			color: true,
			radius: true,
			width: true,
			__experimentalDefaultControls: {
				color: true,
				radius: true,
				width: true,
			},
			__experimentalSkipSerialization: true,
		},
	},
	save: v1.save,
	isEligible: ( attributes: Record< string, unknown > ) =>
		attributes.layout !== 'cover',
	migrate: migrateToCover,
};

export default [
	v2,
	{
		...v1,
		// The parser can check older eligible versions after v2 has migrated.
		isEligible: (
			attributes: BlockAttributes,
			innerBlocks: BlockInstance[]
		) =>
			! innerBlocks.some( ( block ) => block.name === 'core/cover' ) &&
			v1.isEligible( attributes ),
	},
];
