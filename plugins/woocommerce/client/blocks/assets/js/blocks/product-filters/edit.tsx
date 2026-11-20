/**
 * External dependencies
 */
import {
	InnerBlocks,
	InspectorControls,
	useBlockProps,
} from '@wordpress/block-editor';
import {
	BlockEditProps,
	InnerBlockTemplate,
	store as blocksStore,
} from '@wordpress/blocks';
import { useSelect } from '@wordpress/data';
import type { HTMLAttributes } from 'react';
import {
	BaseControl,
	Button,
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
import { usePreviewMode } from '@woocommerce/base-hooks';
import clsx from 'clsx';

/**
 * Internal dependencies
 */
import './editor.scss';
import { type BlockAttributes } from './types';
import { getColorsFromBlockSupports } from './utils/get-colors-from-block-supports';
import { getOverlayMode, isOverlayMode } from './utils/overlay-mode';
import {
	getOverlayButtonStyle,
	isOverlayButtonStyle,
} from './utils/overlay-button-style';
import { presetToCssVariable } from './utils/preset-to-css-variable';
import { getDefaultAttributeId } from './inner-blocks/attribute-filter/utils';

type ButtonStyle = { name: string; label?: string; isDefault?: boolean };

const NO_BUTTON_STYLES: ButtonStyle[] = [];

const DEFAULT_ATTRIBUTE_ID = getDefaultAttributeId();

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
	[
		'woocommerce/product-filter-attribute',
		// Persist the ID: a block attribute equal to its default isn't saved.
		DEFAULT_ATTRIBUTE_ID > 0 ? { attributeId: DEFAULT_ATTRIBUTE_ID } : {},
	],
	[ 'woocommerce/product-filter-taxonomy' ],
	[ 'woocommerce/product-filter-status' ],
];

export const Edit = ( props: BlockEditProps< BlockAttributes > ) => {
	const { attributes, setAttributes } = props;
	const isPreviewMode = usePreviewMode();
	const overlayMode = getOverlayMode( attributes );
	const hasOverlay = overlayMode !== 'off';
	const overlayPosition =
		attributes.overlayPosition === 'right' ? 'right' : 'left';
	const overlayButtonStyle = getOverlayButtonStyle( attributes );
	const isStyledButton = overlayButtonStyle !== 'plain';
	// Every core Button style, including the ones the theme adds.
	const buttonStyles: ButtonStyle[] = useSelect(
		( select ) =>
			(
				select( blocksStore ) as {
					getBlockStyles: (
						name: string
					) => ButtonStyle[] | undefined;
				}
			 ).getBlockStyles( 'core/button' ) ?? NO_BUTTON_STYLES,
		[]
	);
	const buttonStyleOptions = [
		{ value: 'plain', label: __( 'Plain', 'woocommerce' ) },
		...buttonStyles
			.filter( ( style: ButtonStyle ) =>
				isOverlayButtonStyle( style.name )
			)
			.map( ( style: ButtonStyle ) => ( {
				value: style.name,
				label: style.label || style.name,
			} ) ),
	];
	// Core's Button block does the same. A style from another theme keeps its
	// saved value, but the button looks like the default style, so the default
	// tile shows as selected.
	const activeButtonStyle = buttonStyleOptions.some(
		( option ) => option.value === overlayButtonStyle
	)
		? overlayButtonStyle
		: ( buttonStyles.find( ( style: ButtonStyle ) => style.isDefault )
				?.name ?? 'fill' );

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
			'has-styled-overlay-button': hasOverlay && isStyledButton,
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

	if ( isPreviewMode ) {
		filtersContent = (
			<div className="wc-block-product-filters__overlay-content">
				<InnerBlocks templateLock={ false } template={ TEMPLATE } />
			</div>
		);
	} else if ( hasOverlay ) {
		filtersContent = (
			<>
				{ /* Same markup as render() in PHP. Styles other than plain use
				core Button classes, so the theme's Button style applies. */ }
				<div
					className={ clsx(
						'wc-block-product-filters__open-overlay-wrapper',
						isStyledButton && [
							'wp-block-button',
							`is-style-${ overlayButtonStyle }`,
						]
					) }
				>
					<button
						type="button"
						className={ clsx(
							isStyledButton && [
								'wp-block-button__link',
								'wp-element-button',
							],
							'wc-block-product-filters__open-overlay'
						) }
						onClick={ () => setIsOpen( ! isOpen ) }
					>
						<Icon icon={ filterThreeLines } />
						<span>{ __( 'Filter products', 'woocommerce' ) }</span>
					</button>
				</div>

				<div className="wc-block-product-filters__overlay">
					<div className="wc-block-product-filters__overlay-wrapper">
						<section className="wc-block-product-filters__overlay-dialog">
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
						</section>
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
					{ overlayMode !== 'off' && (
						// Same tiles as core's block Styles panel, so the
						// picker looks like the Button block's own.
						<div
							role="group"
							aria-label={ __( 'Button style', 'woocommerce' ) }
						>
							<BaseControl.VisualLabel>
								{ __( 'Button style', 'woocommerce' ) }
							</BaseControl.VisualLabel>
							<div className="block-editor-block-styles__variants">
								{ buttonStyleOptions.map( ( option ) => (
									<Button
										__next40pxDefaultSize
										key={ option.value }
										variant="secondary"
										className={ clsx(
											'block-editor-block-styles__item',
											{
												'is-active':
													option.value ===
													activeButtonStyle,
											}
										) }
										aria-pressed={
											option.value === activeButtonStyle
										}
										onClick={ () =>
											setAttributes( {
												overlayButtonStyle:
													option.value,
											} )
										}
									>
										<span className="block-editor-block-styles__item-text">
											{ option.label }
										</span>
									</Button>
								) ) }
							</div>
						</div>
					) }
				</PanelBody>
			</InspectorControls>
			<div { ...blockProps }>{ filtersContent }</div>
		</>
	);
};
