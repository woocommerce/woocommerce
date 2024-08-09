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
import type { BlockAttributes } from './types';
import './editor.scss';

export const Edit = ( {
	attributes,
	setAttributes,
}: BlockEditProps< BlockAttributes > ) => {
	const { navigationStyle, buttonStyle, iconSize, style } = attributes;
	const blockProps = useBlockProps( {
		className: clsx( 'wc-block-product-filters-overlay-navigation', {
			'wp-block-button__link wp-element-button': buttonStyle !== 'link',
		} ),
	} );
	// We need useInnerBlocksProps because Gutenberg only applies layout classes
	// to parent block. We don't have any inner blocks but we want to use the
	// layout controls.
	const innerBlocksProps = useInnerBlocksProps( blockProps );

	const buttonBlockStyles = useSelect(
		( select ) => select( blocksStore ).getBlockStyles( 'core/button' ),
		[]
	);

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
				{ navigationStyle !== 'icon-only' && (
					<span>{ __( 'Close', 'woocommerce' ) }</span>
				) }
				{ navigationStyle !== 'label-only' && (
					<Icon
						fill="currentColor"
						icon={ close }
						style={ {
							width:
								iconSize ||
								style?.typography?.fontSize ||
								'16px',
							height:
								iconSize ||
								style?.typography?.fontSize ||
								'16px',
						} }
					/>
				) }
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
