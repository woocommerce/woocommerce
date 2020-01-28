/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { registerBlockType } from '@wordpress/blocks';
import Gridicon from 'gridicons';
import { Fragment } from '@wordpress/element';
import { Disabled, PanelBody, ToggleControl } from '@wordpress/components';
import { InspectorControls } from '@wordpress/block-editor';
import ToggleButtonControl from '@woocommerce/block-components/toggle-button-control';
import { ProductImage } from '@woocommerce/atomic-components/product';
import { previewProducts } from '@woocommerce/resource-previews';

/**
 * Internal dependencies
 */
import sharedConfig from '../shared-config';

const blockConfig = {
	title: __( 'Product Image', 'woocommerce' ),
	description: __(
		'Display the main product image',
		'woocommerce'
	),
	icon: {
		src: <Gridicon icon="image" />,
		foreground: '#96588a',
	},
	attributes: {
		product: {
			type: 'object',
			default: previewProducts[ 0 ],
		},
		productLink: {
			type: 'boolean',
			default: true,
		},
		showSaleBadge: {
			type: 'boolean',
			default: true,
		},
		saleBadgeAlign: {
			type: 'string',
			default: 'right',
		},
	},
	edit( props ) {
		const { attributes, setAttributes } = props;
		const { productLink, showSaleBadge, saleBadgeAlign } = attributes;

		return (
			<Fragment>
				<InspectorControls>
					<PanelBody
						title={ __(
							'Content',
							'woocommerce'
						) }
					>
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
						<ToggleControl
							label={ __(
								'Show On-Sale Badge',
								'woocommerce'
							) }
							help={ __(
								'Overlay a "sale" badge if the product is on-sale.',
								'woocommerce'
							) }
							checked={ showSaleBadge }
							onChange={ () =>
								setAttributes( {
									showSaleBadge: ! showSaleBadge,
								} )
							}
						/>
						{ showSaleBadge && (
							<ToggleButtonControl
								label={ __(
									'Sale Badge Alignment',
									'woocommerce'
								) }
								value={ saleBadgeAlign }
								options={ [
									{
										label: __(
											'Left',
											'woocommerce'
										),
										value: 'left',
									},
									{
										label: __(
											'Center',
											'woocommerce'
										),
										value: 'center',
									},
									{
										label: __(
											'Right',
											'woocommerce'
										),
										value: 'right',
									},
								] }
								onChange={ ( value ) =>
									setAttributes( { saleBadgeAlign: value } )
								}
							/>
						) }
					</PanelBody>
				</InspectorControls>
				<Disabled>
					<ProductImage
						product={ attributes.product }
						productLink={ productLink }
						showSaleBadge={ showSaleBadge }
						saleBadgeAlign={ saleBadgeAlign }
					/>
				</Disabled>
			</Fragment>
		);
	},
};

registerBlockType( 'woocommerce/product-image', {
	...sharedConfig,
	...blockConfig,
} );
