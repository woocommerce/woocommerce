/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import classNames from 'classnames';
import { InnerBlocks } from '@wordpress/block-editor';
import { registerBlockType } from '@wordpress/blocks';
import { Icon, cart } from '@woocommerce/icons';
import {
	IS_SHIPPING_CALCULATOR_ENABLED,
	IS_SHIPPING_COST_HIDDEN,
} from '@woocommerce/block-settings';

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
	attributes: {
		isShippingCalculatorEnabled: {
			type: 'boolean',
			default: IS_SHIPPING_CALCULATOR_ENABLED,
		},
		isShippingCostHidden: {
			type: 'boolean',
			default: IS_SHIPPING_COST_HIDDEN,
		},
	},

	/**
	 * Renders the edit view for a block.
	 *
	 * @param {Object} props Props to pass to block.
	 */
	edit( props ) {
		return <Editor { ...props } />;
	},

	/**
	 * Save the props to post content.
	 */
	save( { attributes } ) {
		const {
			className,
			isShippingCalculatorEnabled,
			isShippingCostHidden,
		} = attributes;
		const data = {
			'data-isshippingcalculatorenabled': isShippingCalculatorEnabled,
			'data-isshippingcosthidden': isShippingCostHidden,
		};
		return (
			<div
				className={ classNames( 'is-loading', className ) }
				{ ...data }
			>
				<InnerBlocks.Content />
			</div>
		);
	},
};

if ( process.env.WOOCOMMERCE_BLOCKS_PHASE === 'experimental' ) {
	registerBlockType( 'woocommerce/cart', settings );
}
