/**
 * External dependencies
 */
import {
	InnerBlocks,
	InspectorControls,
	useBlockProps,
} from '@wordpress/block-editor';
import { BlockEditProps, InnerBlockTemplate } from '@wordpress/blocks';
import { PanelBody, ToggleControl } from '@wordpress/components';
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
	const showFilterDrawer = attributes.showFilterDrawer !== false;
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
			'is-filter-drawer-disabled': ! showFilterDrawer,
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
	} else if ( showFilterDrawer ) {
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
					<ToggleControl
						label={ __(
							'Use drawer on small screens',
							'woocommerce'
						) }
						help={
							showFilterDrawer
								? __(
										'Shoppers tap the button to open filters.',
										'woocommerce'
								  )
								: __(
										'Filters are shown directly on the page.',
										'woocommerce'
								  )
						}
						checked={ showFilterDrawer }
						onChange={ ( value ) =>
							setAttributes( { showFilterDrawer: value } )
						}
					/>
				</PanelBody>
			</InspectorControls>
			<div { ...blockProps }>{ filtersContent }</div>
		</>
	);
};
