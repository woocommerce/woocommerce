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
import {
	DEFAULT_ALLOWED_CONTROLS,
	INNER_BLOCKS_TEMPLATE,
	QUERY_DEFAULT_ATTRIBUTES,
} from '../constants';

const VARIATION_NAME = 'woocommerce/product-query';

if ( isExperimentalBuild() ) {
	registerBlockVariation( 'core/query', {
		name: VARIATION_NAME,
		title: __( 'Product Query', 'woo-gutenberg-products-block' ),
		isActive: ( blockAttributes ) =>
			blockAttributes.namespace === VARIATION_NAME,
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
			namespace: VARIATION_NAME,
		},
		// Gutenberg doesn't support this type yet, discussion here:
		// https://github.com/WordPress/gutenberg/pull/43632
		// eslint-disable-next-line @typescript-eslint/ban-ts-comment
		// @ts-ignore
		allowControls: DEFAULT_ALLOWED_CONTROLS,
		innerBlocks: INNER_BLOCKS_TEMPLATE,
		scope: [ 'block', 'inserter' ],
	} );
}
