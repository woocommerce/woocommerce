/**
 * External dependencies
 */
import { useSelect } from '@wordpress/data';
import {
	useBlockProps,
	useInnerBlocksProps,
	InspectorControls,
} from '@wordpress/block-editor';
import { BlockEditProps, store as blocksStore } from '@wordpress/blocks';
import { __ } from '@wordpress/i18n';
import clsx from 'clsx';
import {
	PanelBody,
	RadioControl,
	SelectControl,
	RangeControl,
	__experimentalToggleGroupControl as ToggleGroupControl,
	__experimentalToggleGroupControlOption as ToggleGroupControlOption,
} from '@wordpress/components';
import { Icon, close } from '@wordpress/icons';

/**
 * Internal dependencies
 */
import type {
	BlockAttributes,
	BlockContext,
	BlockVariationTriggerType,
} from './types';
import { default as productFiltersIcon } from '../product-filters/icon';
import { BlockOverlayAttribute as ProductFiltersBlockOverlayAttribute } from '../product-filters/constants';
import './editor.scss';

const OverlayNavigationLabel = ( {
	variation,
}: {
	variation: BlockVariationTriggerType;
} ) => {
	let label = __( 'Close', 'woocommerce' );
	if ( variation === 'open-overlay' ) {
		label = __( 'Filters', 'woocommerce' );
	}

	return <span>{ label }</span>;
};

const OverlayNavigationIcon = ( {
	variation,
	iconSize,
	style,
}: {
	variation: BlockVariationTriggerType;
	iconSize: number | undefined;
	style: BlockAttributes[ 'style' ];
} ) => {
	let icon = close;

	if ( variation === 'open-overlay' ) {
		icon = productFiltersIcon();
	}

	return (
		<Icon
			fill="currentColor"
			icon={ icon }
			style={ {
				width: iconSize || style?.typography?.fontSize || '16px',
				height: iconSize || style?.typography?.fontSize || '16px',
			} }
		/>
	);
};

const OverlayNavigationContent = ( {
	variation,
	iconSize,
	style,
	navigationStyle,
}: {
	variation: BlockVariationTriggerType;
	iconSize: BlockAttributes[ 'iconSize' ];
	style: BlockAttributes[ 'style' ];
	navigationStyle: BlockAttributes[ 'navigationStyle' ];
} ) => {
	const overlayNavigationLabel = (
		<OverlayNavigationLabel variation={ variation } />
	);
	const overlayNavigationIcon = (
		<OverlayNavigationIcon
			variation={ variation }
			iconSize={ iconSize }
			style={ style }
		/>
	);

	if ( navigationStyle === 'label-and-icon' ) {
		if ( variation === 'open-overlay' ) {
			return (
				<>
					{ overlayNavigationIcon }
					{ overlayNavigationLabel }
				</>
			);
		} else if ( variation === 'close-overlay' ) {
			return (
				<>
					{ overlayNavigationLabel }
					{ overlayNavigationIcon }
				</>
			);
		}
	} else if ( navigationStyle === 'label-only' ) {
		return overlayNavigationLabel;
	} else if ( navigationStyle === 'icon-only' ) {
		return overlayNavigationIcon;
	}

	return null;
};

type BlockProps = BlockEditProps< BlockAttributes > & { context: BlockContext };

export const Edit = ( { attributes, setAttributes, context }: BlockProps ) => {
	const { navigationStyle, buttonStyle, iconSize, style, triggerType } =
		attributes;
	const { 'woocommerce/product-filters/overlay': productFiltersOverlayMode } =
		context;
	const blockProps = useBlockProps( {
		className: clsx( 'wc-block-product-filters-overlay-navigation', {
			'wp-block-button__link wp-element-button': buttonStyle !== 'link',
		} ),
	} );
	const {
		isWithinProductFiltersTemplatePart,
		isWithinProductFiltersOverlayTemplatePart,
	}: {
		isWithinProductFiltersTemplatePart: boolean;
		isWithinProductFiltersOverlayTemplatePart: boolean;
	} = useSelect( ( select ) => {
		// eslint-disable-next-line @typescript-eslint/ban-ts-comment
		// @ts-ignore
		const { getCurrentPostId, getCurrentPostType } =
			select( 'core/editor' );
		const currentPostId = getCurrentPostId< string >();
		const currentPostIdParts = currentPostId?.split( '//' );
		const currentPostType = getCurrentPostType< string >();
		let isProductFiltersTemplatePart = false;
		let isProductFiltersOverlayTemplatePart = false;

		if (
			currentPostType === 'wp_template_part' &&
			currentPostIdParts?.length > 1
		) {
			const [ , postId ] = currentPostIdParts;
			isProductFiltersTemplatePart = postId === 'product-filters';
			isProductFiltersOverlayTemplatePart =
				postId === 'product-filters-overlay';
		}

		return {
			isWithinProductFiltersTemplatePart: isProductFiltersTemplatePart,
			isWithinProductFiltersOverlayTemplatePart:
				isProductFiltersOverlayTemplatePart,
		};
	} );

	const shouldHideBlock = () => {
		if ( triggerType === 'open-overlay' ) {
			if (
				productFiltersOverlayMode ===
				ProductFiltersBlockOverlayAttribute.NEVER
			) {
				return true;
			}

			if ( isWithinProductFiltersTemplatePart ) {
				return true;
			}

			if ( isWithinProductFiltersOverlayTemplatePart ) {
				return true;
			}
		}

		return false;
	};
	// We need useInnerBlocksProps because Gutenberg only applies layout classes
	// to parent block. We don't have any inner blocks but we want to use the
	// layout controls.
	const innerBlocksProps = useInnerBlocksProps( blockProps );

	const buttonBlockStyles = useSelect(
		( select ) => select( blocksStore ).getBlockStyles( 'core/button' ),
		[]
	);

	if ( shouldHideBlock() ) {
		return null;
	}

	const buttonStyles = [
		{ value: 'link', label: __( 'Link', 'woocommerce' ) },
	];

	buttonBlockStyles.forEach(
		( buttonBlockStyle: { name: string; label: string } ) => {
			if ( buttonBlockStyle.name === 'link' ) return;
			buttonStyles.push( {
				value: buttonBlockStyle.name,
				label: buttonBlockStyle.label,
			} );
		}
	);

	return (
		<nav
			className={ clsx(
				'wc-block-product-filters-overlay-navigation__wrapper',
				`is-style-${ buttonStyle }`,
				{
					'wp-block-button': buttonStyle !== 'link',
				}
			) }
		>
			<div { ...innerBlocksProps }>
				<OverlayNavigationContent
					variation={ triggerType }
					iconSize={ iconSize }
					navigationStyle={ navigationStyle }
					style={ style }
				/>
			</div>
			<InspectorControls group="styles">
				<PanelBody title={ __( 'Style', 'woocommerce' ) }>
					<RadioControl
						selected={ navigationStyle }
						options={ [
							{
								label: __( 'Label and icon', 'woocommerce' ),
								value: 'label-and-icon',
							},
							{
								label: __( 'Label only', 'woocommerce' ),
								value: 'label-only',
							},
							{
								label: __( 'Icon only', 'woocommerce' ),
								value: 'icon-only',
							},
						] }
						onChange={ (
							value: BlockAttributes[ 'navigationStyle' ]
						) =>
							setAttributes( {
								navigationStyle: value,
							} )
						}
					/>

					{ buttonStyles.length <= 3 && (
						<ToggleGroupControl
							label={ __( 'Button', 'woocommerce' ) }
							value={ buttonStyle }
							isBlock
							onChange={ (
								value: BlockAttributes[ 'buttonStyle' ]
							) =>
								setAttributes( {
									buttonStyle: value,
								} )
							}
						>
							{ buttonStyles.map( ( option ) => (
								<ToggleGroupControlOption
									key={ option.value }
									label={ option.label }
									value={ option.value }
								/>
							) ) }
						</ToggleGroupControl>
					) }
					{ buttonStyles.length > 3 && (
						<SelectControl
							label={ __( 'Button', 'woocommerce' ) }
							value={ buttonStyle }
							options={ buttonStyles }
							onChange={ (
								value: BlockAttributes[ 'buttonStyle' ]
							) =>
								setAttributes( {
									buttonStyle: value,
								} )
							}
						/>
					) }

					{ navigationStyle !== 'label-only' && (
						<RangeControl
							className="wc-block-product-filters-overlay-navigation__icon-size-control"
							label={ __( 'Icon Size', 'woocommerce' ) }
							value={ iconSize }
							onChange={ ( newSize: number ) => {
								setAttributes( { iconSize: newSize } );
							} }
							min={ 0 }
							max={ 300 }
						/>
					) }
				</PanelBody>
			</InspectorControls>
		</nav>
	);
};
