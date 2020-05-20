/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { registerBlockType } from '@wordpress/blocks';
import classNames from 'classnames';

/**
 * Internal dependencies
 */
import edit from './edit.js';
import { IconMoney } from '../../components/icons';

registerBlockType( 'woocommerce/price-filter', {
	title: __( 'Filter Products by Price', 'woocommerce' ),
	icon: {
		src: <IconMoney />,
		foreground: '#96588a',
	},
	category: 'woocommerce',
	keywords: [ __( 'WooCommerce', 'woocommerce' ) ],
	description: __(
		'Display a slider to filter products in your store by price.',
		'woocommerce'
	),
	supports: {
		multiple: false,
	},
	example: {},
	attributes: {
		showInputFields: {
			type: 'boolean',
			default: true,
		},
		showFilterButton: {
			type: 'boolean',
			default: false,
		},
		heading: {
			type: 'string',
			default: __( 'Filter by price', 'woocommerce' ),
		},
		headingLevel: {
			type: 'number',
			default: 3,
		},
	},

	edit,

	/**
	 * Save the props to post content.
	 */
	save( { attributes } ) {
		const {
			className,
			showInputFields,
			showFilterButton,
			heading,
			headingLevel,
		} = attributes;
		const data = {
			'data-showinputfields': showInputFields,
			'data-showfilterbutton': showFilterButton,
			'data-heading': heading,
			'data-heading-level': headingLevel,
		};
		return (
			<div
				className={ classNames( 'is-loading', className ) }
				{ ...data }
			>
				<span
					aria-hidden
					className="wc-block-product-categories__placeholder"
				/>
			</div>
		);
	},
} );
