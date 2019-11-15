/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { registerBlockType } from '@wordpress/blocks';
import { Fragment } from 'react';
import { Disabled, PanelBody, ToggleControl } from '@wordpress/components';
import { InspectorControls } from '@wordpress/block-editor';
import { ProductTitle } from '@woocommerce/atomic-components/product';
import { previewProducts } from '@woocommerce/resource-previews';
import HeadingToolbar from '@woocommerce/block-components/heading-toolbar';

/**
 * Internal dependencies
 */
import sharedConfig from '../shared-config';

const blockConfig = {
	title: __( 'Product Title', 'woo-gutenberg-products-block' ),
	description: __(
		'Display the name of a product.',
		'woo-gutenberg-products-block'
	),
	icon: {
		src: 'heading',
		foreground: '#96588a',
	},
	attributes: {
		product: {
			type: 'object',
			default: previewProducts[ 0 ],
		},
		level: {
			type: 'number',
			default: 2,
		},
		productLink: {
			type: 'boolean',
			default: true,
		},
	},
	edit( props ) {
		const { attributes, setAttributes } = props;
		const { level, productLink } = attributes;

		return (
			<Fragment>
				<InspectorControls>
					<PanelBody
						title={ __(
							'Content',
							'woo-gutenberg-products-block'
						) }
					>
						<p>{ __( 'Level', 'woo-gutenberg-products-block' ) }</p>
						<HeadingToolbar
							isCollapsed={ false }
							minLevel={ 2 }
							maxLevel={ 7 }
							selectedLevel={ level }
							onChange={ ( newLevel ) =>
								setAttributes( { level: newLevel } )
							}
						/>
						<ToggleControl
							label={ __(
								'Link to Product Page',
								'woo-gutenberg-products-block'
							) }
							help={ __(
								'Links the image to the single product listing.',
								'woo-gutenberg-products-block'
							) }
							checked={ productLink }
							onChange={ () =>
								setAttributes( {
									productLink: ! productLink,
								} )
							}
						/>
					</PanelBody>
				</InspectorControls>
				<Disabled>
					<ProductTitle
						headingLevel={ level }
						productLink={ productLink }
						product={ attributes.product }
					/>
				</Disabled>
			</Fragment>
		);
	},
};

registerBlockType( 'woocommerce/product-title', {
	...sharedConfig,
	...blockConfig,
} );
