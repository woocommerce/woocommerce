/**
 * External dependencies
 */
import { isExperimentalBuild } from '@woocommerce/block-settings';
import { registerBlockVariation } from '@wordpress/blocks';
import { __ } from '@wordpress/i18n';
import { Icon, percent } from '@wordpress/icons';

/**
 * Internal dependencies
 */
import { INNER_BLOCKS_TEMPLATE, QUERY_DEFAULT_ATTRIBUTES } from '../constants';

if ( isExperimentalBuild() ) {
	registerBlockVariation( 'core/query', {
		name: 'woocommerce/query-products-on-sale',
		title: __( 'Products on Sale', 'woo-gutenberg-products-block' ),
		isActive: ( blockAttributes ) =>
			blockAttributes?.__woocommerceVariationProps?.name ===
				'query-products-on-sale' ||
			blockAttributes?.__woocommerceVariationProps?.query?.onSale ===
				true,
		icon: {
			src: (
				<Icon
					icon={ percent }
					className="wc-block-editor-components-block-icon wc-block-editor-components-block-icon--percent"
				/>
			),
		},
		attributes: {
			...QUERY_DEFAULT_ATTRIBUTES,
			__woocommerceVariationProps: {
				name: 'query-products-on-sale',
				attributes: {
					query: {
						onSale: true,
					},
				},
			},
		},
		innerBlocks: INNER_BLOCKS_TEMPLATE,
		scope: [ 'block', 'inserter' ],
	} );
}
