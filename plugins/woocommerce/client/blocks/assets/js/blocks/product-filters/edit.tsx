/**
 * External dependencies
 */
import {
	InnerBlocks,
	InspectorControls,
	useBlockProps,
} from '@wordpress/block-editor';
import { BlockEditProps, InnerBlockTemplate } from '@wordpress/blocks';
import type { HTMLAttributes } from 'react';
import {
	PanelBody,
	// eslint-disable-next-line @wordpress/no-unsafe-wp-apis
	__experimentalToggleGroupControl as ToggleGroupControl,
	// eslint-disable-next-line @wordpress/no-unsafe-wp-apis
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
import { type BlockAttributes } from './types';
import { getColorsFromBlockSupports } from './utils/get-colors-from-block-supports';
import { getOverlayMode, isOverlayMode } from './utils/overlay-mode';
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
	const overlayMode = getOverlayMode( attributes );
	const hasOverlay = overlayMode !== 'off';
	const overlayPosition =
		attributes.overlayPosition === 'right' ? 'right' : 'left';
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
			'is-mobile-overlay': overlayMode === 'mobile',
			'is-overlay-right': hasOverlay && overlayPosition === 'right',
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
	} ) as HTMLAttributes< HTMLDivElement >;

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
						__nextHasNoMarginBottom
						__next40pxDefaultSize
						isBlock
						label={ __( 'Overlay', 'woocommerce' ) }
						help={ __(
							'When on, filters are hidden behind a button instead of showing on the page.',
							'woocommerce'
						) }
						value={ overlayMode }
						onChange={ ( value ) => {
							if ( isOverlayMode( value ) ) {
								setAttributes( { overlayMode: value } );
							}
						} }
					>
						<ToggleGroupControlOption
							value="off"
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
					{ overlayMode !== 'off' && (
						<ToggleGroupControl
							__nextHasNoMarginBottom
							__next40pxDefaultSize
							isBlock
							label={ __( 'Overlay position', 'woocommerce' ) }
							value={ overlayPosition }
							onChange={ ( value ) => {
								if ( value === 'left' || value === 'right' ) {
									setAttributes( {
										overlayPosition: value,
									} );
								}
							} }
						>
							<ToggleGroupControlOption
								value="left"
								label={ __( 'Left', 'woocommerce' ) }
							/>
							<ToggleGroupControlOption
								value="right"
								label={ __( 'Right', 'woocommerce' ) }
							/>
						</ToggleGroupControl>
					) }
				</PanelBody>
			</InspectorControls>
			<div { ...blockProps }>{ filtersContent }</div>
		</>
	);
};
