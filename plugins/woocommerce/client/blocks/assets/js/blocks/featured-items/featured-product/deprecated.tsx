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
		// If the block has showDesc or showPrice attributes are not explicitly set to false,
		// it's a legacy block and it should be migrated to use inner blocks instead.
		return attributes.showDesc !== false || attributes.showPrice !== false;
	},
	migrate: ( attributes: BlockAttributes, innerBlocks: BlockInstance[] ) => {
		const { showDesc, showPrice, ...otherAttributes } = attributes;

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

		// We check if these legacy attributes are explicitly set to false, because
		// the default value is true (i.e. `undefined` meant `true`).
		if ( showPrice !== false ) {
			innerBlocks.unshift(
				createBlock( 'woocommerce/product-price', {
					textAlign: 'center',
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

		return [
			{
				...otherAttributes,
				showDesc: false,
				showPrice: false,
				__woocommerceBlockVersion: 2,
			},
			innerBlocks,
		];
	},
};

export default [ v1 ];
