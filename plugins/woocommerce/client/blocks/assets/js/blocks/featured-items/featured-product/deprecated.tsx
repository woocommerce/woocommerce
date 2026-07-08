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
			createBlock( 'woocommerce/featured-product-title', {
				level: 2,
				isLink: false,
				textAlign: 'center',
			} )
		);

		return [ otherAttributes, innerBlocks ];
	},
};

// Version 2: Convert the legacy `core/post-title` inner block (used before the
// dedicated `woocommerce/featured-product-title` block existed) so its text
// edits stay decoupled from the underlying product.
const v2 = {
	save: () => <InnerBlocks.Content />,
	isEligible: (
		_attributes: BlockAttributes,
		innerBlocks: BlockInstance[]
	) => {
		return innerBlocks.some(
			( block ) => block.name === 'core/post-title'
		);
	},
	migrate: ( attributes: BlockAttributes, innerBlocks: BlockInstance[] ) => {
		const migratedInnerBlocks = innerBlocks.map( ( block ) => {
			if ( block.name === 'core/post-title' ) {
				return createBlock( 'woocommerce/featured-product-title', {
					level: block.attributes.level ?? 2,
					isLink: block.attributes.isLink ?? false,
					textAlign: block.attributes.textAlign ?? '',
				} );
			}

			return block;
		} );

		return [ attributes, migratedInnerBlocks ];
	},
};

export default [ v1, v2 ];
