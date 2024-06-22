/**
 * External dependencies
 */
import { useDebounce } from '@wordpress/compose';
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
	__experimentalToggleGroupControl as ToggleGroupControl,
	__experimentalToggleGroupControlOption as ToggleGroupControlOption,
} from '@wordpress/components';
import { Icon, close } from '@wordpress/icons';

/**
 * Internal dependencies
 */
import type { BlockAttributes } from './types';
import SizeControl from './components/size-control';

export const Edit = ( {
	attributes,
	setAttributes,
}: BlockEditProps< BlockAttributes > ) => {
	const { navigationStyle, buttonStyle, iconSize } = attributes;
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

	buttonBlockStyles.forEach( ( style: { name: string; label: string } ) => {
		if ( style.name === 'link' ) return;
		buttonStyles.push( { value: style.name, label: style.label } );
	} );

	const setIconSize = useDebounce( ( value: string ) => {
		setAttributes( {
			iconSize: value,
		} );
	}, 50 );

	return (
		<nav
			className={ clsx(
				'wc-block-product-filters-overlay-navigation-wrapper',
				`is-style-${ buttonStyle }`,
				{
					'wp-block-button': buttonStyle !== 'link',
				}
			) }
		>
			<div { ...innerBlocksProps }>
				{ navigationStyle !== 'icon' && (
					<span>{ __( 'Close', 'woocommerce' ) }</span>
				) }
				{ navigationStyle !== 'label' && (
					<Icon
						fill="currentColor"
						icon={ close }
						style={ {
							width: iconSize || '1rem',
							height: iconSize || '1rem',
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
								value: 'full',
							},
							{
								label: __( 'Label only', 'woocommerce' ),
								value: 'label',
							},
							{
								label: __( 'Icon only', 'woocommerce' ),
								value: 'icon',
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
							{ buttonStyles.map( ( style ) => (
								<ToggleGroupControlOption
									key={ style.value }
									label={ style.label }
									value={ style.value }
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

					<SizeControl
						label={ __( 'Icon size', 'woocommerce' ) }
						onChange={ ( numericSize: number, unit: string ) =>
							setIconSize( `${ numericSize }${ unit }` )
						}
						value={ iconSize }
						units={ [
							{ value: 'px', label: 'px', default: 16 },
							{ value: 'rem', label: 'rem', default: 1 },
							{ value: 'em', label: 'em', default: 1 },
						] }
					/>
				</PanelBody>
			</InspectorControls>
		</nav>
	);
};
