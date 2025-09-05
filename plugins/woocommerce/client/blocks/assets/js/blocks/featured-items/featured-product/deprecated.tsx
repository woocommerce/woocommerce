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
	[ key: string ]: any;
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
		// If the block has showDesc or showPrice attributes as boolean values, it's a legacy block
		// and it should be migrated to use inner blocks instead.
		return (
			typeof attributes.showDesc === 'boolean' ||
			typeof attributes.showPrice === 'boolean'
		);
	},
	migrate: ( attributes: BlockAttributes, innerBlocks: BlockInstance[] ) => {
		const { showDesc, showPrice, ...otherAttributes } = attributes;

		innerBlocks.unshift(
			createBlock( 'core/post-title', {
				level: 2,
				isLink: false,
				__woocommerceNamespace:
					'woocommerce/product-query/product-title',
			} )
		);

		if ( showPrice ) {
			innerBlocks.push( createBlock( 'woocommerce/product-price' ) );
		}

		if ( showDesc ) {
			innerBlocks.push(
				createBlock( 'woocommerce/product-summary', {
					showDescriptionIfEmpty: true,
					summaryLength: 80,
				} )
			);
		}

		return [ otherAttributes, innerBlocks ];
	},
};

export default [ v1 ];
