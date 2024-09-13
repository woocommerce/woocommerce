/* eslint-disable @wordpress/no-unsafe-wp-apis */
/**
 * External dependencies
 */
import {
	InnerBlocks,
	useBlockProps,
	InspectorControls,
} from '@wordpress/block-editor';
import { BlockEditProps, InnerBlockTemplate } from '@wordpress/blocks';
import {
	PanelBody,
	__experimentalToggleGroupControlOption as ToggleGroupControlOption,
	__experimentalToggleGroupControl as ToggleGroupControl,
} from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { WC_BLOCKS_IMAGE_URL } from '@woocommerce/block-settings';

/**
 * Internal dependencies
 */
import type { ProductFiltersOverlayBlockAttributes } from './types';
import { BlockOverlayAttribute } from './constants';

const TEMPLATE: InnerBlockTemplate[] = [ [ 'woocommerce/product-filters' ] ];

export const Edit = ( {
	setAttributes,
	attributes,
}: BlockEditProps< ProductFiltersOverlayBlockAttributes > ) => {
	const blockProps = useBlockProps();

	return (
		<div { ...blockProps }>
			<InspectorControls group="styles">
				<PanelBody title={ __( 'Style', 'woocommerce' ) }>
					<ToggleGroupControl
						className="wc-block-editor-product-filters-overlay__overlay-style-toggle"
						isBlock={ true }
						value={ attributes.overlayStyle }
						onChange={ (
							value:
								| BlockOverlayAttribute.FULLSCREEN
								| BlockOverlayAttribute.DRAWER
						) => {
							setAttributes( {
								overlayStyle: value,
							} );
						} }
						help={
							attributes.overlayStyle ===
							BlockOverlayAttribute.FULLSCREEN
								? __(
										'The overlay will fill up the whole screen.',
										'woocommerce'
								  )
								: __(
										'The overlay will show on the left or right side of the screen (only on desktop).',
										'woocommerce'
								  )
						}
					>
						<ToggleGroupControlOption
							value={ BlockOverlayAttribute.FULLSCREEN }
							label={ __( 'Full-Screen', 'woocommerce' ) }
						/>
						<ToggleGroupControlOption
							value={ BlockOverlayAttribute.DRAWER }
							label={ __( 'Drawer', 'woocommerce' ) }
						/>
					</ToggleGroupControl>
					{ attributes.overlayStyle ===
						BlockOverlayAttribute.DRAWER && (
						<ToggleGroupControl
							className="wc-block-editor-product-filters-overlay__overlay-position-toggle"
							isBlock={ true }
							value={ attributes.overlayPosition }
							label={ __( 'POSITION', 'woocommerce' ) }
							onChange={ (
								value:
									| BlockOverlayAttribute.LEFT
									| BlockOverlayAttribute.RIGHT
							) => {
								setAttributes( {
									overlayPosition: value,
								} );
							} }
						>
							<ToggleGroupControlOption
								value={ BlockOverlayAttribute.LEFT }
								label={ __( 'Left', 'woocommerce' ) }
							/>
							<ToggleGroupControlOption
								value={ BlockOverlayAttribute.RIGHT }
								label={ __( 'Right', 'woocommerce' ) }
							/>
						</ToggleGroupControl>
					) }

					{ attributes.overlayStyle ===
					BlockOverlayAttribute.DRAWER ? (
						<img
							className="wc-block-editor-product-filters-overlay__drawer-image"
							src={
								attributes.overlayPosition ===
								BlockOverlayAttribute.LEFT
									? `${ WC_BLOCKS_IMAGE_URL }blocks/product-filters-overlay/overlay-drawer-left.svg`
									: `${ WC_BLOCKS_IMAGE_URL }blocks/product-filters-overlay/overlay-drawer-right.svg`
							}
							alt={ __(
								'Overlay drawer orientation',
								'woocommerce'
							) }
						/>
					) : (
						<img
							className="wc-block-editor-product-filters-overlay__drawer-image"
							src={ `${ WC_BLOCKS_IMAGE_URL }blocks/product-filters-overlay/overlay-drawer-fullscreen.svg` }
							alt={ __(
								'Overlay drawer orientation',
								'woocommerce'
							) }
						/>
					) }
				</PanelBody>
			</InspectorControls>
			<InnerBlocks templateLock={ false } template={ TEMPLATE } />
		</div>
	);
};
