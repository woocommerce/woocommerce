/**
 * External dependencies
 */
import { __, sprintf } from '@wordpress/i18n';
import clsx from 'clsx';
import { Icon, closeSmall, arrowRight, arrowDown } from '@wordpress/icons';
import { Label } from '@woocommerce/blocks-components';
import { ToolbarGroup, ToolbarButton } from '@wordpress/components';
import { getBlockSupport } from '@wordpress/blocks';
import {
	InspectorControls,
	useBlockProps,
	useInnerBlocksProps,
	BlockControls,
	withColors,
	// @ts-expect-error - no types.
	// eslint-disable-next-line @wordpress/no-unsafe-wp-apis
	__experimentalColorGradientSettingsDropdown as ColorGradientSettingsDropdown,
	// @ts-expect-error - no types.
	// eslint-disable-next-line @wordpress/no-unsafe-wp-apis
	__experimentalUseMultipleOriginColorsAndGradients as useMultipleOriginColorsAndGradients,
} from '@wordpress/block-editor';

/**
 * Internal dependencies
 */
import { EditProps } from './types';
import { getColorClasses, getColorVars } from './utils';

const Edit = ( props: EditProps ): JSX.Element => {
	const colorGradientSettings = useMultipleOriginColorsAndGradients();
	const {
		name,
		context,
		clientId,
		attributes,
		setAttributes,
		chipText,
		setChipText,
		chipBackground,
		setChipBackground,
		chipBorder,
		setChipBorder,
	} = props;
	const { customChipText, customChipBackground, customChipBorder, layout } =
		attributes;
	const { filterData } = context;
	const { items } = filterData;

	// Extract attributes from block layout
	const layoutBlockSupport = getBlockSupport( name, 'layout' );
	const defaultBlockLayout = layoutBlockSupport?.default;
	const usedLayout = layout || defaultBlockLayout || {};

	const blockProps = useBlockProps( {
		className: clsx( 'wc-block-product-filter-removable-chips', {
			...getColorClasses( attributes ),
		} ),
		style: getColorVars( attributes ),
	} );

	const innerBlocksProps = useInnerBlocksProps( blockProps, {} );
	const removeText = ( label: string ): string => {
		return sprintf(
			/* translators: %s attribute value used in the filter. For example: yellow, green, small, large. */
			__( 'Remove %s filter', 'woocommerce' ),
			label
		);
	};

	return (
		<div { ...innerBlocksProps }>
			<BlockControls>
				<ToolbarGroup>
					<ToolbarButton
						icon={ arrowRight }
						label={ __( 'Horizontal', 'woocommerce' ) }
						onClick={ () =>
							setAttributes( {
								layout: {
									...usedLayout,
									orientation: 'horizontal',
								},
							} )
						}
						isPressed={
							usedLayout.orientation === 'horizontal' ||
							! usedLayout.orientation
						}
					/>
					<ToolbarButton
						icon={ arrowDown }
						label={ __( 'Vertical', 'woocommerce' ) }
						onClick={ () =>
							setAttributes( {
								layout: {
									...usedLayout,
									orientation: 'vertical',
								},
							} )
						}
						isPressed={ usedLayout.orientation === 'vertical' }
					/>
				</ToolbarGroup>
			</BlockControls>
			<ul className="wc-block-product-filter-removable-chips__items">
				{ items?.map( ( item, index ) => (
					<li
						key={ index }
						className="wc-block-product-filter-removable-chips__item"
					>
						<span className="wc-block-product-filter-removable-chips__label">
							{ item.type + ': ' + item.label }
						</span>
						<button className="wc-block-product-filter-removable-chips__remove">
							<Icon
								className="wc-block-product-filter-removable-chips__remove-icon"
								icon={ closeSmall }
								size={ 25 }
							/>
							<Label
								screenReaderLabel={ removeText(
									item.type + ': ' + item.label
								) }
							/>
						</button>
					</li>
				) ) }
			</ul>
			<InspectorControls group="color">
				{ colorGradientSettings.hasColorsOrGradients && (
					<ColorGradientSettingsDropdown
						__experimentalIsRenderedInSidebar
						settings={ [
							{
								label: __( 'Chip Text', 'woocommerce' ),
								colorValue: chipText.color || customChipText,
								onColorChange: ( colorValue: string ) => {
									setChipText( colorValue );
									setAttributes( {
										customChipText: colorValue,
									} );
								},
								resetAllFilter: () => {
									setChipText( '' );
									setAttributes( {
										customChipText: '',
									} );
								},
							},
							{
								label: __( 'Chip Border', 'woocommerce' ),
								colorValue:
									chipBorder.color || customChipBorder,
								onColorChange: ( colorValue: string ) => {
									setChipBorder( colorValue );
									setAttributes( {
										customChipBorder: colorValue,
									} );
								},
								resetAllFilter: () => {
									setChipBorder( '' );
									setAttributes( {
										customChipBorder: '',
									} );
								},
							},
							{
								label: __( 'Chip Background', 'woocommerce' ),
								colorValue:
									chipBackground.color ||
									customChipBackground,
								onColorChange: ( colorValue: string ) => {
									setChipBackground( colorValue );
									setAttributes( {
										customChipBackground: colorValue,
									} );
								},
								resetAllFilter: () => {
									setChipBackground( '' );
									setAttributes( {
										customChipBackground: '',
									} );
								},
							},
						] }
						panelId={ clientId }
						{ ...colorGradientSettings }
					/>
				) }
			</InspectorControls>
		</div>
	);
};

export default withColors( {
	chipText: 'chip-text',
	chipBorder: 'chip-border',
	chipBackground: 'chip-background',
} )( Edit );
