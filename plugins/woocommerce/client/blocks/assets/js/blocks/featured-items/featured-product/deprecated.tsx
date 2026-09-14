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
	showPrice?: boolean;
	[ key: string ]: unknown;
}

// Version 1: Migration from legacy showDesc/showPrice attributes to inner blocks
const v1 = {
	attributes: {
		...metadata.attributes,
		showDesc: {
			type: 'boolean',
			default: true,
		},
		showPrice: {
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
		const { editMode, showDesc, showPrice, ...otherAttributes } =
			attributes;

		// This padding was applied via the styles in inner sections of the block.
		// Now that they are inner blocks, we are porting this padding to their attributes.
		const V1_PADDING_BOTTOM = '16px';

		// We check if these legacy attributes are explicitly set to false, because
		// the default value is true (i.e. `undefined` meant `true`).
		if ( showPrice !== false ) {
			innerBlocks.unshift(
				createBlock( 'woocommerce/product-price', {
					style: {
						spacing: {
							padding: {
								bottom: V1_PADDING_BOTTOM,
							},
						},
					},
					textAlign: 'center',
				} )
			);
		}

		if ( showDesc !== false ) {
			innerBlocks.unshift(
				createBlock( 'woocommerce/product-summary', {
					showDescriptionIfEmpty: true,
					summaryLength: 80,
					style: {
						typography: {
							textAlign: 'center',
						},
					},
				} )
			);
		}
		innerBlocks.unshift(
			createBlock( 'core/post-title', {
				level: 2,
				isLink: false,
				textAlign: 'center',
				__woocommerceNamespace:
					'woocommerce/product-collection/product-title',
			} )
		);

		return [ otherAttributes, innerBlocks ];
	},
};

export default [ v1 ];
