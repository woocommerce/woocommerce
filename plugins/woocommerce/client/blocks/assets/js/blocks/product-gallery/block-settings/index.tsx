/**
 * External dependencies
 */
import { InspectorControls } from '@wordpress/block-editor';
import { __ } from '@wordpress/i18n';
import {
	ToggleControl,
	// eslint-disable-next-line @wordpress/no-unsafe-wp-apis
	__experimentalToolsPanel as ToolsPanel,
	// eslint-disable-next-line @wordpress/no-unsafe-wp-apis
	__experimentalToolsPanelItem as ToolsPanelItem,
} from '@wordpress/components';

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
			<ToolsPanel
				label={ __( 'Media Settings', 'woocommerce' ) }
				resetAll={ () => {
					setAttributes( {
						hoverZoom: true,
						fullScreenOnClick: true,
					} );
				} }
			>
				<ToolsPanelItem
					hasValue={ () => hoverZoom !== true }
					label={ __( 'Zoom while hovering', 'woocommerce' ) }
					onDeselect={ () => setAttributes( { hoverZoom: true } ) }
					isShownByDefault
				>
					<ToggleControl
						label={ __( 'Zoom while hovering', 'woocommerce' ) }
						help={ __(
							'While hovering the image in the viewer will zoom in by 30%.',
							'woocommerce'
						) }
						checked={ hoverZoom }
						onChange={ () =>
							setAttributes( {
								hoverZoom: ! hoverZoom,
							} )
						}
					/>
				</ToolsPanelItem>
				<ToolsPanelItem
					hasValue={ () => fullScreenOnClick !== true }
					label={ __( 'Open pop-up when clicked', 'woocommerce' ) }
					onDeselect={ () =>
						setAttributes( { fullScreenOnClick: true } )
					}
					isShownByDefault
				>
					<ToggleControl
						label={ __(
							'Open pop-up when clicked',
							'woocommerce'
						) }
						help={ __(
							'Clicking on the image in the viewer will open a full-screen gallery experience.',
							'woocommerce'
						) }
						checked={ fullScreenOnClick }
						onChange={ () =>
							setAttributes( {
								fullScreenOnClick: ! fullScreenOnClick,
							} )
						}
					/>
				</ToolsPanelItem>
			</ToolsPanel>
		</InspectorControls>
	);
};
