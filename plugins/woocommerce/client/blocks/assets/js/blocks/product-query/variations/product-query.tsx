/**
 * External dependencies
 */
import { stacks } from '@woocommerce/icons';
import { registerBlockVariation } from '@wordpress/blocks';
import { Icon } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import {
	DEFAULT_ALLOWED_CONTROLS,
	INNER_BLOCKS_TEMPLATE,
	PRODUCT_QUERY_VARIATION_NAME,
	QUERY_DEFAULT_ATTRIBUTES,
	QUERY_LOOP_ID,
} from '../constants';

registerBlockVariation( QUERY_LOOP_ID, {
	description: __(
		'A block that displays a selection of products in your store.',
		'woocommerce'
	),
	name: PRODUCT_QUERY_VARIATION_NAME,
	/* translators: "Products" is the name of the block. */
	title: __( 'Products (Deprecated)', 'woocommerce' ),
	isActive: ( blockAttributes ) =>
		blockAttributes.namespace === PRODUCT_QUERY_VARIATION_NAME,
	icon: (
		<Icon
			icon={ stacks }
			className="wc-block-editor-components-block-icon wc-block-editor-components-block-icon--stacks"
		/>
	),
	attributes: {
		...QUERY_DEFAULT_ATTRIBUTES,
		namespace: PRODUCT_QUERY_VARIATION_NAME,
	},
	// Gutenberg doesn't support this type yet, discussion here:
	// https://github.com/WordPress/gutenberg/pull/43632
	// eslint-disable-next-line @typescript-eslint/ban-ts-comment
	// @ts-ignore
	allowedControls: DEFAULT_ALLOWED_CONTROLS,
	innerBlocks: INNER_BLOCKS_TEMPLATE,
	scope: [],
} );
