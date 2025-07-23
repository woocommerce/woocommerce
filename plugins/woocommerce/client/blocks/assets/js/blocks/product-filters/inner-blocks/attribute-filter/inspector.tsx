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
	PanelBody,
	SelectControl,
	ToggleControl,
	// @ts-expect-error - no types.
	// eslint-disable-next-line @wordpress/no-unsafe-wp-apis
	__experimentalToggleGroupControl as ToggleGroupControl,
	// @ts-expect-error - no types.
	// eslint-disable-next-line @wordpress/no-unsafe-wp-apis
	__experimentalToggleGroupControlOption as ToggleGroupControlOption,
} from '@wordpress/components';

/**
 * Internal dependencies
 */
import { sortOrderOptions } from './constants';
import { BlockAttributes, EditProps } from './types';

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
				<PanelBody title={ __( 'Display', 'woocommerce' ) }>
					<SelectControl
						label={ __( 'Sort order', 'woocommerce' ) }
						value={ sortOrder }
						options={ [
							{
								value: '',
								label: __( 'Select an option', 'woocommerce' ),
								disabled: true,
							},
							...sortOrderOptions,
						] }
						onChange={ ( value ) =>
							setAttributes( {
								sortOrder:
									value as BlockAttributes[ 'sortOrder' ],
							} )
						}
						help={ __(
							'Determine the order of filter options.',
							'woocommerce'
						) }
						__nextHasNoMarginBottom
					/>
					<ToggleGroupControl
						label={ __( 'Logic', 'woocommerce' ) }
						isBlock
						value={ queryType }
						onChange={ ( value: BlockAttributes[ 'queryType' ] ) =>
							setAttributes( { queryType: value } )
						}
						__nextHasNoMarginBottom
						__next40pxDefaultSize
						style={ { width: '100%' } }
						help={
							queryType === 'and'
								? createInterpolateElement(
										__(
											'Show results for <b>all</b> selected attributes. Displayed products must contain <b>all of them</b> to appear in the results.',
											'woocommerce'
										),
										{
											b: <strong />,
										}
								  )
								: __(
										'Show results for any of the attributes selected (displayed products don’t have to have them all).',
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
										displayStyleBlocksAttributes[ value ] ||
											{}
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
					<ToggleControl
						label={ __( 'Product counts', 'woocommerce' ) }
						checked={ showCounts }
						onChange={ ( value ) =>
							setAttributes( { showCounts: value } )
						}
						__nextHasNoMarginBottom
					/>
					<ToggleControl
						label={ __( 'Empty filter options', 'woocommerce' ) }
						checked={ ! hideEmpty }
						onChange={ ( value ) =>
							setAttributes( { hideEmpty: ! value } )
						}
						__nextHasNoMarginBottom
					/>
				</PanelBody>
			</InspectorControls>
		</>
	);
};
