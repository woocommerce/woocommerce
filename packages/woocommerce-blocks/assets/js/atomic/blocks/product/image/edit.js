/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Disabled, PanelBody, ToggleControl } from '@wordpress/components';
import { InspectorControls } from '@wordpress/block-editor';
import ToggleButtonControl from '@woocommerce/block-components/toggle-button-control';

/**
 * Internal dependencies
 */
import Block from './block';

export default ( { attributes, setAttributes } ) => {
	const { productLink, showSaleBadge, saleBadgeAlign } = attributes;

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
				</PanelBody>
			</InspectorControls>
			<Disabled>
				<Block { ...attributes } />
			</Disabled>
		</>
	);
};
