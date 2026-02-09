/**
 * External dependencies
 */
import { InspectorControls } from '@wordpress/block-editor';
import { createInterpolateElement } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { Block, getBlockTypes } from '@wordpress/blocks';
import {
	SelectControl,
	ToggleControl,
	// eslint-disable-next-line @wordpress/no-unsafe-wp-apis
	__experimentalToggleGroupControl as ToggleGroupControl,
	// eslint-disable-next-line @wordpress/no-unsafe-wp-apis
	__experimentalToggleGroupControlOption as ToggleGroupControlOption,
	// eslint-disable-next-line @wordpress/no-unsafe-wp-apis
	__experimentalToolsPanel as ToolsPanel,
	// eslint-disable-next-line @wordpress/no-unsafe-wp-apis
	__experimentalToolsPanelItem as ToolsPanelItem,
} from '@wordpress/components';

/**
 * Internal dependencies
 */
import { sortOrderOptions, sortOrders } from './constants';
import { EditProps, DEFAULT_SORT_ORDER, DEFAULT_QUERY_TYPE } from './types';
import metadata from './block.json';
import {
	DisplayStyleSwitcher,
	resetDisplayStyleBlock,
} from '../../components/display-style-switcher';

let displayStyleOptions: Block[] = [];

export const Inspector = ( {
	clientId,
	attributes,
	setAttributes,
}: EditProps ) => {
	const { sortOrder, queryType, displayStyle, showCounts, hideEmpty } =
		attributes;

	if ( displayStyleOptions.length === 0 ) {
		displayStyleOptions = getBlockTypes().filter( ( blockType ) =>
			blockType.ancestor?.includes(
				'woocommerce/product-filter-attribute'
			)
		);
	}

	return (
		<>
			<InspectorControls key="inspector">
				<ToolsPanel
					label={ __( 'Display Settings', 'woocommerce' ) }
					resetAll={ () => {
						setAttributes( {
							sortOrder: DEFAULT_SORT_ORDER,
							queryType: DEFAULT_QUERY_TYPE,
							displayStyle:
								metadata.attributes.displayStyle.default,
							showCounts: metadata.attributes.showCounts.default,
							hideEmpty: metadata.attributes.hideEmpty.default,
						} );
						resetDisplayStyleBlock(
							clientId,
							metadata.attributes.displayStyle.default
						);
					} }
				>
					<ToolsPanelItem
						label={ __( 'Sort Order', 'woocommerce' ) }
						hasValue={ () => sortOrder !== DEFAULT_SORT_ORDER }
						onDeselect={ () =>
							setAttributes( {
								sortOrder: DEFAULT_SORT_ORDER,
							} )
						}
					>
						<SelectControl
							label={ __( 'Sort order', 'woocommerce' ) }
							value={ sortOrder }
							options={ [
								{
									value: '',
									label: __(
										'Select an option',
										'woocommerce'
									),
									disabled: true,
								},
								...sortOrderOptions,
							] }
							onChange={ ( value ) => {
								if (
									value &&
									Object.keys( sortOrders ).includes( value )
								) {
									setAttributes( {
										sortOrder:
											value as keyof typeof sortOrders,
									} );
								}
							} }
							help={ __(
								'Determine the order of filter options.',
								'woocommerce'
							) }
							__nextHasNoMarginBottom
						/>
					</ToolsPanelItem>
					<ToolsPanelItem
						label={ __( 'Logic', 'woocommerce' ) }
						hasValue={ () => queryType !== DEFAULT_QUERY_TYPE }
						onDeselect={ () =>
							setAttributes( {
								queryType: DEFAULT_QUERY_TYPE,
							} )
						}
					>
						<ToggleGroupControl
							label={ __( 'Logic', 'woocommerce' ) }
							isBlock
							value={ queryType }
							onChange={ ( value ) => {
								if ( value === 'and' || value === 'or' ) {
									setAttributes( { queryType: value } );
								}
							} }
							__nextHasNoMarginBottom
							__next40pxDefaultSize
							style={ { width: '100%' } }
							help={
								queryType === 'and'
									? createInterpolateElement(
											__(
												'Display products that match <b>all</b> selected attributes (they need to include <b>all of them</b>).',
												'woocommerce'
											),
											{
												b: <strong />,
											}
									  )
									: __(
											"Display products that match any of the selected attributes (they don't need to match all).",
											'woocommerce'
									  )
							}
						>
							<ToggleGroupControlOption
								label={ __( 'Any', 'woocommerce' ) }
								value="or"
							/>
							<ToggleGroupControlOption
								label={ __( 'All', 'woocommerce' ) }
								value="and"
							/>
						</ToggleGroupControl>
					</ToolsPanelItem>
					<ToolsPanelItem
						label={ __( 'Display Style', 'woocommerce' ) }
						hasValue={ () =>
							displayStyle !==
							metadata.attributes.displayStyle.default
						}
						isShownByDefault={ true }
						onDeselect={ () => {
							setAttributes( {
								displayStyle:
									metadata.attributes.displayStyle.default,
							} );
							resetDisplayStyleBlock(
								clientId,
								metadata.attributes.displayStyle.default
							);
						} }
					>
						<DisplayStyleSwitcher
							clientId={ clientId }
							currentStyle={ displayStyle }
							onChange={ ( value ) =>
								setAttributes( { displayStyle: value } )
							}
						/>
					</ToolsPanelItem>
					<ToolsPanelItem
						label={ __( 'Product counts', 'woocommerce' ) }
						hasValue={ () =>
							showCounts !==
							metadata.attributes.showCounts.default
						}
						onDeselect={ () =>
							setAttributes( {
								showCounts:
									metadata.attributes.showCounts.default,
							} )
						}
						isShownByDefault={ true }
					>
						<ToggleControl
							label={ __( 'Product counts', 'woocommerce' ) }
							checked={ showCounts }
							onChange={ ( value ) =>
								setAttributes( { showCounts: value } )
							}
							__nextHasNoMarginBottom
						/>
					</ToolsPanelItem>
					<ToolsPanelItem
						label={ __(
							'Hide items with no products',
							'woocommerce'
						) }
						hasValue={ () =>
							hideEmpty !== metadata.attributes.hideEmpty.default
						}
						onDeselect={ () =>
							setAttributes( {
								hideEmpty:
									metadata.attributes.hideEmpty.default,
							} )
						}
					>
						<ToggleControl
							label={ __(
								'Hide items with no products',
								'woocommerce'
							) }
							checked={ hideEmpty }
							onChange={ ( value ) =>
								setAttributes( { hideEmpty: value } )
							}
							__nextHasNoMarginBottom
						/>
					</ToolsPanelItem>
				</ToolsPanel>
			</InspectorControls>
		</>
	);
};
