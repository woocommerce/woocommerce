/**
 * External dependencies
 */
import { isFeaturePluginBuild } from '@woocommerce/block-settings';
import { registerBlockVariation } from '@wordpress/blocks';
import { Icon } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { stacks } from '@woocommerce/icons';

/**
 * Internal dependencies
 */
import {
	DEFAULT_ALLOWED_CONTROLS,
	INNER_BLOCKS_TEMPLATE,
	QUERY_DEFAULT_ATTRIBUTES,
	QUERY_LOOP_ID,
} from '../constants';

const VARIATION_NAME = 'woocommerce/product-query';

// This is a feature flag to enable the custom inherit Global Query implementation.
// This is not intended to be a permanent feature flag, but rather a temporary.
// It is also necessary to enable this feature flag on the PHP side: `src/BlockTypes/ProductQuery.php:49`.
// https://github.com/woocommerce/woocommerce-blocks/pull/7382
const isCustomInheritGlobalQueryImplementationEnabled = false;

if ( isFeaturePluginBuild() ) {
	registerBlockVariation( QUERY_LOOP_ID, {
		description: __(
			'A block that displays a selection of products in your store.',
			'woo-gutenberg-products-block'
		),
		name: VARIATION_NAME,
		/* translators: “Products“ is the name of the block. */
		title: __( 'Products (Beta)', 'woo-gutenberg-products-block' ),
		isActive: ( blockAttributes ) =>
			blockAttributes.namespace === VARIATION_NAME,
		icon: {
			src: (
				<Icon
					icon={ stacks }
					className="wc-block-editor-components-block-icon wc-block-editor-components-block-icon--stacks"
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
		allowedControls: isCustomInheritGlobalQueryImplementationEnabled
			? [ ...DEFAULT_ALLOWED_CONTROLS, 'wooInherit' ]
			: DEFAULT_ALLOWED_CONTROLS,
		innerBlocks: INNER_BLOCKS_TEMPLATE,
		scope: [ 'block', 'inserter' ],
	} );
}
