/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { registerBlockType } from '@wordpress/blocks';
import { InspectorControls } from '@wordpress/editor';
import { PanelBody, ToggleControl } from '@wordpress/components';
import { Fragment } from '@wordpress/element';
import { IconProductSearch } from '@woocommerce/block-components/icons';

/**
 * Internal dependencies
 */
import './style.scss';
import './editor.scss';
import Block from './block.js';

registerBlockType( 'woocommerce/product-search', {
	title: __( 'Product Search', 'woo-gutenberg-products-block' ),
	icon: {
		src: <IconProductSearch />,
		foreground: '#96588a',
	},
	category: 'woocommerce',
	keywords: [ __( 'WooCommerce', 'woo-gutenberg-products-block' ) ],
	description: __(
		'Help visitors find your products.',
		'woo-gutenberg-products-block'
	),
	supports: {
		align: [ 'wide', 'full' ],
	},
	example: {
		attributes: {
			hasLabel: true,
		},
	},
	attributes: {
		/**
		 * Whether to show the field label.
		 */
		hasLabel: {
			type: 'boolean',
			default: true,
		},

		/**
		 * Search field label.
		 */
		label: {
			type: 'string',
			default: __( 'Search', 'woo-gutenberg-products-block' ),
			source: 'text',
			selector: 'label',
		},

		/**
		 * Search field placeholder.
		 */
		placeholder: {
			type: 'string',
			default: __( 'Search products...', 'woo-gutenberg-products-block' ),
			source: 'attribute',
			selector: 'input.wc-block-product-search__field',
			attribute: 'placeholder',
		},

		/**
		 * Store the instance ID.
		 */
		formId: {
			type: 'string',
			default: '',
		},
	},

	/**
	 * Renders and manages the block.
	 */
	edit( props ) {
		const { attributes, setAttributes } = props;
		const { hasLabel } = attributes;
		return (
			<Fragment>
				<InspectorControls key="inspector">
					<PanelBody
						title={ __(
							'Content',
							'woo-gutenberg-products-block'
						) }
						initialOpen
					>
						<ToggleControl
							label={ __(
								'Show search field label',
								'woo-gutenberg-products-block'
							) }
							help={
								hasLabel
									? __(
											'Label is visible.',
											'woo-gutenberg-products-block'
									  )
									: __(
											'Label is hidden.',
											'woo-gutenberg-products-block'
									  )
							}
							checked={ hasLabel }
							onChange={ () =>
								setAttributes( { hasLabel: ! hasLabel } )
							}
						/>
					</PanelBody>
				</InspectorControls>
				<Block { ...props } isPreview />
			</Fragment>
		);
	},

	/**
	 * Save the props to post content.
	 */
	save( attributes ) {
		return (
			<div>
				<Block { ...attributes } />
			</div>
		);
	},
} );
