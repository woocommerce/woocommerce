/**
 * External dependencies
 */
import {
	InnerBlocks,
	useBlockProps,
	useInnerBlocksProps,
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
						onChange={ ( value: 'fullscreen' | 'drawer' ) => {
							setAttributes( {
								overlayStyle: value,
							} );
						} }
						help={
							attributes.overlayStyle === 'fullscreen'
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
							value={ 'fullscreen' }
							label={ __( 'Full-Screen', 'woocommerce' ) }
						/>
						<ToggleGroupControlOption
							value={ 'drawer' }
							label={ __( 'Drawer', 'woocommerce' ) }
						/>
					</ToggleGroupControl>
					{ attributes.overlayStyle === 'drawer' && (
						<ToggleGroupControl
							className="wc-block-editor-product-filters-overlay__overlay-position-toggle"
							isBlock={ true }
							value={ attributes.overlayPosition }
							label={ __( 'POSITION', 'woocommerce' ) }
							onChange={ ( value: 'left' | 'right' ) => {
								setAttributes( {
									overlayPosition: value,
								} );
							} }
						>
							<ToggleGroupControlOption
								value={ 'left' }
								label={ __( 'Left', 'woocommerce' ) }
							/>
							<ToggleGroupControlOption
								value={ 'right' }
								label={ __( 'Right', 'woocommerce' ) }
							/>
						</ToggleGroupControl>
					) }

					{ attributes.overlayStyle === 'drawer' ? (
						<img
							className="wc-block-editor-product-filters-overlay__drawer-image"
							src={
								attributes.overlayPosition === 'left'
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

export const Save = () => {
	const blockProps = useBlockProps.save();
	const innerBlocksProps = useInnerBlocksProps.save( blockProps );
	return <div { ...innerBlocksProps } />;
};
