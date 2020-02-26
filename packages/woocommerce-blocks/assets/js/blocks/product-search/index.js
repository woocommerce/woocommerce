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
	title: __( 'Product Search', 'woocommerce' ),
	icon: {
		src: <IconProductSearch />,
		foreground: '#96588a',
	},
	category: 'woocommerce',
	keywords: [ __( 'WooCommerce', 'woocommerce' ) ],
	description: __(
		'Help visitors find your products.',
		'woocommerce'
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
			default: __( 'Search', 'woocommerce' ),
			source: 'text',
			selector: 'label',
		},

		/**
		 * Search field placeholder.
		 */
		placeholder: {
			type: 'string',
			default: __( 'Search products...', 'woocommerce' ),
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
							'woocommerce'
						) }
						initialOpen
					>
						<ToggleControl
							label={ __(
								'Show search field label',
								'woocommerce'
							) }
							help={
								hasLabel
									? __(
											'Label is visible.',
											'woocommerce'
									  )
									: __(
											'Label is hidden.',
											'woocommerce'
									  )
							}
							checked={ hasLabel }
							onChange={ () =>
								setAttributes( { hasLabel: ! hasLabel } )
							}
						/>
					</PanelBody>
				</InspectorControls>
				<Block { ...props } isEditor={ true } />
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
