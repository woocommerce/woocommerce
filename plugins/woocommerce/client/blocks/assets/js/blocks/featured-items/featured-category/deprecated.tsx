/**
 * External dependencies
 */
import { BlockInstance, createBlock } from '@wordpress/blocks';
import { InnerBlocks } from '@wordpress/block-editor';

/**
 * Internal dependencies
 */
import metadata from './block.json';

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

		// Conditionally add category description if showDesc was true
		if ( showDesc ) {
			innerBlocks.unshift(
				createBlock( 'woocommerce/category-description', {
					textAlign: 'center',
				} )
			);
		}

		// Always add category title as first inner block
		innerBlocks.unshift(
			createBlock( 'woocommerce/category-title', {
				level: 2,
				isLink: false,
				textAlign: 'center',
			} )
		);

		return [ otherAttributes, innerBlocks ];
	},
};

export default [ v1 ];
