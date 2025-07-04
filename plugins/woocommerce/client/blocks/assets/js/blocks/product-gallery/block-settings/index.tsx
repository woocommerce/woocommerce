/**
 * External dependencies
 */
import { InspectorControls } from '@wordpress/block-editor';
import { PanelBody, ToggleControl } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import type { ProductGallerySettingsProps } from '../types';

export const ProductGalleryBlockSettings = ( {
	attributes,
	setAttributes,
}: ProductGallerySettingsProps ) => {
	const { hoverZoom, fullScreenOnClick } = attributes;
	return (
		<InspectorControls>
			<PanelBody title={ __( 'Media Settings', 'woocommerce' ) }>
				<ToggleControl
					label={ __( 'Zoom while hovering', 'woocommerce' ) }
					help={ __(
						'While hovering the large image will zoom in by 30%.',
						'woocommerce'
					) }
					checked={ hoverZoom }
					onChange={ () =>
						setAttributes( {
							hoverZoom: ! hoverZoom,
						} )
					}
				/>
				<ToggleControl
					label={ __( 'Open pop-up when clicked', 'woocommerce' ) }
					help={ __(
						'Clicking on the large image will open a full-screen gallery experience.',
						'woocommerce'
					) }
					checked={ fullScreenOnClick }
					onChange={ () =>
						setAttributes( {
							fullScreenOnClick: ! fullScreenOnClick,
						} )
					}
				/>
			</PanelBody>
		</InspectorControls>
	);
};
