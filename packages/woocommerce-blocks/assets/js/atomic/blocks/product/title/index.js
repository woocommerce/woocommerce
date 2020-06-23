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
	title: __( 'Product Title', 'woocommerce' ),
	description: __(
		'Display the name of a product.',
		'woocommerce'
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
		headingLevel: {
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
		const { headingLevel, productLink } = attributes;

		return (
			<Fragment>
				<InspectorControls>
					<PanelBody
						title={ __(
							'Content',
							'woocommerce'
						) }
					>
						<p>{ __( 'Level', 'woocommerce' ) }</p>
						<HeadingToolbar
							isCollapsed={ false }
							minLevel={ 2 }
							maxLevel={ 7 }
							selectedLevel={ headingLevel }
							onChange={ ( newLevel ) =>
								setAttributes( { headingLevel: newLevel } )
							}
						/>
						<ToggleControl
							label={ __(
								'Link to Product Page',
								'woocommerce'
							) }
							help={ __(
								'Links the image to the single product listing.',
								'woocommerce'
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
						headingLevel={ headingLevel }
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
