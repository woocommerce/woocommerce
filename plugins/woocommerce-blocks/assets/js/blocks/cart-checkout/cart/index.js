/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { InnerBlocks } from '@wordpress/block-editor';
import { registerBlockType } from '@wordpress/blocks';
import { Icon, cart } from '@woocommerce/icons';

/**
 * Internal dependencies
 */
import Editor from './edit';
import { example } from './example';
import './style.scss';

/**
 * Register and run the Cart block.
 */
const settings = {
	title: __( 'Cart', 'woo-gutenberg-products-block' ),
	icon: {
		src: <Icon srcElement={ cart } />,
		foreground: '#96588a',
	},
	category: 'woocommerce',
	keywords: [ __( 'WooCommerce', 'woo-gutenberg-products-block' ) ],
	description: __( 'Shopping cart.', 'woo-gutenberg-products-block' ),
	supports: {
		align: [ 'wide', 'full' ],
		html: false,
		multiple: false,
	},
	example,
	attributes: {},

	/**
	 * Renders the edit view for a block.
	 *
	 * @param {Object} props Props to pass to block.
	 */
	edit( props ) {
		return <Editor { ...props } />;
	},

	/**
	 * Block content is rendered in PHP, not via save function.
	 */
	save() {
		return (
			<div className="is-loading">
				<InnerBlocks.Content />
			</div>
		);
	},
};

if ( process.env.WOOCOMMERCE_BLOCKS_PHASE === 'experimental' ) {
	registerBlockType( 'woocommerce/cart', settings );
}
