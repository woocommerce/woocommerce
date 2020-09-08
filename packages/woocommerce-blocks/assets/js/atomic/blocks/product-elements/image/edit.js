/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Disabled, PanelBody, ToggleControl } from '@wordpress/components';
import { InspectorControls } from '@wordpress/block-editor';
import { __experimentalCreateInterpolateElement } from 'wordpress-element';
import ToggleButtonControl from '@woocommerce/block-components/toggle-button-control';
import { getAdminLink } from '@woocommerce/settings';

/**
 * Internal dependencies
 */
import Block from './block';
import withProductSelector from '../shared/with-product-selector';
import { BLOCK_TITLE, BLOCK_ICON } from './constants';

const Edit = ( { attributes, setAttributes } ) => {
	const {
		productLink,
		imageSizing,
		showSaleBadge,
		saleBadgeAlign,
	} = attributes;

	return (
		<>
			<InspectorControls>
				<PanelBody
					title={ __( 'Content', 'woocommerce' ) }
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
					<ToggleButtonControl
						label={ __(
							'Image Sizing',
							'woocommerce'
						) }
						help={ __experimentalCreateInterpolateElement(
							__(
								'Product image cropping can be modified in the <a>Customizer</a>.',
								'woocommerce'
							),
							{
								a: (
									// eslint-disable-next-line jsx-a11y/anchor-has-content
									<a
										href={ `${ getAdminLink(
											'customize.php'
										) }?autofocus[panel]=woocommerce&autofocus[section]=woocommerce_product_images` }
										target="_blank"
										rel="noopener noreferrer"
									/>
								),
							}
						) }
						value={ imageSizing }
						options={ [
							{
								label: __(
									'Full Size',
									'woocommerce'
								),
								value: 'full-size',
							},
							{
								label: __(
									'Cropped',
									'woocommerce'
								),
								value: 'cropped',
							},
						] }
						onChange={ ( value ) =>
							setAttributes( { imageSizing: value } )
						}
					/>
				</PanelBody>
			</InspectorControls>
			<Disabled>
				<Block { ...attributes } />
			</Disabled>
		</>
	);
};

export default withProductSelector( {
	icon: BLOCK_ICON,
	label: BLOCK_TITLE,
	description: __(
		"Choose a product to display it's image.",
		'woocommerce'
	),
} )( Edit );
