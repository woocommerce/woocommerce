/**
 * External dependencies
 */
import { InspectorControls } from '@wordpress/block-editor';
import { dispatch, useSelect } from '@wordpress/data';
import { createInterpolateElement, useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { Block, getBlockTypes, createBlock } from '@wordpress/blocks';
import { getInnerBlockByName } from '@woocommerce/utils';
import {
	SelectControl,
	ToggleControl,
	// @ts-expect-error - no types.
	// eslint-disable-next-line @wordpress/no-unsafe-wp-apis
	__experimentalToggleGroupControl as ToggleGroupControl,
	// @ts-expect-error - no types.
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
import { BlockAttributes, EditProps } from './types';
import metadata from './block.json';
import { resetDisplayStyleBlock } from '../../components/display-style-switcher';

let displayStyleOptions: Block[] = [];

export const Inspector = ( {
	clientId,
	attributes,
	setAttributes,
}: EditProps ) => {
	const { sortOrder, queryType, displayStyle, showCounts, hideEmpty } =
		attributes;
	const { insertBlock, replaceBlock } = dispatch( 'core/block-editor' );
	const filterBlock = useSelect(
		( select ) => {
			return select( 'core/block-editor' ).getBlock( clientId );
		},
		[ clientId ]
	);
	const [ displayStyleBlocksAttributes, setDisplayStyleBlocksAttributes ] =
		useState< Record< string, unknown > >( {} );

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
							sortOrder: metadata.attributes.sortOrder.default,
							queryType: metadata.attributes.queryType.default,
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
						hasValue={ () =>
							sortOrder !== metadata.attributes.sortOrder.default
						}
						onDeselect={ () =>
							setAttributes( {
								sortOrder:
									metadata.attributes.sortOrder.default,
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
						hasValue={ () =>
							queryType !== metadata.attributes.queryType.default
						}
						onDeselect={ () =>
							setAttributes( {
								queryType:
									metadata.attributes.queryType.default,
							} )
						}
					>
						<ToggleGroupControl
							label={ __( 'Logic', 'woocommerce' ) }
							isBlock
							value={ queryType }
							onChange={ (
								value: BlockAttributes[ 'queryType' ]
							) => setAttributes( { queryType: value } ) }
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
						<ToggleGroupControl
							value={ displayStyle }
							isBlock
							__nextHasNoMarginBottom
							__next40pxDefaultSize
							onChange={ (
								value: BlockAttributes[ 'displayStyle' ]
							) => {
								if ( ! filterBlock ) return;
								const currentStyleBlock = getInnerBlockByName(
									filterBlock,
									displayStyle
								);

								if ( currentStyleBlock ) {
									setDisplayStyleBlocksAttributes( {
										...displayStyleBlocksAttributes,
										[ displayStyle ]:
											currentStyleBlock.attributes,
									} );
									replaceBlock(
										currentStyleBlock.clientId,
										createBlock(
											value,
											displayStyleBlocksAttributes[
												value
											] || {}
										)
									);
								} else {
									insertBlock(
										createBlock( value ),
										filterBlock.innerBlocks.length,
										filterBlock.clientId,
										false
									);
								}
								setAttributes( { displayStyle: value } );
							} }
							style={ { width: '100%' } }
						>
							{ displayStyleOptions.map( ( blockType ) => (
								<ToggleGroupControlOption
									key={ blockType.name }
									label={ blockType.title }
									value={ blockType.name }
								/>
							) ) }
						</ToggleGroupControl>
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
