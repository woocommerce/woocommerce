/**
 * External dependencies
 */
import { getSetting } from '@woocommerce/settings';
import { AttributeSetting } from '@woocommerce/types';
import { InspectorControls } from '@wordpress/block-editor';
import { dispatch, useSelect } from '@wordpress/data';
import { createInterpolateElement, useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { Block, getBlockTypes, createBlock } from '@wordpress/blocks';
import {
	ComboboxControl,
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
import { getAttributeFromId } from './utils';
import { getInnerBlockByName } from '../../utils';

const ATTRIBUTES = getSetting< AttributeSetting[] >( 'attributes', [] );

let displayStyleOptions: Block[] = [];

export const Inspector = ( {
	clientId,
	attributes,
	setAttributes,
}: EditProps ) => {
	const {
		attributeId,
		sortOrder,
		queryType,
		displayStyle,
		showCounts,
		hideEmpty,
		clearButton,
	} = attributes;
	const { updateBlockAttributes, insertBlock, replaceBlock } =
		dispatch( 'core/block-editor' );
	const filterBlock = useSelect(
		( select ) => {
			return select( 'core/block-editor' ).getBlock( clientId );
		},
		[ clientId ]
	);
	const [ displayStyleBlocksAttributes, setDisplayStyleBlocksAttributes ] =
		useState< Record< string, unknown > >( {} );

	const filterHeadingBlock = getInnerBlockByName(
		filterBlock,
		'core/heading'
	);

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
				<PanelBody title={ __( 'Attribute', 'woocommerce' ) }>
					<ComboboxControl
						options={ ATTRIBUTES.map( ( item ) => ( {
							value: item.attribute_id,
							label: item.attribute_label,
						} ) ) }
						value={ attributeId + '' }
						onChange={ ( value ) => {
							const numericId = parseInt( value || '', 10 );
							setAttributes( {
								attributeId: numericId,
							} );
							const attributeObject =
								getAttributeFromId( numericId );
							if ( filterHeadingBlock ) {
								updateBlockAttributes(
									filterHeadingBlock.clientId,
									{
										content:
											attributeObject?.label ??
											__( 'Attribute', 'woocommerce' ),
									}
								);
							}
						} }
						help={ __(
							'Choose the attribute to show in this filter.',
							'woocommerce'
						) }
					/>
				</PanelBody>
				<PanelBody title={ __( 'Settings', 'woocommerce' ) }>
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
							setAttributes( { sortOrder: value } )
						}
						help={ __(
							'Determine the order of filter options.',
							'woocommerce'
						) }
					/>
					<ToggleGroupControl
						label={ __( 'Logic', 'woocommerce' ) }
						value={ queryType }
						onChange={ ( value: BlockAttributes[ 'queryType' ] ) =>
							setAttributes( { queryType: value } )
						}
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
				</PanelBody>
			</InspectorControls>
			<InspectorControls group="styles">
				<PanelBody title={ __( 'Display', 'woocommerce' ) }>
					<ToggleGroupControl
						value={ displayStyle }
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
					/>
					<ToggleControl
						label={ __( 'Empty filter options', 'woocommerce' ) }
						checked={ ! hideEmpty }
						onChange={ ( value ) =>
							setAttributes( { hideEmpty: ! value } )
						}
					/>
					<ToggleControl
						label={ __( 'Clear button', 'woocommerce' ) }
						checked={ clearButton }
						onChange={ ( value ) =>
							setAttributes( { clearButton: value } )
						}
					/>
				</PanelBody>
			</InspectorControls>
		</>
	);
};
