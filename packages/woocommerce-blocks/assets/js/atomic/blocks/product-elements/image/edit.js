/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Disabled, PanelBody, ToggleControl } from '@wordpress/components';
import { InspectorControls } from '@wordpress/block-editor';
import { createInterpolateElement } from 'wordpress-element';
import ToggleButtonControl from '@woocommerce/editor-components/toggle-button-control';
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
					title={ __( 'Content', 'woo-gutenberg-products-block' ) }
				>
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
					<ToggleControl
						label={ __(
							'Show On-Sale Badge',
							'woo-gutenberg-products-block'
						) }
						help={ __(
							'Overlay a "sale" badge if the product is on-sale.',
							'woo-gutenberg-products-block'
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
								'woo-gutenberg-products-block'
							) }
							value={ saleBadgeAlign }
							options={ [
								{
									label: __(
										'Left',
										'woo-gutenberg-products-block'
									),
									value: 'left',
								},
								{
									label: __(
										'Center',
										'woo-gutenberg-products-block'
									),
									value: 'center',
								},
								{
									label: __(
										'Right',
										'woo-gutenberg-products-block'
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
							'woo-gutenberg-products-block'
						) }
						help={ createInterpolateElement(
							__(
								'Product image cropping can be modified in the <a>Customizer</a>.',
								'woo-gutenberg-products-block'
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
									'woo-gutenberg-products-block'
								),
								value: 'full-size',
							},
							{
								label: __(
									'Cropped',
									'woo-gutenberg-products-block'
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
		'Choose a product to display its image.',
		'woo-gutenberg-products-block'
	),
} )( Edit );
