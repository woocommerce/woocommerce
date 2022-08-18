/**
 * External dependencies
 */
import { isExperimentalBuild } from '@woocommerce/block-settings';
import { registerBlockVariation } from '@wordpress/blocks';
import { Icon } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { sparkles } from '@wordpress/icons';

/**
 * Internal dependencies
 */
import { QUERY_DEFAULT_ATTRIBUTES } from '../constants';

if ( isExperimentalBuild() ) {
	registerBlockVariation( 'core/query', {
		name: 'woocommerce/product-query',
		title: __( 'Product Query', 'woo-gutenberg-products-block' ),
		isActive: ( attributes ) => {
			return (
				attributes?.__woocommerceVariationProps?.name ===
				'product-query'
			);
		},
		icon: {
			src: (
				<Icon
					icon={ sparkles }
					className="wc-block-editor-components-block-icon wc-block-editor-components-block-icon--sparkles"
				/>
			),
		},
		attributes: {
			...QUERY_DEFAULT_ATTRIBUTES,
			__woocommerceVariationProps: {
				name: 'product-query',
			},
		},
		innerBlocks: [
			[
				'core/post-template',
				{},
				[ [ 'core/post-title' ], [ 'core/post-featured-image' ] ],
			],
			[ 'core/query-pagination' ],
			[ 'core/query-no-results' ],
		],
		scope: [ 'block', 'inserter' ],
	} );
}
