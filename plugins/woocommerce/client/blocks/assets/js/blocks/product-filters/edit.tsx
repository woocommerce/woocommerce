/* eslint-disable @wordpress/no-unsafe-wp-apis */
/**
 * External dependencies
 */
import {
	InnerBlocks,
	InspectorControls,
	useBlockProps,
} from '@wordpress/block-editor';
import { BlockEditProps, InnerBlockTemplate } from '@wordpress/blocks';
import {
	PanelBody,
	__experimentalToggleGroupControl as ToggleGroupControl,
	__experimentalToggleGroupControlOption as ToggleGroupControlOption,
} from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { Icon, close } from '@wordpress/icons';
import { useState } from '@wordpress/element';
import { filterThreeLines } from '@woocommerce/icons';
import { getSetting } from '@woocommerce/settings';
import clsx from 'clsx';

/**
 * Internal dependencies
 */
import './editor.scss';
import { type BlockAttributes, type OverlayMenu } from './types';
import { getColorsFromBlockSupports } from './utils/get-colors-from-block-supports';
import { getOverlayMenu } from './utils/overlay-menu';
import { presetToCssVariable } from './utils/preset-to-css-variable';

const TEMPLATE: InnerBlockTemplate[] = [
	[
		'core/heading',
		{
			level: 2,
			content: __( 'Filters', 'woocommerce' ),
			style: {
				margin: { top: '0', bottom: '0' },
				spacing: { margin: { top: '0', bottom: '0' } },
			},
		},
	],
	[ 'woocommerce/product-filter-active' ],
	[ 'woocommerce/product-filter-price' ],
	[ 'woocommerce/product-filter-rating' ],
	[ 'woocommerce/product-filter-attribute' ],
	[ 'woocommerce/product-filter-taxonomy' ],
	[ 'woocommerce/product-filter-status' ],
];

export const Edit = ( props: BlockEditProps< BlockAttributes > ) => {
	const { attributes, setAttributes } = props;
	const { isPreview } = attributes;
	const overlayMenu = getOverlayMenu( attributes );
	const hasOverlay = overlayMenu !== 'never';
	const isOverlayAlways = overlayMenu === 'always';
	const [ isOpen, setIsOpen ] = useState( false );

	const globalColors = getSetting< { background?: string; text?: string } >(
		'globalStylesColors',
		{}
	);
	const colors = getColorsFromBlockSupports( attributes );

	const blockGap = (
		attributes as unknown as Record<
			string,
			Record< string, Record< string, string > >
		>
	 )?.style?.spacing?.blockGap;

	const blockProps = useBlockProps( {
		className: clsx( 'wc-block-product-filters', {
			'is-overlay-opened': isOpen,
			'is-filter-drawer-disabled': ! hasOverlay,
			'is-overlay-always': isOverlayAlways,
		} ),
		style: {
			'--wc-product-filters-background-color':
				colors.backgroundColor || globalColors.background || undefined,
			'--wc-product-filters-text-color':
				colors.textColor || globalColors.text || undefined,
			'--wc-product-filter-block-spacing': blockGap
				? presetToCssVariable( blockGap )
				: undefined,
		},
	} );

	let filtersContent: JSX.Element;

	if ( isPreview ) {
		filtersContent = (
			<div className="wc-block-product-filters__overlay-content">
				<InnerBlocks templateLock={ false } template={ TEMPLATE } />
			</div>
		);
	} else if ( hasOverlay ) {
		filtersContent = (
			<>
				<button
					className="wc-block-product-filters__open-overlay"
					onClick={ () => setIsOpen( ! isOpen ) }
				>
					<Icon icon={ filterThreeLines } />
					<span>{ __( 'Filter products', 'woocommerce' ) }</span>
				</button>

				<div className="wc-block-product-filters__overlay">
					<div className="wc-block-product-filters__overlay-wrapper">
						<div
							className="wc-block-product-filters__overlay-dialog"
							role="dialog"
						>
							<header className="wc-block-product-filters__overlay-header">
								<button
									className="wc-block-product-filters__close-overlay"
									onClick={ () => setIsOpen( ! isOpen ) }
								>
									<span>
										{ __( 'Close', 'woocommerce' ) }
									</span>
									<Icon icon={ close } />
								</button>
							</header>
							<div className="wc-block-product-filters__overlay-content">
								<InnerBlocks
									templateLock={ false }
									template={ TEMPLATE }
								/>
							</div>
							<footer className="wc-block-product-filters__overlay-footer">
								<button
									className="wc-block-product-filters__apply wp-block-button__link wp-element-button"
									onClick={ () => setIsOpen( ! isOpen ) }
								>
									<span>
										{ __( 'Apply', 'woocommerce' ) }
									</span>
								</button>
							</footer>
						</div>
					</div>
				</div>
			</>
		);
	} else {
		filtersContent = (
			<div className="wc-block-product-filters__content">
				<InnerBlocks templateLock={ false } template={ TEMPLATE } />
			</div>
		);
	}

	return (
		<>
			<InspectorControls>
				<PanelBody title={ __( 'Settings', 'woocommerce' ) }>
					<ToggleGroupControl
						__next40pxDefaultSize
						__nextHasNoMarginBottom
						label={ __( 'Overlay menu', 'woocommerce' ) }
						aria-label={ __(
							'Configure overlay menu',
							'woocommerce'
						) }
						value={ overlayMenu }
						help={ __(
							'Collapses filters into a menu icon that opens an overlay.',
							'woocommerce'
						) }
						onChange={ ( value ) =>
							setAttributes( {
								overlayMenu: value as OverlayMenu,
								showFilterDrawer: undefined,
							} )
						}
						isBlock
					>
						<ToggleGroupControlOption
							value="never"
							label={ __( 'Off', 'woocommerce' ) }
						/>
						<ToggleGroupControlOption
							value="mobile"
							label={ __( 'Mobile', 'woocommerce' ) }
						/>
						<ToggleGroupControlOption
							value="always"
							label={ __( 'Always', 'woocommerce' ) }
						/>
					</ToggleGroupControl>
				</PanelBody>
			</InspectorControls>
			<div { ...blockProps }>{ filtersContent }</div>
		</>
	);
};
